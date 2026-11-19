<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

/** A graduating set's yearbook (see YearbookAdminController / YearbookController). */
class Yearbook extends Model
{
    protected $table = 'yearbooks';

    protected $fillable = [
        'title', 'slug', 'graduation_year', 'session_id', 'class_label', 'theme', 'description',
        'principal_message', 'principal_name', 'principal_photo', 'cover', 'class_photo', 'album_id',
        'visibility', 'allow_submissions', 'submissions_close_on', 'published', 'sort', 'created_by',
    ];

    protected $casts = [
        'published' => 'boolean', 'allow_submissions' => 'boolean', 'submissions_close_on' => 'date',
        'graduation_year' => 'integer',
    ];

    public function entries()
    {
        return $this->hasMany(YearbookEntry::class, 'yearbook_id')->orderBy('sort')->orderBy('name');
    }

    public function publishedEntries()
    {
        return $this->entries()->where('published', true);
    }

    public function album()
    {
        return $this->belongsTo(WebsiteAlbum::class, 'album_id');
    }

    public function isPortalOnly(): bool
    {
        return $this->visibility === 'portal';
    }

    /** Can this visitor see it? Portal-only yearbooks need a signed-in portal user. */
    public function visibleTo($user): bool
    {
        return $this->published && (!$this->isPortalOnly() || $user !== null);
    }

    /** Are students / parents still allowed to send photos and details through their links? */
    public function acceptingSubmissions(): bool
    {
        return $this->allow_submissions && (!$this->submissions_close_on || $this->submissions_close_on->endOfDay()->isFuture());
    }

    /** Yearbooks the visitor may see. */
    public function scopeVisible($q, $user = null)
    {
        $q->where('published', true);
        if (!$user) $q->where('visibility', 'public');
        return $q;
    }
}
