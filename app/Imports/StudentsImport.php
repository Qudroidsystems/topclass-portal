<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Student;
use Illuminate\Support\Str;
use App\Models\Studentclass;
use App\Models\Studenthouse;
use App\Models\StudentStatus;
use App\Models\Studentpicture;
use App\Models\PromotionStatus;
use App\Models\StudentBatchModel;
use App\Models\ParentRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Studentpersonalityprofile;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;

class StudentsImport implements ToModel, WithProgressBar, WithStartRow, WithUpsertColumns, WithUpserts, WithValidation, WithChunkReading
{
    use Importable;

    public $id = 0;

    public $_sclassid = 0;

    public $_teremid = 0;

    public $_sessionid = 0;

    public $_batchid = 0;

    /**
     * Chunk size for reading large files.
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Parse Excel date with support for serial dates and multiple formats
     * Returns only the date part without time
     */
    private function parseExcelDate($rawDate)
    {
        // Handle empty values
        if (empty($rawDate) || $rawDate === 'N/A' || trim($rawDate) === '') {
            return null;
        }
        
        // Handle Excel serial dates (numbers)
        if (is_numeric($rawDate)) {
            $excelBaseDate = Carbon::create(1899, 12, 30); // Excel Windows base date
            $parsedDate = $excelBaseDate->addDays((int)$rawDate);
            
            // For students, allow dates back to 1940 (for very old teachers/staff, but adjust as needed)
            if ($parsedDate->isFuture()) {
                // If date is in future, subtract 100 years (common Excel issue with dates)
                $parsedDate = $parsedDate->subYears(100);
            }
            
            // Return only date part without time
            return $parsedDate->startOfDay();
        }
        
        // Handle string dates - be more flexible with parsing
        try {
            $parsedDate = Carbon::parse($rawDate);
            
            // If it's a datetime string like "2012-02-07 00:00:00", extract only the date part
            if (strpos($rawDate, ' ') !== false || strpos($rawDate, 'T') !== false) {
                // Extract date part from datetime string
                $datePart = explode(' ', $rawDate)[0];
                $datePart = explode('T', $datePart)[0];
                $parsedDate = Carbon::parse($datePart);
            }
            
            // Return only date part without time
            return $parsedDate->startOfDay();
        } catch (\Exception $e) {
            // Try common date formats more aggressively - prioritize DD/MM/YYYY formats first
            $formats = [
                'd/m/Y', 'd/m/y', // DD/MM/YYYY or DD/MM/YY
                'd-m-Y', 'd-m-y', // DD-MM-YYYY or DD-MM-YY
                'Y-m-d', 'y-m-d', // YYYY-MM-DD or YY-MM-DD
                'm/d/Y', 'm/d/y', // MM/DD/YYYY or MM/DD/YY
                'm-d-Y', 'm-d-y', // MM-DD-YYYY or MM-DD-YY
                'd.M.Y', 'd.M.y', // DD.MM.YYYY or DD.MM.YY
                'd/M/Y', 'M/d/Y', // DD/MMM/YYYY or MM/DD/YYYY
                'd F Y', 'M d, Y', // DD Month YYYY or Month DD, YYYY
                'Ymd', 'dmY'      // YYYYMMDD or DDMMYYYY
            ];
            
            foreach ($formats as $format) {
                try {
                    $parsedDate = Carbon::createFromFormat($format, $rawDate);
                    
                    // Validate the parsed date
                    if (!$parsedDate) {
                        continue;
                    }
                    
                    // If year is 2-digit, assume it's in 1900-1999 range
                    if ($parsedDate->year < 100) {
                        $parsedDate = $parsedDate->addYears(1900);
                    }
                    
                    // Validate reasonable date range for students (born after 1990)
                    if ($parsedDate->year < 1990 || $parsedDate->isFuture()) {
                        continue; // Skip unreasonable dates
                    }
                    
                    // Return only date part without time
                    return $parsedDate->startOfDay();
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // If all parsing fails, try to extract date components manually with different separators
            $patterns = [
                '/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})/', // DD/MM/YYYY or DD-MM-YYYY
                '/(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/',   // YYYY/MM/DD or YYYY-MM-DD
            ];
            
            foreach ($patterns as $pattern) {
                preg_match($pattern, $rawDate, $matches);
                if (count($matches) === 4) {
                    $part1 = (int)$matches[1];
                    $part2 = (int)$matches[2];
                    $part3 = (int)$matches[3];
                    
                    // Determine format (DD/MM/YYYY vs YYYY/MM/DD)
                    if ($part3 > 31) {
                        // YYYY/MM/DD format
                        $year = $part1;
                        $month = $part2;
                        $day = $part3;
                    } else if ($part1 > 31) {
                        // YYYY/MM/DD format (different pattern)
                        $year = $part1;
                        $month = $part2;
                        $day = $part3;
                    } else {
                        // Assume DD/MM/YYYY format (most common in your data)
                        $day = $part1;
                        $month = $part2;
                        $year = $part3;
                        
                        // Handle 2-digit years
                        if ($year < 100) {
                            $year += 2000; // For student data, assume 2000s
                        }
                    }
                    
                    // Validate date
                    if (checkdate($month, $day, $year)) {
                        $parsedDate = Carbon::create($year, $month, $day);
                        
                        // Validate reasonable range
                        if ($parsedDate->year >= 1990 && !$parsedDate->isFuture()) {
                            return $parsedDate->startOfDay();
                        }
                    }
                }
            }
            
            // Last attempt: try to extract any date-like pattern
            preg_match('/(\d{4}-\d{2}-\d{2})/', $rawDate, $matches);
            if (count($matches) === 2) {
                try {
                    $parsedDate = Carbon::parse($matches[1]);
                    if ($parsedDate->year >= 1990 && !$parsedDate->isFuture()) {
                        return $parsedDate->startOfDay();
                    }
                } catch (\Exception $e) {
                    // Continue to throw exception
                }
            }
            
            // If we get here, log the problematic date for debugging
            \Log::warning("Unable to parse date, using fallback: '{$rawDate}'");
            
            // Final fallback: use current date minus 11 years (typical student age)
            return Carbon::now()->subYears(11)->startOfYear();
        }
    }

    /**
     * Normalize gender with comprehensive mapping
     */
    private function normalizeGender($rawGender)
    {
        if ($rawGender === 'N/A' || empty(trim($rawGender))) {
            return 'N/A';
        }

        $cleanGender = strtolower(trim($rawGender));
        
        // Comprehensive gender mapping
        $genderMap = [
            // Male variations
            'male' => 'Male',
            'm' => 'Male',
            'm.' => 'Male',
            'boy' => 'Male',
            'masculine' => 'Male',
            '1' => 'Male',
            'male.' => 'Male',
            'm ' => 'Male',
            ' male' => 'Male',
            'male ' => 'Male',
            
            // Female variations  
            'female' => 'Female',
            'f' => 'Female',
            'f.' => 'Female',
            'girl' => 'Female',
            'feminine' => 'Female',
            '2' => 'Female',
            'female.' => 'Female',
            'f ' => 'Female',
            ' female' => 'Female',
            'female ' => 'Female',
        ];

        // Exact match
        if (isset($genderMap[$cleanGender])) {
            return $genderMap[$cleanGender];
        }

        // Remove any special characters and extra spaces
        $cleanGender = preg_replace('/[^a-z0-9]/', '', $cleanGender);
        
        // Check again after cleaning
        if (isset($genderMap[$cleanGender])) {
            return $genderMap[$cleanGender];
        }

        // Partial matching
        if (strpos($cleanGender, 'male') !== false || $cleanGender === 'm' || strpos($cleanGender, 'boy') !== false) {
            return 'Male';
        }
        
        if (strpos($cleanGender, 'female') !== false || $cleanGender === 'f' || strpos($cleanGender, 'girl') !== false) {
            return 'Female';
        }

        // First character matching
        if (strpos($cleanGender, 'm') === 0) {
            return 'Male';
        }
        
        if (strpos($cleanGender, 'f') === 0) {
            return 'Female';
        }

        // If still not determined, log for debugging and return N/A
        \Log::warning("Unable to normalize gender value: '{$rawGender}' (cleaned: '{$cleanGender}')");
        return 'N/A';
    }

    /**
     * Handle a single row of the Excel file and map it to models.
     */
    public function model(array $row)
    {
        // Helper function to return "N/A" for null, empty, or whitespace-only values
        $naIfEmpty = function ($value) {
            return (is_null($value) || trim($value) === '') ? 'N/A' : trim($value);
        };

        // Retrieve session data with "N/A" fallback
        $schoolclassid = $naIfEmpty(Session::get('sclassid'));
        $termid = $naIfEmpty(Session::get('tid'));
        $sessionid = $naIfEmpty(Session::get('sid'));
        $batchid = $naIfEmpty(Session::get('batchid'));

        // CORRECT COLUMN MAPPING based on your Excel file:
        $admissionno = $naIfEmpty($row[0] ?? null);  // A: Admission No
        $surname = $naIfEmpty($row[1] ?? null);      // B: Surname
        $firstname = $naIfEmpty($row[2] ?? null);    // C: First Name
        $othername = $naIfEmpty($row[3] ?? null);    // D: Other Names
        $rawGender = $naIfEmpty($row[4] ?? null);    // E: Gender (this exists!)
        $homeAddress = $naIfEmpty($row[5] ?? null);  // F: Home Address
        $rawDob = $naIfEmpty($row[6] ?? null);       // G: DOB
        $rawAge = $naIfEmpty($row[7] ?? null);       // H: Age
        $placeofbirth = $naIfEmpty($row[8] ?? null); // I: Place of Birth
        $nationality = $naIfEmpty($row[9] ?? null);  // J: Nationality
        $state = $naIfEmpty($row[10] ?? null);       // K: State of Origin
        $local = $naIfEmpty($row[11] ?? null);       // L: L.G.A
        $religion = $naIfEmpty($row[12] ?? null);    // M: Religion
        $lastschool = $naIfEmpty($row[13] ?? null);  // N: Last Sch. Attended
        $lastclass = $naIfEmpty($row[14] ?? null);   // O: Last Class
        // Columns P, Q, R are schoolclassid, termid, sessionid (constants)

        // Normalize gender
        $gender = $this->normalizeGender($rawGender);

        // Parse date of birth
        $parsedDob = null;
        $age = null;
        
        try {
            if ($rawDob !== 'N/A') {
                $parsedDob = $this->parseExcelDate($rawDob);
            }
        } catch (\Exception $e) {
            \Log::warning("DOB parsing failed for '{$rawDob}', using age to estimate");
        }

        // If DOB parsing failed, use age to estimate
        if (!$parsedDob && $rawAge !== 'N/A' && is_numeric($rawAge)) {
            $age = (int)$rawAge;
            $estimatedBirthYear = Carbon::now()->subYears($age)->year;
            $parsedDob = Carbon::create($estimatedBirthYear, 1, 1)->startOfDay();
        }

        // Set final age
        if ($rawAge === 'N/A' || !is_numeric($rawAge)) {
            $age = $parsedDob ? $parsedDob->diffInYears(Carbon::now()) : null;
        } else {
            $age = (int)$rawAge;
        }

        // Parent data columns
        $father_title = $naIfEmpty(Str::limit($row[18] ?? '', 3, '')); // S: Father Name
        $father = $naIfEmpty(Str::substr($row[18] ?? '', 3));          // S: Father Name
        $father_phone = $naIfEmpty($row[19] ?? null);                  // T: Father Phone
        $father_occupation = $naIfEmpty($row[20] ?? null);             // U: Father Occupation
        $office_address = $naIfEmpty($row[21] ?? null);                // V: Office Address
        $mother_title = $naIfEmpty(Str::limit($row[22] ?? '', 3, '')); // W: Mother Name
        $mother = $naIfEmpty(Str::substr($row[22] ?? '', 3));          // W: Mother Name
        $mother_phone = $naIfEmpty($row[23] ?? null);                  // X: Mother Phone
        $mother_occupation = $naIfEmpty($row[24] ?? null);             // Y: Mother Occupation
        $mother_office_address = $naIfEmpty($row[25] ?? null);         // Z: Office Address
        $parent_address = $naIfEmpty($row[26] ?? null);                // AA: Parent Home Address
        $parent_religion = $naIfEmpty($row[27] ?? null);               // AB: Religion

        // Validate required fields
        if (in_array($admissionno, ['N/A', ''], true) || in_array($surname, ['N/A', ''], true) || in_array($firstname, ['N/A', ''], true)) {
            throw new \Exception("Required fields (admissionno, surname, firstname) cannot be empty or 'N/A' in row " . ($this->startRow() + $this->id));
        }

        // Validate session-based fields
        if (in_array($schoolclassid, ['N/A', ''], true) || in_array($termid, ['N/A', ''], true) || in_array($sessionid, ['N/A', ''], true) || in_array($batchid, ['N/A', ''], true)) {
            throw new \Exception("Session data (schoolclassid, termid, sessionid, batchid) cannot be empty or 'N/A' in row " . ($this->startRow() + $this->id));
        }

        // Debug logging for first few rows
       // Debug logging for first few rows
        if ($this->id < 5) {
            \Log::info("Import Debug - Row {$this->id}:", [
                'admissionno' => $admissionno,
                'firstname' => $firstname,
                'surname' => $surname,
                'raw_gender' => $rawGender,
                'raw_gender_length' => strlen($rawGender),
                'raw_gender_chars' => array_map('ord', str_split($rawGender)),
                'final_gender' => $gender,
                'raw_dob' => $rawDob,
                'raw_age' => $rawAge,
                'parsed_dob' => $parsedDob ? $parsedDob->format('Y-m-d') : 'null',
                'final_age' => $age
            ]);
        }

        // Initialize models
        $studentbiodata = new Student();
        $studentclass = new Studentclass();
        $promotion = new PromotionStatus();
        $parent = new ParentRegistration();
        $studenthouse = new Studenthouse();
        $picture = new Studentpicture();
        $studentpersonalityprofile = new Studentpersonalityprofile();
        $studentStatus = StudentStatus::where('status', 'old')->first();

        // Use transaction to ensure data consistency
        return \DB::transaction(function () use (
            $studentbiodata, $studentclass, $promotion, $parent, $studenthouse, $picture, $studentpersonalityprofile, $studentStatus,
            $admissionno, $surname, $firstname, $othername, $gender, $homeAddress, $parsedDob, $age, $placeofbirth, $nationality, $state, $local, $religion, $lastschool, $lastclass,
            $father_title, $father, $father_phone, $office_address, $father_occupation, $mother_title, $mother, $mother_phone, $mother_occupation, $mother_office_address, $parent_address, $parent_religion,
            $schoolclassid, $termid, $sessionid, $batchid
        ) {
            // Populate student biodata
            $studentbiodata->admissionNo = $admissionno;
            $studentbiodata->title = 'N/A';
            $studentbiodata->firstname = $firstname;
            $studentbiodata->lastname = $surname;
            $studentbiodata->othername = $othername;
            $studentbiodata->gender = $gender;
            $studentbiodata->future_ambition = 'N/A';
           
            $studentbiodata->home_address2 = $homeAddress;
            $studentbiodata->dateofbirth = $parsedDob;
            $studentbiodata->age = $age;
            $studentbiodata->placeofbirth = $placeofbirth;
            $studentbiodata->religion = $religion;
            $studentbiodata->nationality = $nationality;
            $studentbiodata->state = $state;
            $studentbiodata->local = $local;
            $studentbiodata->last_school = $lastschool;
            $studentbiodata->last_class = $lastclass;
            $studentbiodata->registeredBy = Auth::user()->id ?? 'N/A';
            $studentbiodata->batchid = $batchid;
            $studentbiodata->statusId = $studentStatus ? $studentStatus->id : 'N/A';
            $studentbiodata->save();
            $studentId = $studentbiodata->id;

            // Populate parent data
            $parent->studentId = $studentId;
            $parent->father_title = $father_title;
            $parent->father = $father;
            $parent->father_phone = $father_phone;
            $parent->office_address = $office_address;
            $parent->father_occupation = $father_occupation;
            $parent->mother_title = $mother_title;
            $parent->mother = $mother;
            $parent->mother_phone = $mother_phone;
            $parent->mother_occupation = $mother_occupation;
            $parent->mother_office_address = $mother_office_address;
            $parent->parent_address = $parent_address;
            $parent->religion = $parent_religion;
            $parent->save();

            // Populate student picture
            $picture->studentid = $studentId;
            $picture->picture = 'unnamed.jpg';
            $picture->save();

            // Populate student class
            $studentclass->studentId = $studentId;
            $studentclass->schoolclassid = $schoolclassid;
            $studentclass->termid = $termid;
            $studentclass->sessionid = $sessionid;
            $studentclass->save();

            // Populate promotion status
            $promotion->studentId = $studentId;
            $promotion->schoolclassid = $schoolclassid;
            $promotion->termid = $termid;
            $promotion->sessionid = $sessionid;
            $promotion->promotionStatus = 'PROMOTED';
            $promotion->classstatus = 'CURRENT';
            $promotion->save();

            // Populate student house
            $studenthouse->studentid = $studentId;
            $studenthouse->termid = $termid;
            $studenthouse->sessionid = $sessionid;
            $studenthouse->schoolhouse = null;
            $studenthouse->save();

            // Populate student personality profile
            $studentpersonalityprofile->studentid = $studentId;
            $studentpersonalityprofile->schoolclassid = $schoolclassid;
            $studentpersonalityprofile->termid = $termid;
            $studentpersonalityprofile->sessionid = $sessionid;
            $studentpersonalityprofile->save();

            $this->id++; // Increment row counter

            return $studentbiodata;
        });
    }

    /**
     * Validation rules for the Excel import.
     * Note: Column indices are 1-based (A=1, B=2, etc.).
     */
    public function rules(): array
    {
        $this->_sclassid = Session::get('sclassid') ?? 'N/A';
        $this->_termid = Session::get('tid') ?? 'N/A';
        $this->_sessionid = Session::get('sid') ?? 'N/A';
        $this->_batchid = Session::get('batchid') ?? 'N/A';

        return [
            '*.1' => 'required|string|max:255', // admissionno (column A)
            '*.2' => 'required|string|max:255', // surname (column B)
            '*.3' => 'required|string|max:255', // firstname (column C)
            // '*.5' => [ // gender (column E)
            //     function ($attribute, $value, $onFailure) {
            //         if ($value === 'N/A' || trim($value ?? '') === '') {
            //             return; // Allow N/A or empty for gender
            //         }
            //         $normalized = ucfirst(strtolower(trim($value)));
            //         if (!in_array($normalized, ['Male', 'Female'])) {
            //             $onFailure('Gender must be Male or Female.');
            //         }
            //     }
            // ],
            '*.7' => [ // dateofbirth (column G)
                function ($attribute, $value, $onFailure) {
                    if ($value === 'N/A' || trim($value ?? '') === '') {
                        return; // Allow N/A or empty for DOB
                    }
                    
                    try {
                        if (is_numeric($value)) {
                            return; // Excel serial number
                        } else {
                            Carbon::parse($value);
                        }
                    } catch (\Exception $e) {
                        $onFailure('Date of birth must be in a recognizable date format.');
                    }
                }
            ],
            '*.8' => [ // age (column H)
                function ($attribute, $value, $onFailure) {
                    if ($value === 'N/A' || trim($value ?? '') === '') {
                        return; // Allow N/A or empty for age
                    }
                    if (!is_numeric($value) || (int)$value < 1 || (int)$value > 100) {
                        $onFailure('Age must be a number between 1 and 100.');
                    }
                }
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function customValidationMessages()
    {
        return [
            '*.1.required' => 'Admission number is required.',
            '*.2.required' => 'Surname is required.',
            '*.3.required' => 'First name is required.',
        ];
    }

    /**
     * Custom validation attribute names.
     */
    public function customValidationAttributes()
    {
        return [
            '*.1' => 'admissionno',
            '*.2' => 'surname', 
            '*.3' => 'firstname',
            '*.5' => 'gender',
            '*.7' => 'dateofbirth',
            '*.8' => 'age',
        ];
    }

    /**
     * Start reading from row 2 (skip header).
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Unique identifier for upserts.
     */
    public function uniqueBy()
    {
        return ['admissionNo'];
    }

    /**
     * Columns to update during upserts.
     */
    public function upsertColumns()
    {
        return [
            'title',
            'firstname',
            'lastname',
            'othername',
            'gender',
            'future_ambition',
            // 'home_address',
            'home_address2',
            'dateofbirth',
            'age',
            'placeofbirth',
            'religion',
            'nationality',
            'state',
            'local',
            'last_school',
            'last_class',
            'registeredBy',
            'batchid',
            'statusId',
        ];
    }

    /**
     * Handle validation failures.
     */
    public function onFailure(Failure ...$failures)
    {
        StudentBatchModel::where('id', $this->_batchid)->update(['status' => 'Failed']);
        foreach ($failures as $failure) {
            \Log::error('Excel Import Failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    /**
     * Handle exceptions during import.
     */
    public function onError(\Throwable $e)
    {
        StudentBatchModel::where('id', $this->_batchid)->update(['status' => 'Failed']);
        \Log::error('Excel Import Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    }
}