<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classcategory;
use Illuminate\Support\Facades\Log;

class ClasscategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View class-category|Create class-category|Update class-category|Delete class-category', ['only' => ['index']]);
        $this->middleware('permission:Create class-category', ['only' => ['store']]);
        $this->middleware('permission:Update class-category', ['only' => ['update', 'updateclasscategory']]);
        $this->middleware('permission:Delete class-category', ['only' => ['destroy', 'deleteclasscategory']]);
    }

    public function index(Request $request)
    {
        Log::info('Index Class Category Request:', $request->all());
        $pagetitle = "Class Category Management";
        $query = Classcategory::query();

        if ($request->has('search')) {
            $query->where('category', 'like', '%' . $request->query('search') . '%')
                  ->orWhere('ca1score', 'like', '%' . $request->query('search') . '%')
                  ->orWhere('ca2score', 'like', '%' . $request->query('search') . '%')
                  ->orWhere('ca3score', 'like', '%' . $request->query('search') . '%')
                  ->orWhere('examscore', 'like', '%' . $request->query('search') . '%');
        }

        $classcategories = $query->orderBy('category')->paginate(10);

        if ($request->ajax()) {
            return response()->json(['categories' => $classcategories->items()]);
        }

        return view('classcategories.index')->with('classcategories', $classcategories)->with('pagetitle', $pagetitle);
    }

    public function store(Request $request)
    {
        Log::info('Store Class Category Request:', $request->all());
        $request->validate([
            'category' => 'required|string|max:255|unique:classcategories,category',
            'ca1score' => 'required|numeric|min:0',
            'ca2score' => 'required|numeric|min:0',
            'ca3score' => 'required|numeric|min:0',
            'examscore' => 'required|numeric|min:0',
            'is_senior' => 'required|boolean'
        ]);

        $checkCategory = Classcategory::where('category', $request->input('category'))->exists();
        if ($checkCategory) {
            Log::warning('Class category already taken:', ['category' => $request->input('category')]);
            return response()->json(['success' => false, 'message' => 'Class category is already taken'], 422);
        }

        $category = Classcategory::create([
            'category' => $request->input('category'),
            'ca1score' => $request->input('ca1score'),
            'ca2score' => $request->input('ca2score'),
            'ca3score' => $request->input('ca3score'),
            'examscore' => $request->input('examscore'),
            'is_senior' => $request->input('is_senior')
        ]);
        Log::info('Class Category Created:', $category->toArray());

        return response()->json(['success' => true, 'message' => 'Class category has been created successfully']);
    }

    public function update(Request $request, $id)
    {
        Log::info('Update Class Category Request:', ['id' => $id, 'data' => $request->all()]);
        $request->validate([
            'category' => "required|string|max:255|unique:classcategories,category,$id",
            'ca1score' => 'required|numeric|min:0',
            'ca2score' => 'required|numeric|min:0',
            'ca3score' => 'required|numeric|min:0',
            'examscore' => 'required|numeric|min:0',
            'is_senior' => 'required|boolean'
        ]);

        $checkCategory = Classcategory::where('category', $request->input('category'))->where('id', '!=', $id)->exists();
        if ($checkCategory) {
            Log::warning('Class category already taken:', ['category' => $request->input('category')]);
            return response()->json(['success' => false, 'message' => 'Class category is already taken'], 422);
        }

        $category = Classcategory::findOrFail($id);
        $category->update([
            'category' => $request->input('category'),
            'ca1score' => $request->input('ca1score'),
            'ca2score' => $request->input('ca2score'),
            'ca3score' => $request->input('ca3score'),
            'examscore' => $request->input('examscore'),
            'is_senior' => $request->input('is_senior')
        ]);
        Log::info('Class Category Updated:', $category->toArray());

        return response()->json(['success' => true, 'message' => 'Class category has been updated successfully']);
    }

    public function destroy($id)
    {
        Log::info('Delete Class Category Request:', ['id' => $id]);
        $category = Classcategory::findOrFail($id);
        $category->delete();
        Log::info('Class Category Deleted:', ['id' => $id]);

        return response()->json(['success' => true, 'message' => 'Class category has been deleted successfully']);
    }

    public function deleteclasscategory(Request $request)
    {
        Log::info('Delete Class Category AJAX Request:', $request->all());
        $request->validate(['classcategoryid' => 'required|exists:classcategories,id']);
        $category = Classcategory::findOrFail($request->classcategoryid);
        $category->delete();
        Log::info('Class Category Deleted via AJAX:', ['id' => $request->classcategoryid]);

        return response()->json(['success' => true, 'message' => 'Class category has been deleted successfully']);
    }

    public function updateclasscategory(Request $request)
    {
        Log::info('Update Class Category AJAX Request:', $request->all());
        $request->validate([
            'id' => 'required|exists:classcategories,id',
            'category' => "required|string|max:255|unique:classcategories,category,{$request->id}",
            'ca1score' => 'required|numeric|min:0',
            'ca2score' => 'required|numeric|min:0',
            'ca3score' => 'required|numeric|min:0',
            'examscore' => 'required|numeric|min:0',
            'is_senior' => 'required|boolean'
        ]);

        $checkCategory = Classcategory::where('category', $request->input('category'))->where('id', '!=', $request->id)->exists();
        if ($checkCategory) {
            Log::warning('Class category already taken:', ['category' => $request->input('category')]);
            return response()->json(['success' => false, 'message' => 'Class category is already taken'], 422);
        }

        $category = Classcategory::findOrFail($request->id);
        $category->update([
            'category' => $request->input('category'),
            'ca1score' => $request->input('ca1score'),
            'ca2score' => $request->input('ca2score'),
            'ca3score' => $request->input('ca3score'),
            'examscore' => $request->input('examscore'),
            'is_senior' => $request->input('is_senior')
        ]);
        Log::info('Class Category Updated via AJAX:', $category->toArray());

        return response()->json(['success' => true, 'message' => 'Class category has been updated successfully']);
    }
}