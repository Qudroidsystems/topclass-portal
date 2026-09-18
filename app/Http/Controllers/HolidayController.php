<?php
// app/Http/Controllers/HolidayController.php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Schoolsession;
use App\Models\TimetableOverride;
use App\Models\TimetableSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View holidays', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create holidays', ['only' => ['store']]);
        $this->middleware('permission:Edit holidays', ['only' => ['update']]);
        $this->middleware('permission:Delete holidays', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = 'Holiday Management';

        $query = Holiday::with(['session', 'term', 'creator'])->orderBy('date', 'desc');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        if (in_array($request->query('type'), ['full', 'half'], true)) {
            $query->where('is_full_day', $request->query('type') === 'full');
        }

        if ($sessionId = $request->query('session_id')) {
            $query->where('session_id', $sessionId);
        }

        $holidays = $query->paginate(15)->appends($request->query());

        $upcomingHolidays = Holiday::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->take(5)
            ->get();

        $sessions = Schoolsession::orderByDesc('id')->get();

        $stats = [
            'total'    => Holiday::count(),
            'upcoming' => Holiday::where('date', '>=', now()->toDateString())->count(),
            'full_day' => Holiday::where('is_full_day', true)->count(),
            'half_day' => Holiday::where('is_full_day', false)->count(),
        ];

        return view('holidays.index', compact('pagetitle', 'holidays', 'upcomingHolidays', 'sessions', 'stats'));
    }

    /**
     * GET /holidays/{id}
     * Returns a single holiday record as JSON (used by the edit modal).
     */
    public function show(int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);
        return response()->json(['success' => true, 'holiday' => $holiday]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date'              => 'required|date',
            'title'             => 'required|string|max:200',
            'is_full_day'       => 'boolean',
            'cutoff_time'       => 'nullable|date_format:H:i|required_if:is_full_day,false',
            'session_id'        => 'nullable|exists:schoolsession,id',
            'term_id'           => 'nullable|exists:schoolterm,id',
            'affects_timetable' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $isFullDay = $validated['is_full_day'] ?? true;

            $holiday = Holiday::create([
                'date'        => $validated['date'],
                'title'       => $validated['title'],
                'is_full_day' => $isFullDay,
                'cutoff_time' => $isFullDay ? null : ($validated['cutoff_time'] ?? null),
                'session_id'  => $validated['session_id'] ?? null,
                'term_id'     => $validated['term_id'] ?? null,
                'created_by'  => Auth::id(),
            ]);

            if ($validated['affects_timetable'] ?? false) {
                $this->createHolidayOverrides($holiday);
            }

            DB::commit();
            return response()->json(['success' => true, 'holiday' => $holiday, 'message' => 'Holiday created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create holiday: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);

        $validated = $request->validate([
            'date'              => 'required|date',
            'title'             => 'required|string|max:200',
            'is_full_day'       => 'boolean',
            'cutoff_time'       => 'nullable|date_format:H:i|required_if:is_full_day,false',
            'session_id'        => 'nullable|exists:schoolsession,id',
            'term_id'           => 'nullable|exists:schoolterm,id',
            'affects_timetable' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Remove overrides previously generated for this holiday's OLD
            // date/title, before either changes below.
            TimetableOverride::where('override_type', 'holiday')
                ->where('override_date', $holiday->date)
                ->where('title', $holiday->title)
                ->delete();

            $isFullDay = $validated['is_full_day'] ?? true;

            $holiday->update([
                'date'        => $validated['date'],
                'title'       => $validated['title'],
                'is_full_day' => $isFullDay,
                'cutoff_time' => $isFullDay ? null : ($validated['cutoff_time'] ?? null),
                'session_id'  => $validated['session_id'] ?? null,
                'term_id'     => $validated['term_id'] ?? null,
            ]);

            if ($validated['affects_timetable'] ?? false) {
                $this->createHolidayOverrides($holiday);
            }

            DB::commit();
            return response()->json(['success' => true, 'holiday' => $holiday, 'message' => 'Holiday updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update holiday'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);

        TimetableOverride::where('override_type', 'holiday')
            ->where('override_date', $holiday->date)
            ->where('title', $holiday->title)
            ->delete();

        $holiday->delete();
        return response()->json(['success' => true, 'message' => 'Holiday deleted successfully']);
    }

    public function applyToTimetable(int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);

        try {
            $this->createHolidayOverrides($holiday);
            return response()->json(['success' => true, 'message' => 'Holiday applied to all timetables']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to apply holiday: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create/refresh a `timetable_overrides` row (holiday type) for every
     * active TimetableSetting whose active_days include this holiday's
     * weekday — scoped to the holiday's session/term when one is set, or
     * every session/term when it's left as "All Sessions"/"All Terms".
     */
    private function createHolidayOverrides(Holiday $holiday): void
    {
        $date      = Carbon::parse($holiday->date);
        $dayOfWeek = $date->format('l');

        $settings = TimetableSetting::where('is_active', true)
            ->when($holiday->session_id, fn($q) => $q->where('session_id', $holiday->session_id))
            ->when($holiday->term_id, fn($q) => $q->where('term_id', $holiday->term_id))
            ->get();

        foreach ($settings as $setting) {
            $activeDays = $setting->active_days ?? TimetableController::DAYS;
            if (!in_array($dayOfWeek, $activeDays)) {
                continue;
            }

            TimetableOverride::updateOrCreate(
                [
                    'setting_id'    => $setting->id,
                    'override_date' => $date->toDateString(),
                ],
                [
                    'override_type'       => 'holiday',
                    'title'               => $holiday->title,
                    'description'         => $holiday->is_full_day
                        ? null
                        : 'Half day — classes end by ' . ($holiday->cutoff_time ? Carbon::parse($holiday->cutoff_time)->format('H:i') : 'the scheduled cut-off time'),
                    'cancel_all_classes'  => (bool) $holiday->is_full_day,
                    'cancellation_reason' => $holiday->title,
                    'status'              => 'approved',
                    'created_by'          => Auth::id(),
                ]
            );
        }
    }
}
