{{-- resources/views/broadsheet/partials/ranking.blade.php
     UNOFFICIAL best-student panel. Uses the cb- variables and .pos-badge
     classes already defined in broadsheet/web.blade.php. --}}
@php
    $rk         = $ranking;
    $showOverall = ($rk['scope'] ?? 'both') !== 'arm';
    $showArms    = ($rk['scope'] ?? 'both') !== 'class' && count($rk['by_arm'] ?? []) > 0
                   && (($rk['scope'] ?? 'both') === 'arm' || count($rk['by_arm']) > 1);
    $fmt = fn ($v) => is_numeric($v) ? rtrim(rtrim(number_format((float) $v, 2), '0'), '.') : '—';
    $badge = fn ($r) => $r <= 3 ? 'pos-' . $r : 'pos-other';
    $basisLabel = ($rk['basis'] ?? 'cum') === 'total' ? 'term total' : 'cumulative';
@endphp

<style>
.rk-panel { background:var(--cb-white); border:1px solid var(--cb-border); border-left:4px solid var(--cb-amber); border-radius:var(--cb-radius); box-shadow:var(--cb-shadow); margin-bottom:24px; }
.rk-head { padding:16px 22px; border-bottom:1px solid var(--cb-border); display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; }
.rk-head h5 { margin:0; font-size:15px; font-weight:700; color:var(--cb-navy); }
.rk-head .rk-sub { font-size:12px; color:var(--cb-muted); margin-top:2px; }
.rk-body { padding:18px 22px; }
.rk-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; }
.rk-block h6 { font-size:12.5px; font-weight:700; color:var(--cb-navy); margin:0 0 8px; }
.rk-row { display:flex; align-items:center; gap:10px; padding:7px 0; border-bottom:1px dashed var(--cb-border); }
.rk-row:last-child { border-bottom:none; }
.rk-row .pos-badge { width:28px; height:28px; font-size:11px; }
.rk-name { font-weight:700; font-size:12.5px; color:var(--cb-navy); line-height:1.25; }
.rk-meta { font-size:10.5px; color:var(--cb-muted); }
.rk-val { margin-left:auto; font-weight:800; font-size:13px; color:var(--cb-navy); white-space:nowrap; }
.rk-subj { width:100%; border-collapse:collapse; font-size:12px; }
.rk-subj th { background:var(--cb-surface); color:var(--cb-navy); font-weight:700; padding:7px 10px; border-bottom:1px solid var(--cb-border); text-align:left; }
.rk-subj td { padding:7px 10px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.rk-chip { display:inline-flex; align-items:center; gap:5px; margin:2px 6px 2px 0; white-space:nowrap; }
.rk-chip b { font-size:10px; color:#92400e; background:#fef3c7; border-radius:4px; padding:1px 5px; }
.rk-toggle { background:none; border:1px solid var(--cb-border); border-radius:8px; padding:5px 12px; font-size:12px; font-weight:600; color:var(--cb-navy); cursor:pointer; }
.rk-toggle:focus-visible, .rk-select:focus-visible { outline:2px solid var(--cb-teal); outline-offset:2px; }
.rk-select { border:1.5px solid var(--cb-border); border-radius:8px; font-size:12px; padding:5px 10px; }
@media print { .rk-panel .no-print { display:none !important; } .rk-collapse { display:block !important; } }
</style>

<div class="rk-panel">
    <div class="rk-head">
        <div>
            <h5><i class="ri-trophy-line me-1" style="color:var(--cb-amber)"></i>Best students</h5>
            <div class="rk-sub">
                Ranked by {{ strtolower($rk['measure_label']) }}
                @if(!empty($rk['tiebreakers'])), ties broken by {{ strtolower(implode(', then ', $rk['tiebreakers'])) }}@endif.
                Subject toppers use {{ $basisLabel }} scores. Unofficial — report-card positions are unchanged.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 no-print">
            <label for="rkRankBy" class="small text-muted mb-0">Rank by</label>
            <select id="rkRankBy" class="rk-select"
                    onchange="document.getElementById('rb_input').value=this.value;document.getElementById('gradeBasisForm').submit();">
                @foreach(\App\Models\BroadsheetRankingSetting::MEASURES as $k => $lbl)
                    <option value="{{ $k }}" @selected($rk['measure'] === $k)>{{ $lbl }}</option>
                @endforeach
            </select>
            @if(Route::has('broadsheet.ranking.index'))
                <a href="{{ route('broadsheet.ranking.index') }}" class="rk-toggle text-decoration-none" title="Ranking settings">
                    <i class="ri-settings-3-line"></i>
                </a>
            @endif
        </div>
    </div>

    <div class="rk-body">
        @if(empty($rk['overall']) && empty($rk['by_arm']))
            <p class="text-muted small mb-0">No student meets the ranking rules yet. Check the minimum subjects and failed-subject settings, or enter more scores.</p>
        @else
            <div class="rk-grid">
                @if($showOverall)
                    <div class="rk-block">
                        <h6>{{ !empty($is_combined) ? 'Across all arms' : 'This class' }} — top {{ $rk['top_n'] }}</h6>
                        @foreach($rk['overall'] as $e)
                            <div class="rk-row">
                                <span class="pos-badge {{ $badge($e['rank']) }}">{{ $e['rank'] }}</span>
                                <div>
                                    <div class="rk-name">{{ $e['name'] }}</div>
                                    <div class="rk-meta">{{ $e['admissionno'] }} @if(!empty($is_combined))· {{ $e['arm'] }}@endif</div>
                                </div>
                                <span class="rk-val">{{ $fmt($e['value']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($showArms)
                    @foreach($rk['by_arm'] as $armLabel => $entries)
                        <div class="rk-block">
                            <h6>{{ $armLabel }} — top {{ $rk['top_n'] }}</h6>
                            @foreach($entries as $e)
                                <div class="rk-row">
                                    <span class="pos-badge {{ $badge($e['rank']) }}">{{ $e['rank'] }}</span>
                                    <div>
                                        <div class="rk-name">{{ $e['name'] }}</div>
                                        <div class="rk-meta">{{ $e['admissionno'] }}</div>
                                    </div>
                                    <span class="rk-val">{{ $fmt($e['value']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>
        @endif

        @if(!empty($rk['by_subject']))
            <div class="mt-4">
                <button type="button" class="rk-toggle no-print" aria-expanded="false" aria-controls="rkSubjects"
                        onclick="var b=document.getElementById('rkSubjects');var o=b.style.display!=='none';b.style.display=o?'none':'block';this.setAttribute('aria-expanded',!o);">
                    <i class="ri-book-open-line me-1"></i>Best in each subject (top {{ $rk['subject_top_n'] }})
                </button>
                <div id="rkSubjects" class="rk-collapse mt-3" style="display:none;overflow-x:auto;">
                    <table class="rk-subj">
                        <thead><tr><th style="width:24%;">Subject</th><th>Toppers</th></tr></thead>
                        <tbody>
                            @foreach($rk['by_subject'] as $subj)
                                <tr>
                                    <td style="font-weight:600;color:var(--cb-navy);">{{ $subj['subject'] }}
                                        <div class="rk-meta">{{ $subj['count'] }} scored</div></td>
                                    <td>
                                        @foreach($subj['top'] as $e)
                                            <span class="rk-chip">
                                                <b>{{ $e['rank'] }}</b>{{ $e['name'] }}
                                                @if(!empty($is_combined))<span class="rk-meta">({{ $e['arm'] }})</span>@endif
                                                <strong>{{ $fmt($e['value']) }}</strong>
                                                @if($e['grade'] && $e['grade'] !== '-')<span class="rk-meta">{{ $e['grade'] }}</span>@endif
                                            </span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(!empty($rk['excluded']))
            <details class="mt-3 no-print">
                <summary class="small text-muted" style="cursor:pointer;">
                    {{ count($rk['excluded']) }} student(s) not ranked — see why
                </summary>
                @php $byId = collect($studentRows)->keyBy('id'); @endphp
                <ul class="small text-muted mt-2 mb-0">
                    @foreach($rk['excluded'] as $sid => $reason)
                        @php $s = $byId[$sid] ?? null; @endphp
                        <li>{{ $s ? strtoupper($s['lastname']) . ', ' . $s['firstname'] : 'Student #' . $sid }} — {{ $reason }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>
</div>
