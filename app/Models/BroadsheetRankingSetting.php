<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * UNOFFICIAL best-student ranking settings, one row per section (junior / senior).
 * Never affects the official position columns stored on broadsheets.
 */
class BroadsheetRankingSetting extends Model
{
    protected $table = 'broadsheet_ranking_settings';

    protected $fillable = [
        'section', 'primary_measure', 'tiebreakers', 'min_subjects', 'min_average',
        'require_all_compulsory', 'exclude_failed', 'scope', 'top_n', 'subject_top_n',
        'show_rank_column', 'core_subject_ids',
    ];

    protected $casts = [
        'tiebreakers'            => 'array',
        'core_subject_ids'       => 'array',
        'min_subjects'           => 'integer',
        'min_average'            => 'float',
        'require_all_compulsory' => 'boolean',
        'exclude_failed'         => 'boolean',
        'top_n'                  => 'integer',
        'subject_top_n'          => 'integer',
        'show_rank_column'       => 'boolean',
    ];

    public const SECTIONS = ['junior', 'senior'];

    public const MEASURES = [
        'cum_ave'      => 'Cumulative average',
        'term_ave'     => 'Term average',
        'total_cum'    => 'Cumulative total (sum of subjects)',
        'total_term'   => 'Term total (sum of subjects)',
        'core_ave'     => 'Core-subjects average',
        'distinctions' => 'Number of distinctions (A / A1)',
        'lowest_score' => 'Weakest subject score (consistency)',
        'num_subjects' => 'Number of subjects scored',
    ];

    public const SCOPES = [
        'class' => 'Whole class (all arms together)',
        'arm'   => 'Each arm separately',
        'both'  => 'Both',
    ];

    public static function defaultFor(string $section): self
    {
        return new self([
            'section'                => $section,
            'primary_measure'        => 'cum_ave',
            'tiebreakers'            => ['distinctions', 'lowest_score'],
            'min_subjects'           => 0,   // no minimum until the school sets one
            'min_average'            => null,
            'require_all_compulsory' => false,
            'exclude_failed'         => false,
            'scope'                  => 'both',
            'top_n'                  => 3,
            'subject_top_n'          => 3,
            'show_rank_column'       => false,
            'core_subject_ids'       => [],
        ]);
    }

    public static function forSection(string $section): self
    {
        return static::where('section', $section)->first() ?? static::defaultFor($section);
    }
}
