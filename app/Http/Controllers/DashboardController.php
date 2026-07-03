<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard', ['only' => ['index']]);
    }

    public function index()
    {
        // Page title
        $pagetitle = "Dashboard Management";

        // Current month and previous month for filtering
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();

        // Total population (all students)
        $total_population = Student::count();
        $previous_population = Student::where('created_at', '<', $currentMonth)
            ->where('created_at', '>=', $previousMonth)
            ->count();
        $population_percentage = $previous_population > 0
            ? number_format((($total_population - $previous_population) / $previous_population) * 100, 2)
            : ($total_population > 0 ? 100.00 : 0.00);

        // Status counts
        $status_counts = Student::groupBy('statusId')
            ->selectRaw("CASE WHEN statusId = 1 THEN 'Old Student' ELSE 'New Student' END as student_status, COUNT(*) as student_count")
            ->pluck('student_count', 'student_status')
            ->toArray();
        $status_counts = [
            'Old Student' => $status_counts['Old Student'] ?? 0,
            'New Student' => $status_counts['New Student'] ?? 0
        ];

        // Gender counts
        $gender_counts = Student::groupBy('gender')
            ->selectRaw('gender, COUNT(*) as gender_count')
            ->pluck('gender_count', 'gender')
            ->toArray();
        $gender_counts = [
            'Male' => $gender_counts['Male'] ?? 0,
            'Female' => $gender_counts['Female'] ?? 0
        ];

        // Male percentage
        $previous_male = Student::where('gender', 'Male')
            ->where('created_at', '<', $currentMonth)
            ->where('created_at', '>=', $previousMonth)
            ->count();
        $male_percentage = $previous_male > 0
            ? number_format((($gender_counts['Male'] - $previous_male) / $previous_male) * 100, 2)
            : ($gender_counts['Male'] > 0 ? 100.00 : 0.00);

        // Female percentage
        $previous_female = Student::where('gender', 'Female')
            ->where('created_at', '<', $currentMonth)
            ->where('created_at', '>=', $previousMonth)
            ->count();
        $female_percentage = $previous_female > 0
            ? number_format((($gender_counts['Female'] - $previous_female) / $previous_female) * 100, 2)
            : ($gender_counts['Female'] > 0 ? 100.00 : 0.00);

        // Staff count (users with NULL student_id)
        $staff_count = User::whereNull('student_id')->count();
        $previous_staff = User::whereNull('student_id')
            ->where('created_at', '<', $currentMonth)
            ->where('created_at', '>=', $previousMonth)
            ->count();
        $staff_percentage = $previous_staff > 0
            ? number_format((($staff_count - $previous_staff) / $previous_staff) * 100, 2)
            : ($staff_count > 0 ? 100.00 : 0.00);

        return view('dashboards.dashboard', compact(
            'pagetitle',
            'total_population',
            'status_counts',
            'gender_counts',
            'staff_count',
            'population_percentage',
            'staff_percentage',
            'male_percentage',
            'female_percentage'
        ));
    }
}