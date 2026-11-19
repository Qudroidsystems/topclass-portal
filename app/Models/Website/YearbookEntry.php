<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** One graduating student in a yearbook. */
class YearbookEntry extends Model
{
    protected $table = 'yearbook_entries';

    /** Profile fields a student / parent can fill through the upload link. */
    public const SUBMITTABLE = ['nickname', 'ambition', 'hobbies', 'favourite_subject', 'quote', 'best_memory', 'testimonial', 'next_step'];

    protected $fillable = [
        'yearbook_id', 'student_id', 'name', 'nickname', 'admission_no', 'gender', 'class_name', 'house',
        'position', 'awards', 'photo', 'portal_photo', 'ambition', 'hobbies', 'favourite_subject', 'quote',
        'best_memory', 'testimonial', 'school_remark', 'next_step', 'featured', 'published', 'sort',
        'upload_token', 'link_sent_at', 'pending', 'pending_photo', 'pending_at', 'pending_by',
    ];

    protected $casts = [
        'featured' => 'boolean', 'published' => 'boolean', 'pending' => 'array',
        'pending_at' => 'datetime', 'link_sent_at' => 'datetime',
    ];

    public function yearbook()
    {
        return $this->belongsTo(Yearbook::class, 'yearbook_id');
    }

    public function alumnus()
    {
        return $this->hasOne(Alumnus::class, 'yearbook_entry_id');
    }

    /** The graduation photo, or the portal passport until one is uploaded. */
    public function displayPhoto(): ?string
    {
        return $this->photo ?: $this->portal_photo;
    }

    public function hasPending(): bool
    {
        return $this->pending_at !== null;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    }

    /** @return string[] */
    public function awardList(): array
    {
        return collect(explode(',', (string) $this->awards))->map(fn ($a) => trim($a))->filter()->values()->all();
    }

    /** The private link a student or parent uses to send the photo and details. */
    public function uploadToken(): string
    {
        if (!$this->upload_token) {
            $this->upload_token = Str::random(40);
            $this->save();
        }
        return $this->upload_token;
    }

    public function uploadUrl(): string
    {
        return route('site.yearbook.upload', $this->uploadToken());
    }
}
