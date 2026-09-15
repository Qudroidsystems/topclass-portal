<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\RoomClassSubject;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
{
    const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public function index()
    {
        $pagetitle = 'Room Management';
        $rooms = Room::orderBy('room_name')->paginate(15);
        $roomTypes = ['classroom', 'laboratory', 'auditorium', 'library', 'sports', 'other'];

        return view('rooms.index', compact('pagetitle', 'rooms', 'roomTypes'));
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'room_code' => 'required|string|max:50|unique:rooms,room_code',
                'room_name' => 'required|string|max:100',
                'type' => 'required|in:classroom,laboratory,auditorium,library,sports,other',
                'capacity' => 'required|integer|min:1',
                'facilities' => 'nullable|array',
                'building' => 'nullable|string|max:100',
                'floor' => 'nullable|string|max:50',
                'is_active' => 'sometimes|boolean',
                'notes' => 'nullable|string',
            ]);

            $validated['is_active'] = $request->input('is_active', true);

            $room = Room::create($validated);

            return response()->json([
                'success' => true,
                'room' => $room,
                'message' => 'Room created successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Room creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create room: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $room = Room::findOrFail($id);

            $currentWeekSlots = TimetableSlot::where('room_id', $id)
                ->with(['period', 'subject', 'teacher'])
                ->get()
                ->map(function($slot) {
                    return [
                        'day' => $slot->day,
                        'period' => $slot->period ? [
                            'name' => $slot->period->name,
                            'start_time' => $slot->period->start_time,
                            'end_time' => $slot->period->end_time
                        ] : null,
                        'subject' => $slot->subject ? ['subject' => $slot->subject->subject_name] : null,
                        'teacher' => $slot->teacher ? ['name' => $slot->teacher->name] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'room' => $room,
                'current_bookings' => $currentWeekSlots
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found'
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $room = Room::findOrFail($id);

            $validated = $request->validate([
                'room_code' => 'required|string|max:50|unique:rooms,room_code,' . $id,
                'room_name' => 'required|string|max:100',
                'type' => 'required|in:classroom,laboratory,auditorium,library,sports,other',
                'capacity' => 'required|integer|min:1',
                'facilities' => 'nullable|array',
                'building' => 'nullable|string|max:100',
                'floor' => 'nullable|string|max:50',
                'is_active' => 'sometimes|boolean',
                'notes' => 'nullable|string',
            ]);

            $room->update($validated);

            return response()->json([
                'success' => true,
                'room' => $room,
                'message' => 'Room updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update room: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $room = Room::findOrFail($id);

            $isUsed = TimetableSlot::where('room_id', $id)->exists();
            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete room that is currently in use in a timetable'
                ], 422);
            }

            $hasFutureBookings = RoomBooking::where('room_id', $id)
                ->where('date', '>=', now()->toDateString())
                ->exists();

            if ($hasFutureBookings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete room with upcoming bookings'
                ], 422);
            }

            // Clean up any mappings — cascading FKs on the pivot would
            // handle this automatically, but explicit is safer if the
            // schema changes.
            RoomClassSubject::where('room_id', $id)->delete();

            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room'
            ], 500);
        }
    }

    public function book(Request $request, $roomId = null): JsonResponse
    {
        try {
            $roomId = $roomId ?? $request->input('room_id');

            $validated = $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'purpose' => 'required|string|max:500',
                'recurring_type' => 'sometimes|in:none,weekly,biweekly',
            ]);

            $room = Room::find($roomId);
            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found'
                ], 404);
            }

            $isAvailable = $this->checkRoomAvailability(
                $roomId,
                $validated['date'],
                $validated['start_time'],
                $validated['end_time']
            );

            if (!$isAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room is not available at this time'
                ], 422);
            }

            $booking = RoomBooking::create([
                'room_id' => $roomId,
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'purpose' => $validated['purpose'],
                'recurring_type' => $validated['recurring_type'] ?? 'none',
                'booked_by' => Auth::id(),
                'status' => 'confirmed'
            ]);

            return response()->json([
                'success' => true,
                'booking' => $booking,
                'message' => 'Room booked successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Booking failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to book room: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelBooking($bookingId): JsonResponse
    {
        try {
            $booking = RoomBooking::findOrFail($bookingId);

            if ($booking->booked_by !== Auth::id() && !Auth::user()->can('Manage room bookings')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if ($booking->date < now()->toDateString()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a past booking'
                ], 422);
            }

            $booking->delete();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking'
            ], 500);
        }
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'room_id' => 'required|exists:rooms,id',
                'date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
            ]);

            $isAvailable = $this->checkRoomAvailability(
                $validated['room_id'],
                $validated['date'],
                $validated['start_time'],
                $validated['end_time']
            );

            return response()->json([
                'success' => true,
                'available' => $isAvailable
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => 'Failed to check availability'
            ], 500);
        }
    }

    private function checkRoomAvailability($roomId, $date, $startTime, $endTime): bool
    {
        $dayOfWeek = date('l', strtotime($date));

        $timetableConflict = TimetableSlot::where('room_id', $roomId)
            ->where('day', $dayOfWeek)
            ->whereHas('period', function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($timetableConflict) return false;

        $bookingConflict = RoomBooking::where('room_id', $roomId)
            ->where('date', $date)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        return !$bookingConflict;
    }

    // =========================================================================
    // ROOM-CLASS-SUBJECT MAPPINGS
    // =========================================================================

    public function mappings(int $roomId): JsonResponse
    {
        try {
            Room::findOrFail($roomId);

            $rows = RoomClassSubject::with(['schoolclass', 'subject', 'session', 'term'])
                ->where('room_id', $roomId)
                ->orderBy('schoolclass_id')
                ->orderBy('subject_id')
                ->get()
                ->map(fn($m) => [
                    'id'              => $m->id,
                    'schoolclass_id'  => $m->schoolclass_id,
                    'class_name'      => trim(($m->schoolclass?->schoolclass ?? '')
                                       . ' ' . ($m->schoolclass?->arm ?? '')),
                    'subject_id'      => $m->subject_id,
                    'subject_name'    => $m->subject?->subject,
                    'session_id'      => $m->session_id,
                    'session_name'    => $m->session?->session,
                    'term_id'         => $m->term_id,
                    'term_name'       => $m->term?->term,
                    'note'            => $m->note,
                ]);

            return response()->json(['success' => true, 'mappings' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeMapping(Request $request, int $roomId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'schoolclass_id' => 'required|exists:schoolclass,id',
                'subject_id'     => 'nullable|exists:subject,id',
                'session_id'     => 'required|exists:schoolsession,id',
                'term_id'        => 'nullable|exists:schoolterm,id',
                'note'           => 'nullable|string|max:190',
            ]);

            $validated['room_id'] = $roomId;

            $exists = RoomClassSubject::where('room_id', $roomId)
                ->where('schoolclass_id', $validated['schoolclass_id'])
                ->where('subject_id', $validated['subject_id'] ?? null)
                ->where('session_id', $validated['session_id'])
                ->where('term_id', $validated['term_id'] ?? null)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'That room is already mapped to this class/subject for the same session and term.',
                ], 422);
            }

            $row = RoomClassSubject::create($validated);

            return response()->json(['success' => true, 'mapping' => $row]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyMapping(int $mappingId): JsonResponse
    {
        try {
            RoomClassSubject::findOrFail($mappingId)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function mappingCounts(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');

        $q = RoomClassSubject::query();
        if ($sessionId) $q->where('session_id', $sessionId);

        $counts = $q->select('room_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('room_id')
            ->pluck('cnt', 'room_id');

        return response()->json(['success' => true, 'counts' => $counts]);
    }

        // =========================================================================
    // PER-ROOM STATS DETAIL
    //
    // Returns the full lists behind the three counters shown on the rooms
    // page: upcoming bookings, mapped classes/subjects, and timetable uses.
    // The frontend caps each list at 10 for display; we cap at 50 here so
    // the "Show all N" link can reveal more without a second fetch.
    // =========================================================================
    public function statsDetail(int $roomId): JsonResponse
    {
        try {
            $room = Room::findOrFail($roomId);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Room not found.'], 404);
        }

        // ── Upcoming bookings ──────────────────────────────────────────
        $bookings = RoomBooking::where('room_id', $roomId)
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'confirmed')
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(50)
            ->get()
            ->map(fn($b) => [
                'id'         => $b->id,
                'date'       => \Carbon\Carbon::parse($b->date)->format('d M Y'),
                'date_h'     => \Carbon\Carbon::parse($b->date)->diffForHumans(),
                'start_time' => $this->formatTimeOnly($b->start_time),
                'end_time'   => $this->formatTimeOnly($b->end_time),
                'purpose'    => $b->purpose,
                'recurring'  => $b->recurring_type ?? 'none',
            ]);

        // ── Mapped classes / subjects ──────────────────────────────────
        $mappings = RoomClassSubject::with(['schoolclass', 'subject', 'session', 'term'])
            ->where('room_id', $roomId)
            ->orderBy('schoolclass_id')
            ->orderBy('subject_id')
            ->limit(50)
            ->get()
            ->map(function ($m) {
                $className = trim(
                    ($m->schoolclass?->schoolclass ?? '') . ' ' .
                    ($m->schoolclass?->arm ?? '')
                );
                return [
                    'id'           => $m->id,
                    'class_name'   => $className ?: 'Unknown Class',
                    'subject_name' => $m->subject?->subject,   // null = generic
                    'session_name' => $m->session?->session,
                    'term_name'    => $m->term?->term,
                    'note'         => $m->note,
                ];
            });

        // ── Timetable uses ─────────────────────────────────────────────
        // Slot → setting → class. Group by setting so the payload is
        // compact, then flatten for the client.
        $slots = TimetableSlot::where('room_id', $roomId)
            ->with(['subject', 'teacher', 'period', 'setting.schoolclass', 'setting.session', 'setting.term'])
            ->orderBy('day')
            ->orderBy('period_id')
            ->limit(50)
            ->get()
            ->map(function ($s) {
                $className = trim(
                    ($s->setting?->schoolclass?->schoolclass ?? '') . ' ' .
                    ($s->setting?->schoolclass?->arm ?? '')
                );
                return [
                    'id'           => $s->id,
                    'day'          => $s->day,
                    'period_name'  => $s->period?->name,
                    'period_time'  => $this->formatTimeOnly($s->period?->start_time)
                                    . ' – '
                                    . $this->formatTimeOnly($s->period?->end_time),
                    'subject_name' => $s->subject?->subject,
                    'teacher_name' => $s->teacher?->name,
                    'class_name'   => $className ?: 'Unknown Class',
                    'session_name' => $s->setting?->session?->session,
                    'term_name'    => $s->setting?->term?->term,
                ];
            });

        return response()->json([
            'success'  => true,
            'room'     => [
                'id'        => $room->id,
                'room_name' => $room->room_name,
                'room_code' => $room->room_code,
            ],
            'bookings' => $bookings,
            'mappings' => $mappings,
            'uses'     => $slots,
            'totals'   => [
                'bookings' => RoomBooking::where('room_id', $roomId)
                    ->where('date', '>=', now()->toDateString())
                    ->where('status', 'confirmed')
                    ->count(),
                'mappings' => RoomClassSubject::where('room_id', $roomId)->count(),
                'uses'     => TimetableSlot::where('room_id', $roomId)->count(),
            ],
        ]);
    }

    /**
     * Small helper: normalise a time value ("10:00:00" or Carbon) to "HH:MM".
     * Used by statsDetail only, so it lives here rather than the trait
     * helpers we keep elsewhere.
     */
    private function formatTimeOnly($time): string
    {
        if (!$time) return '';
        if ($time instanceof \Carbon\Carbon) return $time->format('H:i');
        return substr((string) $time, 0, 5);
    }
}