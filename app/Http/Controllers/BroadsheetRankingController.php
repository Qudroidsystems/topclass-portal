<?php
// app/Http/Controllers/BroadsheetRankingController.php

namespace App\Http\Controllers;

use App\Models\BroadsheetRankingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Settings for the UNOFFICIAL best-student ranking (junior / senior).
 * Official broadsheet positions are never touched by anything here.
 */
class BroadsheetRankingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View student-report');
    }

    public function index(): View
    {
        $sections = [];
        foreach (BroadsheetRankingSetting::SECTIONS as $section) {
            $sections[$section] = BroadsheetRankingSetting::forSection($section);
        }

        return view('broadsheet.ranking.index', [
            'pagetitle' => 'Broadsheet Ranking',
            'sections'  => $sections,
            'measures'  => BroadsheetRankingSetting::MEASURES,
            'scopes'    => BroadsheetRankingSetting::SCOPES,
            'subjects'  => DB::table('subject')->orderBy('subject')->get(['id', 'subject']),
        ]);
    }

    public function save(Request $request, string $section): RedirectResponse
    {
        abort_unless(in_array($section, BroadsheetRankingSetting::SECTIONS, true), 404);

        $measureKeys = implode(',', array_keys(BroadsheetRankingSetting::MEASURES));
        $scopeKeys   = implode(',', array_keys(BroadsheetRankingSetting::SCOPES));

        $v = $request->validate([
            'primary_measure'    => "required|in:{$measureKeys}",
            'tiebreakers'        => 'nullable|array|max:3',
            'tiebreakers.*'      => "nullable|in:{$measureKeys}",
            'min_subjects'       => 'nullable|integer|min:0|max:40',
            'min_average'        => 'nullable|numeric|min:0|max:100',
            'scope'              => "required|in:{$scopeKeys}",
            'top_n'              => 'required|integer|min:1|max:10',
            'subject_top_n'      => 'required|integer|min:1|max:10',
            'core_subject_ids'   => 'nullable|array',
            'core_subject_ids.*' => 'integer',
        ]);

        $tiebreakers = array_values(array_unique(array_filter(
            (array) ($v['tiebreakers'] ?? []),
            fn ($m) => $m && $m !== $v['primary_measure']
        )));

        if ($v['primary_measure'] === 'core_ave' && empty($v['core_subject_ids'])) {
            return back()->withErrors(['core_subject_ids' => 'Pick at least one core subject to rank by core-subjects average.'])->withInput();
        }

        BroadsheetRankingSetting::updateOrCreate(
            ['section' => $section],
            [
                'primary_measure'        => $v['primary_measure'],
                'tiebreakers'            => $tiebreakers,
                'min_subjects'           => (int) ($v['min_subjects'] ?? 0),
                'min_average'            => $v['min_average'] ?? null,
                'require_all_compulsory' => $request->boolean('require_all_compulsory'),
                'exclude_failed'         => $request->boolean('exclude_failed'),
                'scope'                  => $v['scope'],
                'top_n'                  => (int) $v['top_n'],
                'subject_top_n'          => (int) $v['subject_top_n'],
                'show_rank_column'       => $request->boolean('show_rank_column'),
                'core_subject_ids'       => array_map('intval', $v['core_subject_ids'] ?? []),
            ]
        );

        return back()->with('success', ucfirst($section) . ' ranking settings saved.');
    }
}
