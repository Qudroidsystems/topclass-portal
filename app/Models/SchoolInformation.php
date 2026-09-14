<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SchoolInformation extends Model
{
    use HasFactory;

    protected $table = 'school_information';

    protected $fillable = [
        'school_name',
        'school_address',
        'school_phone',
        'school_phones',
        'school_email',
        'school_logo',
        'app_logo',
        'school_stamp',
        'school_motto',
        'school_website',
        'no_of_times_school_opened',
        'date_school_opened',
        'date_school_closed',
        'date_next_term_begins',
        'is_active',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'date_school_opened'    => 'date',
        'date_school_closed'    => 'date',
        'date_next_term_begins' => 'date',
        'school_phones'         => 'array',
    ];

    public static function getActiveSchool()
    {
        return self::where('is_active', true)->first();
    }

    public function getLogoUrlAttribute()
    {
        return $this->fileUrl($this->school_logo);
    }

    public function getAppLogoUrlAttribute()
    {
        return $this->fileUrl($this->app_logo);
    }

    public function getStampUrlAttribute()
    {
        return $this->fileUrl($this->school_stamp);
    }

    public function getLogoWithFallbackAttribute()
    {
        return $this->getLogoUrlAttribute() ?? asset('theme/layouts/assets/images/logo-dark.png');
    }

    public function getAppLogoWithFallbackAttribute()
    {
        return $this->getAppLogoUrlAttribute() ?? $this->getLogoWithFallbackAttribute();
    }

    private function fileUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (filter_var($path, FILTER_VALIDATE_URL)) return $path;
        if (Storage::disk('public')->exists($path)) return asset('storage/' . $path);
        return null;
    }
}
