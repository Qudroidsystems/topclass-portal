<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schoolterm;
use Illuminate\Support\Facades\Log;

class SchooltermController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View term|Create term|Update term|Delete term', ['only' => ['index']]);
        $this->middleware('permission:Create term', ['only' => ['store']]);
        $this->middleware('permission:Update term', ['only' => ['update', 'updateterm', 'updateStatus']]);
        $this->middleware('permission:Delete term', ['only' => ['destroy', 'deleteterm']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Term Management";

        $query = Schoolterm::query();

        if ($request->filled('search')) {
            $query->where('term', 'like', '%' . $request->search . '%');
        }

        // Add filter by status if needed
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $terms = $query->latest()->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'terms'      => $terms->items(),
                'pagination' => $terms->links()->toHtml()
            ]);
        }

        return view('term.index', compact('terms', 'pagetitle'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'term'   => 'required|string|max:255|unique:schoolterm,term',
            'status' => 'sometimes|boolean'
        ]);

        $term = Schoolterm::create([
            'term'   => $validated['term'],
            'status' => $validated['status'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Term created successfully',
            'term' => $term
        ]);
    }

    public function update(Request $request, $id)
    {
        $term = Schoolterm::findOrFail($id);

        $validated = $request->validate([
            'term'   => 'required|string|max:255|unique:schoolterm,term,' . $id,
            'status' => 'sometimes|boolean'
        ]);

        $term->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Term updated successfully',
            'term' => $term
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|boolean'
        ]);

        $term = Schoolterm::findOrFail($id);
        $term->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'term' => $term
        ]);
    }

    public function destroy($id)
    {
        $term = Schoolterm::findOrFail($id);
        $term->delete();

        return response()->json([
            'success' => true,
            'message' => 'Term deleted successfully'
        ]);
    }

    // AJAX helpers
    public function updateterm(Request $request)
    {
        $validated = $request->validate([
            'id'     => 'required|exists:schoolterm,id',
            'term'   => 'required|string|max:255|unique:schoolterm,term,' . $request->id,
            'status' => 'sometimes|boolean'
        ]);

        $term = Schoolterm::findOrFail($validated['id']);
        $term->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Term updated successfully'
        ]);
    }

    public function deleteterm(Request $request)
    {
        $request->validate(['termid' => 'required|exists:schoolterm,id']);

        Schoolterm::findOrFail($request->termid)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Term deleted successfully'
        ]);
    }
}
