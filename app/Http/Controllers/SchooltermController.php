<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schoolterm;
use Illuminate\Support\Facades\DB;

class SchooltermController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View term|Create term|Update term|Delete term', ['only' => ['index']]);
        $this->middleware('permission:Create term', ['only' => ['store']]);
        $this->middleware('permission:Update term', ['only' => ['update', 'updateterm', 'updateStatus', 'updatePromotional']]);
        $this->middleware('permission:Delete term', ['only' => ['destroy', 'deleteterm']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Term Management";

        $query = Schoolterm::query();

        if ($request->filled('search')) {
            $query->where('term', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $terms = $query->latest()->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'terms'      => $terms->items(),
                'pagination' => $terms->links()->toHtml(),
            ]);
        }

        return view('term.index', compact('terms', 'pagetitle'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'term'           => 'required|string|max:255|unique:schoolterm,term',
            'status'         => 'sometimes|boolean',
            'is_promotional' => 'sometimes|boolean',
        ]);

        $term = null;

        DB::transaction(function () use ($validated, &$term) {
            if (!empty($validated['is_promotional'])) {
                Schoolterm::where('is_promotional', true)->update(['is_promotional' => false]);
            }

            $term = Schoolterm::create([
                'term'           => $validated['term'],
                'status'         => $validated['status']         ?? true,
                'is_promotional' => $validated['is_promotional'] ?? false,
            ]);
        });

        Schoolterm::forgetCurrent();

        return response()->json([
            'success' => true,
            'message' => 'Term created successfully',
            'term'    => $term,
        ]);
    }

    public function update(Request $request, $id)
    {
        $term = Schoolterm::findOrFail($id);

        $validated = $request->validate([
            'term'           => 'required|string|max:255|unique:schoolterm,term,' . $id,
            'status'         => 'sometimes|boolean',
            'is_promotional' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($validated, $term, $id) {
            if (!empty($validated['is_promotional'])) {
                Schoolterm::where('is_promotional', true)
                    ->where('id', '!=', $id)
                    ->update(['is_promotional' => false]);
            }
            $term->update($validated);
        });

        Schoolterm::forgetCurrent();

        return response()->json([
            'success' => true,
            'message' => 'Term updated successfully',
            'term'    => $term->fresh(),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|boolean']);

        $term = Schoolterm::findOrFail($id);

        DB::transaction(function () use ($request, $term) {
            if ($request->boolean('status')) {
                // Ensure only one active term
                Schoolterm::where('id', '!=', $term->id)->update(['status' => false]);
            }
            $term->update(['status' => $request->boolean('status')]);
        });

        Schoolterm::forgetCurrent();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'term'    => $term->fresh(),
        ]);
    }

    /**
     * Toggle the promotional term flag via AJAX.
     * Ensures only one term is ever promotional at a time.
     */
    public function updatePromotional(Request $request, $id)
    {
        $request->validate(['is_promotional' => 'required|boolean']);

        // Make sure the term exists before entering the transaction
        $term = Schoolterm::findOrFail($id);

        DB::transaction(function () use ($request, $id, $term) {
            if ($request->boolean('is_promotional')) {
                Schoolterm::where('is_promotional', true)
                    ->where('id', '!=', $id)
                    ->update(['is_promotional' => false]);
            }
            $term->update(['is_promotional' => $request->boolean('is_promotional')]);
        });

        Schoolterm::forgetCurrent();

        $term->refresh();

        return response()->json([
            'success' => true,
            'message' => $term->is_promotional
                ? "'{$term->term}' is now the promotional term."
                : "'{$term->term}' is no longer the promotional term.",
            'term'    => $term,
        ]);
    }

    public function destroy($id)
    {
        $term = Schoolterm::findOrFail($id);
        $term->delete();

        Schoolterm::forgetCurrent();

        return response()->json([
            'success' => true,
            'message' => 'Term deleted successfully',
        ]);
    }

    public function updateterm(Request $request)
    {
        $validated = $request->validate([
            'id'             => 'required|exists:schoolterm,id',
            'term'           => 'required|string|max:255|unique:schoolterm,term,' . $request->id,
            'status'         => 'sometimes|boolean',
            'is_promotional' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($validated) {
            if (!empty($validated['is_promotional'])) {
                Schoolterm::where('is_promotional', true)
                    ->where('id', '!=', $validated['id'])
                    ->update(['is_promotional' => false]);
            }
            Schoolterm::findOrFail($validated['id'])->update($validated);
        });

        Schoolterm::forgetCurrent();

        return response()->json([
            'success' => true,
            'message' => 'Term updated successfully',
        ]);
    }

    public function deleteterm(Request $request)
    {
        $request->validate(['termid' => 'required|exists:schoolterm,id']);
        Schoolterm::findOrFail($request->termid)->delete();

        Schoolterm::forgetCurrent();

        return response()->json([
            'success' => true,
            'message' => 'Term deleted successfully',
        ]);
    }
}