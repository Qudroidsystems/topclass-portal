<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolInformationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View schoolinformation|Create schoolinformation|Update schoolinformation|Delete schoolinformation', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create schoolinformation', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update schoolinformation', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete schoolinformation', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $pagetitle = "School Information Management";
        $data = SchoolInformation::latest()->paginate(10);
        $status_counts = [
            'Active' => SchoolInformation::where('is_active', true)->count(),
            'Inactive' => SchoolInformation::where('is_active', false)->count(),
        ];

        if (config('app.debug')) {
            \Log::info('School information loaded:', ['count' => $data->count()]);
        }

        return view('schoolinformation.index', compact('data', 'pagetitle', 'status_counts'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    public function create(): View
    {
        $title = "Create School Information";
        return view('schoolinformation.create', compact('title'));
    }

    public function store(Request $request): JsonResponse
    {
        \Log::debug("Creating school information", $request->all());

        if (!auth()->user()->hasPermissionTo('Create schoolinformation')) {
            \Log::warning("User ID " . auth()->user()->id . " attempted to create school information without 'Create schoolinformation' permission");
            return response()->json([
                'success' => false,
                'message' => 'User does not have the right permissions',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'school_name' => 'required|string|max:255',
                'school_address' => 'required|string|max:500',
                'school_phone' => 'required|string|max:20',
                'school_email' => 'required|email:rfc,dns|unique:school_information,school_email',
                'school_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'school_motto' => 'nullable|string|max:255',
                'school_website' => 'nullable|url|max:255',
                'no_of_times_school_opened' => 'required|integer|min:0',
                'date_school_opened' => 'nullable|date',
                'date_next_term_begins' => 'nullable|date',
                'is_active' => 'boolean',
            ], [
                'no_of_times_school_opened.integer' => 'The number of times school opened must be a valid integer.',
                'date_school_opened.date' => 'The date school opened must be a valid date.',
                'date_next_term_begins.date' => 'The date next term begins must be a valid date.',
            ]);

            if ($request->hasFile('school_logo')) {
                $path = $request->file('school_logo')->store('school_logos', 'public');
                $validated['school_logo'] = $path;
            }

            if ($request->hasFile('app_logo')) {
                $path = $request->file('app_logo')->store('app_logos', 'public');
                $validated['app_logo'] = $path;
            }

            if ($validated['is_active']) {
                SchoolInformation::where('is_active', true)->update(['is_active' => false]);
            }

            $school = SchoolInformation::create($validated);

            \Log::debug("School information created successfully: ID {$school->id}");
            return response()->json([
                'success' => true,
                'message' => 'School information created successfully',
                'school' => [
                    'id' => $school->id,
                    'school_name' => $school->school_name,
                    'school_email' => $school->school_email,
                    'is_active' => $school->is_active,
                ],
            ], 201);
        } catch (ValidationException $e) {
            \Log::error("Validation error creating school information: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Create school information error: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to create school information: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): View
    {
        $pagetitle = "School Information Overview";
        $school = SchoolInformation::findOrFail($id);
        return view('schoolinformation.show', compact('school', 'pagetitle'));
    }

    public function edit($id): View
    {
        $school = SchoolInformation::findOrFail($id);
        return view('schoolinformation.edit', compact('school'));
    }

    public function update(Request $request, $id)
{
    // Check if this is a POST request with _update flag or PUT request
    $isUpdate = $request->has('_update') || $request->method() === 'PUT';

    if (!$isUpdate) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid request method for update'
        ], 405);
    }

    \Log::debug("Updating school information ID: {$id}", $request->all());

    try {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_address' => 'required|string|max:500',
            'school_phone' => 'required|string|max:20',
            'school_email' => 'required|email|unique:school_information,school_email,' . $id,
            'school_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'school_motto' => 'nullable|string|max:255',
            'school_website' => 'nullable|url|max:255',
            'no_of_times_school_opened' => 'required|integer|min:0',
            'date_school_opened' => 'nullable|date',
            'date_next_term_begins' => 'nullable|date',
            'is_active' => 'boolean',
        ], [
            'no_of_times_school_opened.integer' => 'The number of times school opened must be a valid integer.',
            'date_school_opened.date' => 'The date school opened must be a valid date.',
            'date_next_term_begins.date' => 'The date next term begins must be a valid date.',
        ]);

        $school = SchoolInformation::findOrFail($id);

        if ($request->hasFile('school_logo')) {
            if ($school->school_logo && Storage::disk('public')->exists($school->school_logo)) {
                Storage::disk('public')->delete($school->school_logo);
            }
            $path = $request->file('school_logo')->store('school_logos', 'public');
            $validated['school_logo'] = $path;
        } else {
            $validated['school_logo'] = $school->school_logo;
        }

        if ($request->hasFile('app_logo')) {
            if ($school->app_logo && Storage::disk('public')->exists($school->app_logo)) {
                Storage::disk('public')->delete($school->app_logo);
            }
            $path = $request->file('app_logo')->store('app_logos', 'public');
            $validated['app_logo'] = $path;
        } else {
            $validated['app_logo'] = $school->app_logo;
        }

        if ($validated['is_active']) {
            SchoolInformation::where('is_active', true)->where('id', '!=', $id)->update(['is_active' => false]);
        }

        $school->update($validated);

        \Log::debug("School information ID: {$id} updated successfully");

        return response()->json([
            'success' => true,
            'message' => 'School information updated successfully',
            'school' => [
                'id' => $school->id,
                'school_name' => $school->school_name,
                'school_email' => $school->school_email,
                'is_active' => $school->is_active,
            ],
        ], 200);
    } catch (ValidationException $e) {
        \Log::error("Validation error updating school information ID {$id}: " . json_encode($e->errors()));
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        \Log::error("Update school information error for ID {$id}: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
        return response()->json([
            'success' => false,
            'message' => 'Failed to update school information: ' . $e->getMessage(),
        ], 500);
    }
}

    public function destroy($id): JsonResponse
    {
        \Log::debug("Attempting to delete school information ID: {$id}");
        try {
            $school = SchoolInformation::findOrFail($id);

            if ($school->school_logo && Storage::disk('public')->exists($school->school_logo)) {
                Storage::disk('public')->delete($school->school_logo);
            }

            if ($school->app_logo && Storage::disk('public')->exists($school->app_logo)) {
                Storage::disk('public')->delete($school->app_logo);
            }

            $school->delete();

            \Log::debug("School information ID: {$id} deleted successfully");
            return response()->json([
                'success' => true,
                'message' => 'School information deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error("Delete school information error for ID {$id}: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete school information: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function editJson($id): JsonResponse
    {
        try {
            $school = SchoolInformation::findOrFail($id);

            return response()->json([
                'success' => true,
                'school' => [
                    'id' => $school->id,
                    'school_name' => $school->school_name,
                    'school_address' => $school->school_address,
                    'school_phone' => $school->school_phone,
                    'school_email' => $school->school_email,
                    'school_motto' => $school->school_motto,
                    'school_website' => $school->school_website,
                    'no_of_times_school_opened' => $school->no_of_times_school_opened,
                    'date_school_opened' => $school->date_school_opened,
                    'date_next_term_begins' => $school->date_next_term_begins,
                    'is_active' => $school->is_active,
                    'logo_url' => $school->getLogoUrlAttribute(),
                    'app_logo_url' => $school->getAppLogoUrlAttribute(),
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error("Edit JSON error for ID {$id}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to load school data',
            ], 500);
        }
    }
}
