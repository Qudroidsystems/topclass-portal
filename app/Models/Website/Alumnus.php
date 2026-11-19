<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A member of the alumni network. */
class Alumnus extends Model
{
    protected $table = 'alumni';

    public const INDUSTRIES = [
        'Education', 'Health & Medicine', 'Engineering', 'Technology & ICT', 'Law', 'Banking & Finance',
        'Business & Entrepreneurship', 'Oil, Gas & Energy', 'Agriculture', 'Public Service & Government',
        'Military & Paramilitary', 'Media, Arts & Entertainment', 'Religion & Ministry', 'Sports',
        'Student (Higher Institution)', 'Other',
    ];

    /** Profile fields an alumnus can change through their private edit link. */
    public const EDITABLE = [
        'name', 'maiden_name', 'graduation_year', 'class_set', 'occupation', 'organisation', 'industry', 'location',
        'higher_institution', 'bio', 'message', 'email', 'phone', 'linkedin', 'show_contact', 'mentor',
    ];

    protected $fillable = [
        'yearbook_entry_id', 'student_id', 'name', 'maiden_name', 'graduation_year', 'class_set', 'photo',
        'occupation', 'organisation', 'industry', 'location', 'higher_institution', 'bio', 'message', 'email',
        'phone', 'linkedin', 'show_contact', 'mentor', 'status', 'featured', 'edit_token', 'pending',
        'pending_photo', 'pending_at', 'ip',
    ];

    protected $casts = [
        'show_contact' => 'boolean', 'mentor' => 'boolean', 'featured' => 'boolean', 'pending' => 'array',
        'pending_at' => 'datetime', 'graduation_year' => 'integer',
    ];

    public function entry()
    {
        return $this->belongsTo(YearbookEntry::class, 'yearbook_entry_id');
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    }

    /** Their photo, or their yearbook graduation photo. */
    public function displayPhoto(): ?string
    {
        return $this->photo ?: ($this->entry ? $this->entry->displayPhoto() : null);
    }

    public function headline(): string
    {
        return trim(implode(' at ', array_filter([$this->occupation, $this->organisation])));
    }

    public function editToken(): string
    {
        if (!$this->edit_token) {
            $this->edit_token = Str::random(40);
            $this->save();
        }
        return $this->edit_token;
    }

    public function editUrl(): string
    {
        return route('site.alumni.edit', $this->editToken());
    }
}
