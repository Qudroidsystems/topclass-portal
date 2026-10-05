{{-- resources/views/broadsheet/ranking/index.blade.php (TopClass) --}}
@extends('layouts.master')

@section('content')
<style>
:root { --rk-navy:#0f2342; --rk-teal:#0d9488; --rk-amber:#f59e0b; --rk-muted:#64748b; --rk-border:#e2e8f0; }
.rk-hero { background:linear-gradient(135deg,var(--rk-navy) 0%,#1e4a7e 60%,var(--rk-teal) 100%); border-radius:14px; padding:26px 30px; margin-bottom:22px; color:#fff; }
.rk-hero h1 { font-size:22px; font-weight:700; margin:0 0 6px; color:#fff; }
.rk-hero p { font-size:13px; margin:0; color:rgba(255,255,255,.78); max-width:70ch; }
.rk-card { background:#fff; border:1px solid var(--rk-border); border-radius:14px; box-shadow:0 4px 16px rgba(15,35,66,.08); height:100%; }
.rk-card-head { padding:14px 20px; border-bottom:1px solid var(--rk-border); font-weight:700; color:var(--rk-navy); display:flex; align-items:center; gap:8px; }
.rk-card-body { padding:18px 20px; }
.rk-label { font-size:12px; font-weight:600; color:#374151; margin-bottom:4px; display:block; }
.rk-hint { font-size:11px; color:var(--rk-muted); margin-top:3px; }
.rk-sep { border-top:1px solid var(--rk-border); margin:16px 0; }
.rk-save { background:var(--rk-teal); color:#fff; border:none; border-radius:10px; padding:10px 16px; font-weight:600; width:100%; }
.rk-save:hover { filter:brightness(.95); }
.rk-save:focus-visible { outline:2px solid var(--rk-navy); outline-offset:2px; }
.rk-note { background:#fffbeb; border:1px solid #fcd34d; color:#92400e; border-radius:10px; padding:10px 14px; font-size:12.5px; margin-bottom:18px; }
</style>

<div class="main-content"><div class="page-content"><div class="container-fluid">

    <div class="rk-hero d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1><i class="ri-trophy-line me-2"></i>Broadsheet ranking</h1>
            <p>Choose how the best students are picked on the broadsheet web view, separately for junior and senior classes.</p>
        </div>
        <a href="{{ route('broadsheet.best-students') }}" class="btn btn-light btn-sm" style="border-radius:10px;font-weight:600;">
            <i class="ri-medal-line me-1"></i>Open best students report
        </a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="rk-note">
        <i class="ri-information-line me-1"></i>
        This ranking is unofficial. Official positions on report cards and promotion decisions are not changed by anything here.
    </div>

    <div class="row g-3">
        @foreach($sections as $section => $s)
        <div class="col-lg-6">
            <div class="rk-card">
                <div class="rk-card-head"><i class="ri-graduation-cap-line" style="color:var(--rk-teal)"></i>{{ ucfirst($section) }} classes</div>
                <div class="rk-card-body">
                <form method="POST" action="{{ route('broadsheet.ranking.save', $section) }}">
                    @csrf
                    <label class="rk-label" for="pm_{{ $section }}">Rank by</label>
                    <select id="pm_{{ $section }}" name="primary_measure" class="form-select form-select-sm">
                        @foreach($measures as $k => $lbl)<option value="{{ $k }}" @selected($s->primary_measure === $k)>{{ $lbl }}</option>@endforeach
                    </select>

                    <label class="rk-label mt-3">Tie-breakers, in order</label>
                    @for($i = 0; $i < 3; $i++)
                        <select name="tiebreakers[]" class="form-select form-select-sm mb-1" aria-label="Tie-breaker {{ $i + 1 }}">
                            <option value="">None</option>
                            @foreach($measures as $k => $lbl)<option value="{{ $k }}" @selected(($s->tiebreakers[$i] ?? null) === $k)>{{ $lbl }}</option>@endforeach
                        </select>
                    @endfor

                    <div class="rk-sep"></div>
                    <span class="rk-label">Who can be ranked</span>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="number" min="0" max="40" name="min_subjects" value="{{ $s->min_subjects }}" class="form-control form-control-sm" aria-label="Minimum subjects">
                            <div class="rk-hint">Minimum subjects scored</div>
                        </div>
                        <div class="col-6">
                            <input type="number" step="0.01" min="0" max="100" name="min_average" value="{{ $s->min_average }}" class="form-control form-control-sm" aria-label="Minimum average">
                            <div class="rk-hint">Minimum average (optional)</div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="require_all_compulsory" value="1" id="rac_{{ $section }}" @checked($s->require_all_compulsory)>
                        <label class="form-check-label small" for="rac_{{ $section }}">Every compulsory subject must be scored</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="exclude_failed" value="1" id="exf_{{ $section }}" @checked($s->exclude_failed)>
                        <label class="form-check-label small" for="exf_{{ $section }}">Leave out anyone who failed a subject (below 40 or F / F9)</label>
                    </div>

                    <div class="rk-sep"></div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="rk-label" for="sc_{{ $section }}">Show winners for</label>
                            <select id="sc_{{ $section }}" name="scope" class="form-select form-select-sm">
                                @foreach($scopes as $k => $lbl)<option value="{{ $k }}" @selected($s->scope === $k)>{{ $lbl }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="rk-label" for="tn_{{ $section }}">Overall</label>
                            <select id="tn_{{ $section }}" name="top_n" class="form-select form-select-sm">
                                @foreach([1, 3, 5, 10] as $n)<option value="{{ $n }}" @selected((int) $s->top_n === $n)>Top {{ $n }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="rk-label" for="stn_{{ $section }}">Per subject</label>
                            <select id="stn_{{ $section }}" name="subject_top_n" class="form-select form-select-sm">
                                @foreach([1, 3, 5] as $n)<option value="{{ $n }}" @selected((int) ($s->subject_top_n ?? 3) === $n)>Top {{ $n }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="show_rank_column" value="1" id="src_{{ $section }}" @checked($s->show_rank_column)>
                        <label class="form-check-label small" for="src_{{ $section }}">Add a "Rank" column to the broadsheet</label>
                    </div>

                    <label class="rk-label mt-3" for="core_{{ $section }}">Core subjects</label>
                    <select id="core_{{ $section }}" name="core_subject_ids[]" class="form-select form-select-sm" multiple size="6">
                        @foreach($subjects as $subj)
                            <option value="{{ $subj->id }}" @selected(in_array($subj->id, (array) ($s->core_subject_ids ?? [])))>{{ $subj->subject }}</option>
                        @endforeach
                    </select>
                    <div class="rk-hint">Used by "Core-subjects average". Hold Ctrl / Cmd to pick several.</div>

                    <button class="rk-save mt-3"><i class="ri-save-line me-1"></i>Save {{ $section }} settings</button>
                </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div></div></div>
@endsection
