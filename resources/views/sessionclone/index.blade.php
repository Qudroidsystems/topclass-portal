@extends('layouts.master')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
:root {
    --cl-primary: #1e3a5f;
    --cl-accent:  #2563eb;
    --cl-success: #16a34a;
    --cl-warning: #d97706;
    --cl-danger:  #dc2626;
    --cl-purple:  #7c3aed;
    --cl-muted:   #6b7280;
    --cl-border:  #e2e8f0;
    --cl-bg:      #f8fafc;
    --cl-radius:  12px;
    --cl-shadow:  0 2px 8px rgba(0,0,0,.08);
}
.clone-page { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }

@keyframes fadeInUp   { from { opacity:0; transform:translateY(14px);} to { opacity:1; transform:none; } }
@keyframes fadeInDown { from { opacity:0; transform:translateY(-14px);} to { opacity:1; transform:none; } }
@keyframes rowIn      { from { opacity:0; transform:translateX(-8px);} to { opacity:1; transform:none; } }
@keyframes pulseRing  { 0%{box-shadow:0 0 0 0 rgba(37,99,235,.45)} 100%{box-shadow:0 0 0 12px rgba(37,99,235,0)} }

/* Loading overlay */
.loading-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:9999; display:none; align-items:center; justify-content:center; }
.loading-overlay.active { display:flex; }
.loading-spinner { background:#fff; padding:24px 32px; border-radius:14px; box-shadow:0 8px 32px rgba(0,0,0,.18); text-align:center; }
.loading-spinner p { margin:10px 0 0; font-size:14px; font-weight:600; color:var(--cl-primary); }

/* Hero */
.clone-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--cl-radius); padding: 26px 32px; margin-bottom: 24px;
    position: relative; overflow: hidden; animation: fadeInDown .45s ease both;
}
.clone-hero::before { content:''; position:absolute; top:-60px; right:-40px; width:220px; height:220px; background:rgba(255,255,255,.07); border-radius:50%; }
.clone-hero::after  { content:''; position:absolute; bottom:-80px; right:160px; width:160px; height:160px; background:rgba(255,255,255,.05); border-radius:50%; }
.clone-hero h1 { font-size:21px; font-weight:700; color:#fff; margin:0 0 6px; position:relative; }
.clone-hero p  { font-size:13px; color:rgba(255,255,255,.8); margin:0; position:relative; max-width:720px; }
.clone-hero .hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; color:#fff; font-size:26px; position:relative; }

/* Cards */
.cl-card { background:#fff; border:1px solid var(--cl-border); border-radius:var(--cl-radius); box-shadow:var(--cl-shadow); margin-bottom:20px; animation:fadeInUp .45s ease both; }
.cl-card-header { padding:16px 20px; border-bottom:1px solid var(--cl-border); display:flex; align-items:center; gap:10px; }
.cl-card-header h5 { margin:0; font-size:15px; font-weight:700; color:var(--cl-primary); }
.cl-card-header .step { width:28px; height:28px; border-radius:50%; background:var(--cl-accent); color:#fff; font-weight:700; font-size:13px; display:flex; align-items:center; justify-content:center; }
.cl-card-body { padding:20px; }

/* Mode tabs */
.mode-tabs { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.mode-tab { border:2px solid var(--cl-border); border-radius:12px; padding:14px 16px; cursor:pointer; transition:all .2s; background:#fff; }
.mode-tab:hover { border-color:#93c5fd; transform:translateY(-2px); }
.mode-tab.active { border-color:var(--cl-accent); background:linear-gradient(135deg,#eff6ff,#fff); box-shadow:0 4px 14px rgba(37,99,235,.15); }
.mode-tab i { font-size:22px; color:var(--cl-accent); }
.mode-tab .t { font-weight:700; color:var(--cl-primary); font-size:14px; margin-top:6px; }
.mode-tab .d { font-size:12px; color:var(--cl-muted); }
@media (max-width: 768px) { .mode-tabs { grid-template-columns:1fr; } }

/* Source → target */
.flow-grid { display:grid; grid-template-columns:1fr 70px 1fr; gap:14px; align-items:stretch; }
.flow-box { border-radius:12px; padding:18px; border:1px dashed #cbd5e1; background:var(--cl-bg); }
.flow-box.source { border-color:#bfdbfe; background:linear-gradient(135deg,#eff6ff,#f8fafc); }
.flow-box.target { border-color:#bbf7d0; background:linear-gradient(135deg,#f0fdf4,#f8fafc); }
.flow-box .lbl { font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; margin-bottom:10px; }
.flow-box.source .lbl { color:var(--cl-accent); }
.flow-box.target .lbl { color:var(--cl-success); }
.flow-arrow { display:flex; align-items:center; justify-content:center; }
.flow-arrow span { width:46px; height:46px; border-radius:50%; background:var(--cl-accent); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; animation:pulseRing 1.8s infinite; }
@media (max-width: 768px) { .flow-grid { grid-template-columns:1fr; } .flow-arrow span { transform:rotate(90deg); } }
.form-label { font-size:12px; font-weight:600; color:#334155; }

/* Modules */
.module-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:12px; }
.module-item { position:relative; border:2px solid var(--cl-border); border-radius:12px; padding:14px 14px 14px 52px; cursor:pointer; transition:all .2s; user-select:none; }
.module-item:hover { border-color:#93c5fd; }
.module-item.checked { border-color:var(--cl-accent); background:#eff6ff; }
.module-item .m-icon { position:absolute; left:12px; top:14px; width:30px; height:30px; border-radius:8px; background:#e0e7ff; color:var(--cl-accent); display:flex; align-items:center; justify-content:center; font-size:16px; }
.module-item.checked .m-icon { background:var(--cl-accent); color:#fff; }
.module-item .m-title { font-weight:700; font-size:13.5px; color:var(--cl-primary); }
.module-item .m-desc { font-size:11.5px; color:var(--cl-muted); }
.module-item .m-check { position:absolute; right:12px; top:12px; }
.module-item .m-req { font-size:10.5px; color:var(--cl-purple); font-weight:600; margin-top:4px; }

/* Stats */
.stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:18px; }
.stat-card { border-radius:12px; padding:16px 18px; color:#fff; position:relative; overflow:hidden; animation:fadeInUp .4s ease both; }
.stat-card::after { content:''; position:absolute; right:-20px; top:-20px; width:90px; height:90px; border-radius:50%; background:rgba(255,255,255,.12); }
.stat-card .v { font-size:28px; font-weight:800; line-height:1; }
.stat-card .l { font-size:12px; opacity:.9; margin-top:6px; }
.stat-card.s1 { background:linear-gradient(135deg,#1e3a5f,#2563eb); }
.stat-card.s2 { background:linear-gradient(135deg,#15803d,#22c55e); }
.stat-card.s3 { background:linear-gradient(135deg,#b45309,#f59e0b); }
@media (max-width: 768px) { .stat-row { grid-template-columns:1fr; } }

/* Result rows */
.res-module { border:1px solid var(--cl-border); border-radius:12px; margin-bottom:12px; overflow:hidden; animation:rowIn .35s ease both; }
.res-head { display:flex; align-items:center; gap:12px; padding:12px 16px; background:var(--cl-bg); cursor:pointer; }
.res-head .ic { width:34px; height:34px; border-radius:9px; background:#e0e7ff; color:var(--cl-accent); display:flex; align-items:center; justify-content:center; font-size:17px; }
.res-head .nm { font-weight:700; color:var(--cl-primary); font-size:14px; flex:1; }
.pill { font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:999px; }
.pill.new  { background:#dcfce7; color:#15803d; }
.pill.skip { background:#fef3c7; color:#b45309; }
.pill.src  { background:#e0e7ff; color:#3730a3; }
.res-body { padding:10px 16px 14px; display:none; max-height:320px; overflow:auto; }
.res-body.open { display:block; }
.res-line { display:flex; align-items:center; gap:8px; font-size:12.5px; padding:6px 0; border-bottom:1px dashed #eef2f7; animation:rowIn .3s ease both; }
.res-line:last-child { border-bottom:none; }
.res-line .tag { font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:6px; }
.res-line .tag.new { background:#dcfce7; color:#15803d; }
.res-line .tag.exists { background:#f1f5f9; color:#64748b; }
.res-line .trm { margin-left:auto; color:var(--cl-muted); font-size:11px; }
.res-note { font-size:12px; color:var(--cl-warning); padding:4px 0; }

.info-banner { background:linear-gradient(135deg,#fffbeb,#fef9c3); border:1px solid #fde68a; border-radius:12px; padding:14px 18px; font-size:12.5px; color:#78350f; margin-bottom:20px; }
.info-banner i { color:var(--cl-warning); }

.btn-cl { border-radius:10px; font-weight:700; padding:10px 20px; font-size:13.5px; }
.btn-cl-primary { background:var(--cl-accent); color:#fff; border:none; }
.btn-cl-primary:hover { background:#1d4ed8; color:#fff; }
.btn-cl-success { background:var(--cl-success); color:#fff; border:none; }
.btn-cl-success:hover { background:#15803d; color:#fff; }
.btn-cl:disabled { opacity:.55; }

.hist-table { width:100%; font-size:12.5px; }
.hist-table th { font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--cl-muted); font-weight:700; padding:10px 12px; border-bottom:2px solid var(--cl-border); background:var(--cl-bg); white-space:nowrap; }
.hist-table td { padding:10px 12px; border-bottom:1px solid #eef2f7; vertical-align:top; }
.hist-table tr { animation:rowIn .3s ease both; }
.hist-mod { display:inline-block; font-size:10.5px; background:#eef2ff; color:#3730a3; border-radius:6px; padding:2px 7px; margin:1px 2px 1px 0; }
.st-badge { font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px; white-space:nowrap; }
.st-completed { background:#dcfce7; color:#15803d; }
.st-reverted { background:#f1f5f9; color:#64748b; }
.st-partially_reverted { background:#fef3c7; color:#b45309; }
.btn-xs { font-size:11.5px; padding:4px 10px; border-radius:8px; font-weight:600; }
.kept-list { text-align:left; max-height:220px; overflow:auto; font-size:12px; margin-top:10px; border:1px solid #fde68a; background:#fffbeb; border-radius:8px; padding:8px 10px; }
.kept-list div { padding:3px 0; border-bottom:1px dashed #fde68a; }
.kept-list div:last-child { border-bottom:none; }
.empty-preview { text-align:center; padding:36px 10px; color:var(--cl-muted); }
.empty-preview i { font-size:44px; color:#cbd5e1; }
</style>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status"></div>
        <p id="loadingText">Working…</p>
    </div>
</div>

<div class="main-content">
<div class="page-content clone-page">
<div class="container-fluid">

    {{-- Hero --}}
    <div class="clone-hero d-flex align-items-center gap-3">
        <div class="hero-icon"><i class="ri-file-copy-2-line"></i></div>
        <div>
            <h1>Clone Session / Term Setup</h1>
            <p>Start a new term or session in seconds. Copy subject teachers, subject classes, class teachers,
               compulsory subjects, class bills and promotion rules from an existing term — no re-entry needed.</p>
        </div>
    </div>

    <div class="info-banner">
        <i class="ri-information-line me-1"></i>
        Cloning only <strong>adds</strong> records. Anything already set up in the target term is left untouched and skipped,
        so you can safely run it more than once. Students, scores and payments are never copied.
        Every clone is saved as a <strong>snapshot</strong> that you can review, download or <strong>undo</strong> from Clone History below.
    </div>

    <div class="row">
        <div class="col-xl-7">

            {{-- Step 1: mode --}}
            <div class="cl-card" style="animation-delay:.05s">
                <div class="cl-card-header"><span class="step">1</span><h5>What do you want to clone?</h5></div>
                <div class="cl-card-body">
                    <div class="mode-tabs">
                        <div class="mode-tab active" data-mode="term">
                            <i class="ri-arrow-left-right-line"></i>
                            <div class="t">Term → Term</div>
                            <div class="d">Same session, next term</div>
                        </div>
                        <div class="mode-tab" data-mode="session">
                            <i class="ri-calendar-event-line"></i>
                            <div class="t">Session → Session</div>
                            <div class="d">One term into a new session</div>
                        </div>
                        <div class="mode-tab" data-mode="session_all">
                            <i class="ri-stack-line"></i>
                            <div class="t">Whole Session</div>
                            <div class="d">All terms, term-for-term</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2: source / target --}}
            <div class="cl-card" style="animation-delay:.1s">
                <div class="cl-card-header"><span class="step">2</span><h5>Choose source and target</h5></div>
                <div class="cl-card-body">
                    <div class="flow-grid">
                        <div class="flow-box source">
                            <div class="lbl"><i class="ri-upload-2-line"></i> Copy from</div>
                            <div class="mb-3">
                                <label class="form-label">Session</label>
                                <select id="source_session" class="form-select">
                                    @foreach($sessions as $s)
                                        <option value="{{ $s->id }}" @selected($currentSession && $currentSession->id == $s->id)>
                                            {{ $s->session }}{{ $s->status === 'Current' ? ' (Current)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="term-wrap">
                                <label class="form-label">Term</label>
                                <select id="source_term" class="form-select">
                                    @foreach($terms as $t)
                                        <option value="{{ $t->id }}" @selected($currentTerm && $currentTerm->id == $t->id)>
                                            {{ $t->term }}{{ $t->status ? ' (Active)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="all-terms-note small text-muted" style="display:none">
                                <i class="ri-stack-line"></i> All terms in this session
                            </div>
                        </div>

                        <div class="flow-arrow"><span><i class="ri-arrow-right-line"></i></span></div>

                        <div class="flow-box target">
                            <div class="lbl"><i class="ri-download-2-line"></i> Paste into</div>
                            <div class="mb-3">
                                <label class="form-label">Session</label>
                                <select id="target_session" class="form-select">
                                    @foreach($sessions as $s)
                                        <option value="{{ $s->id }}" @selected($currentSession && $currentSession->id == $s->id)>
                                            {{ $s->session }}{{ $s->status === 'Current' ? ' (Current)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="term-wrap">
                                <label class="form-label">Term</label>
                                <select id="target_term" class="form-select">
                                    @foreach($terms as $t)
                                        <option value="{{ $t->id }}">{{ $t->term }}{{ $t->status ? ' (Active)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="all-terms-note small text-muted" style="display:none">
                                <i class="ri-stack-line"></i> Each term copied onto the same term
                            </div>
                        </div>
                    </div>
                    <div id="sameWarning" class="text-danger small mt-2" style="display:none">
                        <i class="ri-error-warning-line"></i> Source and target are the same — pick a different target.
                    </div>
                </div>
            </div>

            {{-- Step 3: modules --}}
            <div class="cl-card" style="animation-delay:.15s">
                <div class="cl-card-header">
                    <span class="step">3</span><h5>Select what to copy</h5>
                    <div class="ms-auto">
                        <a href="#" id="selectAll" class="small fw-semibold">Select all</a>
                        <span class="text-muted mx-1">·</span>
                        <a href="#" id="selectNone" class="small fw-semibold text-muted">Clear</a>
                    </div>
                </div>
                <div class="cl-card-body">
                    <div class="module-grid">
                        @foreach($modules as $m)
                            <label class="module-item checked" data-key="{{ $m['key'] }}"
                                   data-requires="{{ implode(',', $m['requires']) }}">
                                <span class="m-icon"><i class="{{ $m['icon'] }}"></i></span>
                                <input type="checkbox" class="form-check-input m-check module-cb" value="{{ $m['key'] }}" checked>
                                <div class="m-title">{{ $m['label'] }}</div>
                                <div class="m-desc">{{ $m['description'] }}</div>
                                @if(!empty($m['requires']))
                                    <div class="m-req"><i class="ri-link"></i> Includes Subject Teachers</div>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <button class="btn btn-cl btn-cl-primary" id="btnPreview">
                            <i class="ri-eye-line me-1"></i> Preview
                        </button>
                        @can('Run session clone')
                        <button class="btn btn-cl btn-cl-success" id="btnClone" disabled>
                            <i class="ri-file-copy-2-line me-1"></i> Clone Now
                        </button>
                        @else
                        <span class="small text-muted align-self-center"><i class="ri-lock-line"></i> You can preview, but you don't have permission to run a clone.</span>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        {{-- Preview panel --}}
        <div class="col-xl-5">
            <div class="cl-card" style="animation-delay:.2s; position:sticky; top:90px;">
                <div class="cl-card-header">
                    <i class="ri-search-eye-line text-primary fs-5"></i><h5>Preview</h5>
                    <span class="ms-auto small text-muted" id="previewRoute"></span>
                </div>
                <div class="cl-card-body" id="previewBody">
                    <div class="empty-preview">
                        <i class="ri-file-search-line"></i>
                        <p class="mt-2 mb-0">Pick a source and target, then click <strong>Preview</strong><br>
                           to see exactly what will be copied.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Clone history / snapshots --}}
    <div class="cl-card" style="animation-delay:.25s">
        <div class="cl-card-header">
            <i class="ri-history-line text-primary fs-5"></i><h5>Clone History &amp; Undo</h5>
            <button class="btn btn-light btn-xs ms-auto" id="btnRefreshHistory"><i class="ri-refresh-line"></i> Refresh</button>
        </div>
        <div class="cl-card-body p-0">
            <div class="table-responsive">
                <table class="hist-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Date</th><th>Clone</th><th>What was copied</th><th>Records</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody">
                        <tr><td colspan="7" class="text-center text-muted py-4">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
</div>{{-- /.main-content --}}

{{-- Snapshot details modal --}}
<div class="modal fade" id="snapshotModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#1e3a5f,#2563eb); color:#fff;">
                <h5 class="modal-title text-white"><i class="ri-camera-line me-1"></i> Snapshot <span id="snapId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="snapBody"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    const CSRF        = '{{ csrf_token() }}';
    const PREVIEW_URL = '{{ route('sessionclone.preview') }}';
    const CLONE_URL   = '{{ route('sessionclone.clone') }}';
    const HISTORY_URL = '{{ route('sessionclone.history') }}';
    const BATCH_URL   = '{{ url('setup-clone/history') }}';
    const CAN_REVERT  = @json(auth()->user()->can('Revert session clone'));
    const TERMS       = @json($terms->pluck('term', 'id'));
    let mode          = 'term';
    let previewed     = false;

    const esc = s => $('<div>').text(s ?? '').html();
    const termName = id => TERMS[id] || ('Term ' + id);

    function loading(on, text) {
        $('#loadingText').text(text || 'Working…');
        $('#loadingOverlay').toggleClass('active', !!on);
    }

    function invalidate() {
        previewed = false;
        $('#btnClone').prop('disabled', true);
        checkSame();
    }

    // Suggest the "next" term as the target in Term → Term mode
    function suggestTarget() {
        if (mode === 'term') {
            $('#target_session').val($('#source_session').val());
            const opts = $('#target_term option').map((i, o) => o.value).get();
            const idx  = opts.indexOf($('#source_term').val());
            if (idx > -1 && idx < opts.length - 1) $('#target_term').val(opts[idx + 1]);
        } else if (mode === 'session' || mode === 'session_all') {
            const sOpts = $('#target_session option').map((i, o) => o.value).get();
            // sessions are listed newest-first; pick the one above the source if any
            const idx = sOpts.indexOf($('#source_session').val());
            if (idx > 0) $('#target_session').val(sOpts[idx - 1]);
            if (mode === 'session') $('#target_term').val($('#target_term option:first').val());
        }
    }

    function checkSame() {
        const same = mode !== 'session_all'
            ? ($('#source_session').val() === $('#target_session').val() && $('#source_term').val() === $('#target_term').val())
            : ($('#source_session').val() === $('#target_session').val());
        $('#sameWarning').toggle(same);
        $('#btnPreview').prop('disabled', same);
        return same;
    }

    // Mode switching
    $('.mode-tab').on('click', function () {
        $('.mode-tab').removeClass('active');
        $(this).addClass('active');
        mode = $(this).data('mode');

        const all = mode === 'session_all';
        $('.term-wrap').toggle(!all);
        $('.all-terms-note').toggle(all);
        $('#target_session').prop('disabled', mode === 'term');

        suggestTarget();
        invalidate();
    });

    $('#source_session, #source_term').on('change', function () { suggestTarget(); invalidate(); });
    $('#target_session, #target_term').on('change', invalidate);

    // Module selection with dependency handling
    function syncModuleStyles() {
        $('.module-item').each(function () {
            $(this).toggleClass('checked', $(this).find('.module-cb').is(':checked'));
        });
    }
    $('.module-cb').on('change', function () {
        const item = $(this).closest('.module-item');
        if (this.checked) {
            (item.data('requires') || '').toString().split(',').filter(Boolean).forEach(k => {
                $(`.module-item[data-key="${k}"] .module-cb`).prop('checked', true);
            });
        } else {
            const key = item.data('key');
            $('.module-item').each(function () {
                const req = ($(this).data('requires') || '').toString().split(',');
                if (req.includes(key)) $(this).find('.module-cb').prop('checked', false);
            });
        }
        syncModuleStyles();
        invalidate();
    });
    $('#selectAll').on('click', e => { e.preventDefault(); $('.module-cb').prop('checked', true); syncModuleStyles(); invalidate(); });
    $('#selectNone').on('click', e => { e.preventDefault(); $('.module-cb').prop('checked', false); syncModuleStyles(); invalidate(); });

    function payload() {
        const all = mode === 'session_all';
        return {
            _token: CSRF,
            source_session: $('#source_session').val(),
            source_term:    all ? 'all' : $('#source_term').val(),
            target_session: mode === 'term' ? $('#source_session').val() : $('#target_session').val(),
            target_term:    all ? null : $('#target_term').val(),
            modules:        $('.module-cb:checked').map((i, c) => c.value).get(),
        };
    }

    function routeLabel() {
        const p   = payload();
        const ss  = $('#source_session option:selected').text().trim().replace(' (Current)', '');
        const ts  = $(`#target_session option[value="${p.target_session}"]`).text().trim().replace(' (Current)', '');
        if (p.source_term === 'all') return `${ss} → ${ts} (all terms)`;
        return `${termName(p.source_term)} ${ss} → ${termName(p.target_term)} ${ts}`;
    }

    function renderResult(res, executed) {
        const t = res.totals;
        let html = `
            <div class="stat-row">
                <div class="stat-card s1"><div class="v">${t.source}</div><div class="l">Found in source</div></div>
                <div class="stat-card s2" style="animation-delay:.05s"><div class="v">${t.create}</div><div class="l">${executed ? 'Cloned' : 'Will be cloned'}</div></div>
                <div class="stat-card s3" style="animation-delay:.1s"><div class="v">${t.skip}</div><div class="l">Already exist / skipped</div></div>
            </div>`;

        if (executed) {
            html += `<div class="alert alert-success py-2 small mb-3"><i class="ri-checkbox-circle-line me-1"></i>${esc(res.message)}</div>`;
        }

        res.modules.forEach((m, i) => {
            const lines = (m.samples || []).map((s, j) => `
                <div class="res-line" style="animation-delay:${Math.min(j, 20) * 0.02}s">
                    <span class="tag ${s.status === 'new' ? 'new' : 'exists'}">${s.status === 'new' ? (executed ? 'COPIED' : 'NEW') : 'EXISTS'}</span>
                    <span>${esc(s.text)}</span>
                    ${payload().source_term === 'all' ? `<span class="trm">${esc(termName(s.term))}</span>` : ''}
                </div>`).join('');
            const more  = m.source > (m.samples || []).length
                ? `<div class="small text-muted pt-2">…and ${m.source - m.samples.length} more</div>` : '';
            const notes = (m.notes || []).map(n => `<div class="res-note"><i class="ri-alert-line"></i> ${esc(n)}</div>`).join('');

            html += `
                <div class="res-module" style="animation-delay:${i * 0.05}s">
                    <div class="res-head">
                        <span class="ic"><i class="${m.icon}"></i></span>
                        <span class="nm">${esc(m.label)}</span>
                        <span class="pill new">+${m.create}</span>
                        <span class="pill skip">${m.skip} skip</span>
                        <i class="ri-arrow-down-s-line text-muted"></i>
                    </div>
                    <div class="res-body ${i === 0 ? 'open' : ''}">
                        ${notes}
                        ${lines || '<div class="small text-muted py-2">Nothing set up in the source for this item.</div>'}
                        ${more}
                    </div>
                </div>`;
        });

        $('#previewRoute').text(routeLabel());
        $('#previewBody').html(html);
    }

    $('#previewBody').on('click', '.res-head', function () {
        $(this).next('.res-body').toggleClass('open');
    });

    function fail(xhr) {
        loading(false);
        const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
        Swal.fire({ icon: 'error', title: 'Oops', text: msg, confirmButtonColor: '#2563eb' });
    }

    $('#btnPreview').on('click', function () {
        const p = payload();
        if (!p.modules.length) {
            Swal.fire({ icon: 'warning', title: 'Nothing selected', text: 'Select at least one item to clone.', confirmButtonColor: '#2563eb' });
            return;
        }
        loading(true, 'Building preview…');
        $.post(PREVIEW_URL, p).done(res => {
            loading(false);
            renderResult(res, false);
            previewed = true;
            $('#btnClone').prop('disabled', res.totals.create === 0);
            if (res.totals.create === 0) {
                Swal.fire({ icon: 'info', title: 'Nothing new to copy',
                    text: res.totals.source === 0
                        ? 'The source term has no setup for the selected items.'
                        : 'Everything selected already exists in the target.',
                    confirmButtonColor: '#2563eb' });
            }
        }).fail(fail);
    });

    $('#btnClone').on('click', function () {
        if (!previewed) return;
        const p = payload();
        Swal.fire({
            icon: 'question',
            title: 'Clone setup?',
            html: `<div class="small">${esc(routeLabel())}</div><div class="mt-2">Existing records in the target will be kept as they are.</div>`,
            showCancelButton: true,
            confirmButtonText: 'Yes, clone it',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
        }).then(r => {
            if (!r.isConfirmed) return;
            loading(true, 'Cloning…');
            $.post(CLONE_URL, p).done(res => {
                loading(false);
                renderResult(res, true);
                previewed = false;
                $('#btnClone').prop('disabled', true);
                Swal.fire({ icon: 'success', title: 'Done!', text: res.message, confirmButtonColor: '#16a34a' });
                loadHistory();
            }).fail(fail);
        });
    });

    /* ---------------- Clone history / snapshots ---------------- */
    const STATUS_TEXT = { completed: 'Active', partially_reverted: 'Partly undone', reverted: 'Undone' };

    function loadHistory() {
        $.get(HISTORY_URL).done(res => {
            if (!res.ready) {
                $('#historyBody').html('<tr><td colspan="7" class="text-center text-danger py-4"><i class="ri-error-warning-line"></i> Snapshot tables not found. Run <code>php artisan migrate</code>.</td></tr>');
                return;
            }
            if (!res.data.length) {
                $('#historyBody').html('<tr><td colspan="7" class="text-center text-muted py-4">No clones yet. Each clone you run will appear here as a snapshot you can undo.</td></tr>');
                return;
            }
            $('#historyBody').html(res.data.map((b, i) => `
                <tr style="animation-delay:${Math.min(i, 15) * 0.03}s">
                    <td class="fw-bold text-muted">#${b.id}</td>
                    <td class="text-nowrap">${esc(b.created_at)}<div class="small text-muted">${esc(b.user)}</div></td>
                    <td>${esc(b.route)}</td>
                    <td>${b.modules.map(m => `<span class="hist-mod">${esc(m)}</span>`).join('')}</td>
                    <td class="text-nowrap"><strong>${b.created}</strong> created
                        ${b.status !== 'completed' ? `<div class="small text-muted">${b.remaining} still in place</div>` : ''}</td>
                    <td><span class="st-badge st-${b.status}">${STATUS_TEXT[b.status] || b.status}</span>
                        ${b.reverted_at ? `<div class="small text-muted mt-1">${esc(b.reverted_at)}${b.reverted_by ? ' · ' + esc(b.reverted_by) : ''}</div>` : ''}</td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-light btn-xs btn-view" data-id="${b.id}" title="View snapshot"><i class="ri-eye-line"></i></button>
                        <a class="btn btn-light btn-xs" href="${BATCH_URL}/${b.id}/download" title="Download snapshot (JSON)"><i class="ri-download-2-line"></i></a>
                        ${b.status !== 'reverted' && CAN_REVERT ? `<button class="btn btn-outline-danger btn-xs btn-undo" data-id="${b.id}"><i class="ri-arrow-go-back-line"></i> Undo</button>` : ''}
                    </td>
                </tr>`).join(''));
        }).fail(() => {
            $('#historyBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Could not load clone history.</td></tr>');
        });
    }

    $('#btnRefreshHistory').on('click', loadHistory);

    $('#historyBody').on('click', '.btn-view', function () {
        const id = $(this).data('id');
        $('#snapId').text('#' + id);
        $('#snapBody').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('snapshotModal')).show();
        $.get(`${BATCH_URL}/${id}`).done(res => {
            const groups = {};
            res.items.forEach(it => (groups[it.module] = groups[it.module] || []).push(it));
            $('#snapBody').html(Object.keys(groups).map(mod => `
                <h6 class="fw-bold mt-2" style="color:#1e3a5f">${esc(mod)} <span class="text-muted fw-normal">(${groups[mod].length})</span></h6>
                ${groups[mod].map(it => `
                    <div class="res-line">
                        <span class="tag ${it.reverted ? 'exists' : 'new'}">${it.reverted ? 'UNDONE' : 'IN PLACE'}</span>
                        <span>${esc(it.label)}</span>
                        ${it.kept_reason ? `<span class="trm text-warning">${esc(it.kept_reason)}</span>` : `<span class="trm">ID ${it.record_id}</span>`}
                    </div>`).join('')}`).join('') || '<p class="text-muted">No items recorded.</p>');
        }).fail(() => $('#snapBody').html('<p class="text-danger">Could not load snapshot.</p>'));
    });

    $('#historyBody').on('click', '.btn-undo', function () {
        const id = $(this).data('id');
        loading(true, 'Checking what can be undone…');
        $.post(`${BATCH_URL}/${id}/revert`, { _token: CSRF, dry_run: 1 }).done(res => {
            loading(false);
            if (res.remove === 0) {
                Swal.fire({
                    icon: 'info', title: 'Nothing to undo right now',
                    html: `${esc(res.message)}${keptHtml(res.kept)}`,
                    confirmButtonColor: '#2563eb'
                });
                return;
            }
            Swal.fire({
                icon: 'warning',
                title: `Undo clone #${id}?`,
                html: `<div>${esc(res.message)}</div>${keptHtml(res.kept)}
                       <div class="small text-muted mt-2">Only records created by this clone are affected.</div>`,
                showCancelButton: true,
                confirmButtonText: `Yes, remove ${res.remove} record(s)`,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            }).then(r => {
                if (!r.isConfirmed) return;
                loading(true, 'Undoing clone…');
                $.post(`${BATCH_URL}/${id}/revert`, { _token: CSRF }).done(done => {
                    loading(false);
                    Swal.fire({ icon: 'success', title: 'Clone undone', html: `${esc(done.message)}${keptHtml(done.kept)}`, confirmButtonColor: '#16a34a' });
                    loadHistory();
                    invalidate();
                }).fail(fail);
            });
        }).fail(fail);
    });

    function keptHtml(kept) {
        if (!kept || !kept.length) return '';
        return `<div class="kept-list"><strong>Kept (in use):</strong>${kept.map(k =>
            `<div>${esc(k.module)}: ${esc(k.label)}<br><span class="text-muted">${esc(k.reason)}</span></div>`).join('')}</div>`;
    }

    loadHistory();

    // initial state
    $('#target_session').prop('disabled', true);
    suggestTarget();
    checkSame();
});
</script>
@endsection
