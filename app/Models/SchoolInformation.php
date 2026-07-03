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
        'school_email',
        'school_logo',
        'app_logo', // New field for app logo
        'school_motto',
        'school_website',
        'no_of_times_school_opened',
        'date_school_opened',
        'date_next_term_begins',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_school_opened' => 'date',
        'date_next_term_begins' => 'date',
    ];

    /**
     * Get the active school information
     */
    public static function getActiveSchool()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Get the school logo URL
     */
    public function getLogoUrlAttribute()
    {
        if (!$this->school_logo) {
            return null;
        }

        // Check if it's a full URL (from external source)
        if (filter_var($this->school_logo, FILTER_VALIDATE_URL)) {
            return $this->school_logo;
        }

        // Check if file exists in storage
        if (Storage::disk('public')->exists($this->school_logo)) {
            return asset('storage/' . $this->school_logo);
        }

        return null;
    }

    /**
     * Get the app logo URL
     */
    public function getAppLogoUrlAttribute()
    {
        if (!$this->app_logo) {
            return null;
        }

        // Check if it's a full URL (from external source)
        if (filter_var($this->app_logo, FILTER_VALIDATE_URL)) {
            return $this->app_logo;
        }

        // Check if file exists in storage
        if (Storage::disk('public')->exists($this->app_logo)) {
            return asset('storage/' . $this->app_logo);
        }

        return null;
    }

    /**
     * Get logo with fallback
     */
    public function getLogoWithFallbackAttribute()
    {
        return $this->getLogoUrlAttribute() ?? asset('theme/layouts/assets/images/logo-dark.png');
    }

    /**
     * Get app logo with fallback
     */
    public function getAppLogoWithFallbackAttribute()
    {
        return $this->getAppLogoUrlAttribute() ?? $this->getLogoWithFallbackAttribute();
    }

    /**
     * Delete old logo when updating
     */
    public function deleteOldLogo()
    {
        if ($this->getOriginal('school_logo') && $this->getOriginal('school_logo') !== $this->school_logo) {
            Storage::disk('public')->delete($this->getOriginal('school_logo'));
        }
        if ($this->getOriginal('app_logo') && $this->getOriginal('app_logo') !== $this->app_logo) {
            Storage::disk('public')->delete($this->getOriginal('app_logo'));
        }
    }
}

