@extends('layouts.master')

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $T = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl(\App\Services\Website\Yearbooks::thumb($p));
    $status = request('status');
    $tab = fn ($s, $label, $n = null) => '<a href="' . route('yearbook.admin.show', array_filter(['id' => $book->id, 'status' => $s, 'class' => request('class'), 'q' => request('q')])) . '" class="btn btn-sm ' . ($status === $s ? 'btn-primary' : 'btn-light') . '">' . e($label) . ($n !== null ? ' <span class="badge bg-white text-dark ms-1">' . $n . '</span>' : '') . '</a>';
@endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$book->title" icon="ri-book-open-line" :subtitle="'Class of ' . $book->graduation_year . ($book->class_label ? ' · ' . $book->class_label : '') . ' · ' . ($book->published ? 'Published' : 'Draft — not visible yet') . ($book->isPortalOnly() ? ' · Portal users only' : '')" :back="route('yearbook.admin.index')" back-label="Yearbooks">
        <x-slot:actions>
            <a class="cb-hero-btn" href="{{ route('yearbook.admin.settings', $book->id) }}"><i class="ri-settings-4-line me-1"></i>Settings, cover &amp; principal’s message</a>
            <a class="cb-hero-btn" target="_blank" href="{{ route('site.yearbook', $book->slug) }}"><i class="ri-external-link-line me-1"></i>{{ $book->published ? 'View' : 'Preview' }}</a>
            <a class="cb-hero-btn" target="_blank" href="{{ route('site.yearbook.print', $book->slug) }}"><i class="ri-printer-line me-1"></i>Print / PDF</a>
        </x-slot:actions>
    </x-cb.hero>
    @include('website.admin.yearbooks._nav')

    @unless($book->published)
        <div class="cb-banner warning"><i class="ri-eye-off-line"></i><div>This yearbook is a <b>draft</b>. Students and parents can still use their upload links. Publish it from <a href="{{ route('yearbook.admin.settings', $book->id) }}">Settings</a> when it is ready.</div></div>
    @endunless

    <div class="row g-3">
        <div class="col-xl-4">
            <x-cb.card title="Add graduating students" icon="ri-user-add-line">
                <form id="candForm" onsubmit="return false">
                    <label class="form-label">Session</label>
                    <select id="candSession" class="form-select mb-2">
                        @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($book->session_id ? $book->session_id == $s->id : $s->status === 'Current')>{{ $s->session }}</option>@endforeach
                    </select>
                    <label class="form-label">Classes <small class="text-muted">(the graduating classes)</small></label>
                    <div class="border rounded p-2 mb-2" style="max-height:170px;overflow:auto">
                        @foreach($schoolClasses as $c)
                            <div class="form-check"><input class="form-check-input cand-class" type="checkbox" value="{{ $c->id }}" id="cc{{ $c->id }}"
                                @checked($book->class_label && str_starts_with(strtolower(str_replace(' ', '', $c->name)), strtolower(str_replace(' ', '', $book->class_label))))>
                                <label class="form-check-label" for="cc{{ $c->id }}">{{ $c->name }}</label></div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-outline-primary w-100" id="candLoad"><i class="ri-search-line me-1"></i>Find students</button>
                </form>
                <form method="POST" action="{{ route('yearbook.admin.import', $book->id) }}" id="importForm" class="d-none mt-3">@csrf
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="mb-0 small"><input type="checkbox" id="candAll" checked> Select all</label>
                        <span class="small text-muted" id="candCount"></span>
                    </div>
                    <div class="border rounded p-2 mb-2" style="max-height:260px;overflow:auto" id="candList"></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="copy_photos" value="1" id="copyPh" checked>
                        <label class="form-check-label small" for="copyPh">Use their portal passport until the graduation photo is uploaded</label></div>
                    <button class="btn btn-primary w-100"><i class="ri-download-2-line me-1"></i>Add selected to the yearbook</button>
                </form>
                <hr>
                <a href="{{ route('yearbook.admin.entry.create', $book->id) }}" class="btn btn-light w-100"><i class="ri-user-add-line me-1"></i>Add a graduate manually</a>
                <div class="form-text">For older sets that are not in the portal.</div>
            </x-cb.card>

            <x-cb.card title="Upload graduation photos" icon="ri-camera-line">
                <form method="POST" action="{{ route('yearbook.admin.photos', $book->id) }}" enctype="multipart/form-data" id="bulkPhForm">@csrf
                    <label for="bulkPh" class="d-block border border-2 rounded-3 p-3 text-center" style="border-style:dashed!important;cursor:pointer">
                        <i class="ri-image-add-line" style="font-size:2rem;color:#94a3b8"></i>
                        <div class="fw-semibold">Choose many photos at once</div>
                        <div class="small text-muted">Name each file by the student’s <b>admission number</b> (e.g. <code>TCC-2020-014.jpg</code>) or <b>full name</b> as in the list.</div>
                    </label>
                    <input type="file" name="photos[]" id="bulkPh" accept="image/*" multiple hidden>
                    <div class="small mt-2" id="bulkPhStatus"></div>
                    <div class="progress mt-2 d-none" id="bulkPhBar" style="height:8px"><div class="progress-bar" style="width:0%"></div></div>
                </form>
                <div class="form-text">Or open a student and upload their photo there.</div>
            </x-cb.card>

            <x-cb.card title="Upload links for students & parents" icon="ri-links-line">
                <p class="small text-muted">Each graduate has a private link. Whoever has it can send the graduation photo and yearbook details — you approve before anything shows.
                    @unless($book->acceptingSubmissions())<br><span class="text-danger">Submissions are closed (see Settings).</span>@endunless</p>
                <form method="POST" action="{{ route('yearbook.admin.bulk', $book->id) }}" onsubmit="return confirm('Send upload links through the portal to every student without a graduation photo (and their parents)?')">@csrf
                    <input type="hidden" name="action" value="notify">
                    @foreach($entries->whereNull('photo')->whereNotNull('student_id') as $e)<input type="hidden" name="ids[]" value="{{ $e->id }}">@endforeach
                    <button class="btn btn-primary w-100 mb-2" @disabled(!$entries->whereNull('photo')->whereNotNull('student_id')->count())><i class="ri-notification-3-line me-1"></i>Send links to all without a photo ({{ $entries->whereNull('photo')->whereNotNull('student_id')->count() }})</button>
                </form>
                <a href="{{ route('yearbook.admin.links', $book->id) }}" class="btn btn-light w-100"><i class="ri-file-excel-2-line me-1"></i>Download links + parent phones (CSV)</a>
                <div class="form-text">Portal links reach the student’s and parents’ portal accounts / app. Use the CSV for bulk SMS or WhatsApp, or the <i class="ri-whatsapp-line"></i> button on each student.</div>
            </x-cb.card>
        </div>

        <div class="col-xl-8">
            <x-cb.card title="Graduates" icon="ri-team-line" :count="$counts['all']">
                <x-slot:tools>
                    <form class="d-flex gap-2" method="GET">
                        <input type="hidden" name="status" value="{{ $status }}">
                        @if($classes->count() > 1)<select name="class" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()"><option value="">All classes</option>@foreach($classes as $c)<option @selected(request('class') === $c)>{{ $c }}</option>@endforeach</select>@endif
                        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name / adm. no" style="width:180px">
                    </form>
                </x-slot:tools>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    {!! $tab(null, 'All', $counts['all']) !!}
                    {!! $tab('pending', 'To approve', $counts['pending']) !!}
                    {!! $tab('nophoto', 'No graduation photo', $counts['all'] - $counts['photo']) !!}
                    {!! $tab('photo', 'Has photo', $counts['photo']) !!}
                    {!! $tab('honours', 'Prefects & awards') !!}
                    {!! $tab('hidden', 'Hidden', $counts['hidden']) !!}
                </div>

                @if($entries->isEmpty())
                    <p class="text-muted mb-0">{{ $counts['all'] ? 'No graduates match.' : 'No graduates yet — find them from their class on the left, or add them manually.' }}</p>
                @else
                    <form method="POST" action="{{ route('yearbook.admin.bulk', $book->id) }}" id="bulkForm" class="d-flex flex-wrap gap-2 align-items-center mb-3 p-2 bg-light rounded">@csrf
                        <label class="mb-0 small"><input type="checkbox" id="selAll"> Select all</label>
                        <select name="action" class="form-select form-select-sm" style="width:auto" required>
                            <option value="">With selected…</option>
                            <option value="approve">Approve what they sent</option>
                            <option value="notify">Send upload link (portal)</option>
                            <option value="publish">Show in yearbook</option>
                            <option value="hide">Hide from yearbook</option>
                            <option value="feature">Mark as featured</option>
                            <option value="unfeature">Remove featured</option>
                            <option value="alumni">Add to alumni network</option>
                            <option value="delete">Remove from yearbook</option>
                        </select>
                        <button class="btn btn-sm btn-primary" onclick="var a=this.form.action.value; return a!=='delete' || confirm('Remove the selected graduates from this yearbook?')">Apply</button>
                        <span class="small text-muted ms-auto" id="selCount"></span>
                    </form>

                    <div class="row g-3">
                        @foreach($entries as $e)
                            @php $wa = isset($phones[$e->student_id]) ? 'https://wa.me/' . $phones[$e->student_id] . '?text=' . rawurlencode(\App\Services\Website\Yearbooks::linkMessage($e, $book)) : null; @endphp
                            <div class="col-sm-6 col-lg-4 col-xxl-3">
                                <div class="border rounded-3 h-100 d-flex flex-column {{ $e->hasPending() ? 'border-warning border-2' : '' }}" style="overflow:hidden">
                                    <div class="position-relative" style="aspect-ratio:4/5;background:#f1f5f9">
                                        @if($e->displayPhoto())<img src="{{ $T($e->displayPhoto()) }}" style="width:100%;height:100%;object-fit:cover;object-position:top" loading="lazy">
                                        @else<div class="d-flex h-100 align-items-center justify-content-center text-muted fs-1 fw-bold">{{ $e->initials() }}</div>@endif
                                        <input type="checkbox" class="form-check-input sel position-absolute" style="top:8px;left:8px;width:20px;height:20px" name="ids[]" value="{{ $e->id }}" form="bulkForm">
                                        <div class="position-absolute d-flex flex-column gap-1 align-items-end" style="top:8px;right:8px">
                                            @if(!$e->photo)<span class="badge {{ $e->portal_photo ? 'bg-secondary' : 'bg-danger' }}">{{ $e->portal_photo ? 'Passport only' : 'No photo' }}</span>@endif
                                            @unless($e->published)<span class="badge bg-dark">Hidden</span>@endunless
                                            @if($e->featured)<span class="badge bg-info">Featured</span>@endif
                                            @if($e->link_sent_at)<span class="badge bg-light text-dark" title="Link sent {{ $e->link_sent_at->format('d M, H:i') }}"><i class="ri-send-plane-line"></i> Sent</span>@endif
                                        </div>
                                        @if($e->hasPending() && $e->pending_photo)
                                            <a href="{{ $F($e->pending_photo) }}" target="_blank" class="position-absolute border border-3 border-warning rounded-2" style="bottom:8px;right:8px;width:38%;aspect-ratio:4/5;overflow:hidden;background:#fff" title="New photo sent — click to see it full size">
                                                <img src="{{ $T($e->pending_photo) }}" style="width:100%;height:100%;object-fit:cover"></a>
                                        @endif
                                    </div>
                                    <div class="p-2 d-flex flex-column flex-grow-1">
                                        <a href="{{ route('yearbook.admin.entry', [$book->id, $e->id]) }}" class="fw-semibold text-reset lh-sm">{{ $e->name }}</a>
                                        <div class="small text-muted">{{ collect([$e->class_name, $e->admission_no])->filter()->implode(' · ') }}</div>
                                        @if($e->position || $e->awards)<div class="small text-primary lh-sm mt-1">{{ collect([$e->position])->merge($e->awardList())->filter()->implode(' · ') }}</div>@endif
                                        @if($e->hasPending())
                                            <div class="small mt-1 p-1 rounded" style="background:#fff7e6"><i class="ri-time-line"></i> Sent by {{ $e->pending_by }} · {{ $e->pending_at->diffForHumans() }}
                                                @if($e->pending)<span class="text-muted d-block">{{ implode(', ', array_map(fn ($k) => str_replace('_', ' ', $k), array_keys((array) $e->pending))) }}</span>@endif
                                            </div>
                                            <div class="d-flex gap-1 mt-1">
                                                <form method="POST" action="{{ route('yearbook.admin.entry.approve', [$book->id, $e->id]) }}" class="flex-fill">@csrf<button class="btn btn-sm btn-success w-100"><i class="ri-check-line"></i> Approve</button></form>
                                                <form method="POST" action="{{ route('yearbook.admin.entry.reject', [$book->id, $e->id]) }}" onsubmit="return confirm('Reject what was sent?')">@csrf<button class="btn btn-sm btn-outline-danger"><i class="ri-close-line"></i></button></form>
                                            </div>
                                        @endif
                                        <div class="d-flex gap-1 mt-auto pt-2">
                                            <a href="{{ route('yearbook.admin.entry', [$book->id, $e->id]) }}" class="btn btn-sm btn-light flex-fill"><i class="ri-edit-line"></i> Edit</a>
                                            <button type="button" class="btn btn-sm btn-light copy-link" data-url="{{ $e->uploadUrl() }}" title="Copy upload link"><i class="ri-link"></i></button>
                                            @if($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm btn-light text-success" title="Send link to parent on WhatsApp"><i class="ri-whatsapp-line"></i></a>@endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection

@push('scripts')
<script>
(function () {
    var CSRF = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
    function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }

    // Find students in the chosen classes
    document.getElementById('candLoad').addEventListener('click', function () {
        var btn = this, ids = [].map.call(document.querySelectorAll('.cand-class:checked'), function (c) { return c.value; });
        if (!ids.length) { alert('Tick the graduating class(es) first.'); return; }
        var q = new URLSearchParams(); q.append('session_id', document.getElementById('candSession').value);
        ids.forEach(function (i) { q.append('class_ids[]', i); });
        btn.disabled = true; btn.innerHTML = 'Searching…';
        fetch('{{ route('yearbook.admin.candidates', $book->id) }}?' + q.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function (rows) {
                var list = document.getElementById('candList'), html = '', fresh = 0;
                rows.forEach(function (s) {
                    if (!s.in_book) fresh++;
                    html += '<div class="form-check small"><input class="form-check-input cand" type="checkbox" name="students[]" value="' + s.id + ':' + s.class_id + '" id="cs' + s.id + '" ' + (s.in_book ? 'disabled' : 'checked') + '>'
                        + '<label class="form-check-label" for="cs' + s.id + '">' + esc(s.name) + ' <span class="text-muted">' + esc(s.class) + (s.admission_no ? ' · ' + esc(s.admission_no) : '') + '</span>'
                        + (s.in_book ? ' <span class="badge bg-success-subtle text-success">in yearbook</span>' : '') + (s.status === 'Graduated' ? ' <span class="badge bg-info-subtle text-info">Graduated</span>' : '') + '</label></div>';
                });
                list.innerHTML = html || '<div class="text-muted small">No students found in those classes for that session.</div>';
                document.getElementById('candCount').textContent = rows.length + ' found · ' + fresh + ' new';
                document.getElementById('importForm').classList.remove('d-none');
            })
            .catch(function (e) { alert('Could not load students: ' + e.message); })
            .finally(function () { btn.disabled = false; btn.innerHTML = '<i class="ri-search-line me-1"></i>Find students'; });
    });
    document.getElementById('candAll').addEventListener('change', function () {
        var on = this.checked; document.querySelectorAll('.cand:not(:disabled)').forEach(function (c) { c.checked = on; });
    });

    // Bulk selection
    var selAll = document.getElementById('selAll');
    function count() { var n = document.querySelectorAll('.sel:checked').length, el = document.getElementById('selCount'); if (el) el.textContent = n ? n + ' selected' : ''; }
    if (selAll) selAll.addEventListener('change', function () { document.querySelectorAll('.sel').forEach(function (c) { c.checked = selAll.checked; }); count(); });
    document.querySelectorAll('.sel').forEach(function (c) { c.addEventListener('change', count); });

    // Copy upload link
    document.querySelectorAll('.copy-link').forEach(function (b) {
        b.addEventListener('click', function () {
            var url = b.dataset.url, done = function () { b.innerHTML = '<i class="ri-check-line"></i>'; setTimeout(function () { b.innerHTML = '<i class="ri-link"></i>'; }, 1500); };
            if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(url).then(done); else { window.prompt('Copy this upload link:', url); }
        });
    });

    // Many photos: upload in batches of 15 so large sets don't hit the server's upload limit
    var inp = document.getElementById('bulkPh');
    inp.addEventListener('change', function () {
        var files = [].slice.call(inp.files), total = files.length, done = 0, matched = 0, missed = [];
        if (!total) return;
        var bar = document.getElementById('bulkPhBar'), st = document.getElementById('bulkPhStatus');
        bar.classList.remove('d-none');
        function next() {
            if (!files.length) {
                st.innerHTML = '<b>' + matched + '</b> of ' + total + ' photos matched.' + (missed.length ? '<br><span class="text-danger">Not matched: ' + esc(missed.join(', ')) + '</span>' : '') + ' Reloading…';
                setTimeout(function () { location.reload(); }, missed.length ? 4000 : 800); return;
            }
            var fd = new FormData(), batch = files.splice(0, 15);
            fd.append('_token', CSRF); batch.forEach(function (f) { fd.append('photos[]', f); });
            st.textContent = 'Uploading ' + (done + 1) + '–' + (done + batch.length) + ' of ' + total + '…';
            fetch('{{ route('yearbook.admin.photos', $book->id) }}', { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
                .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(function (j) { matched += j.matched; missed = missed.concat(j.missed || []); })
                .catch(function (e) { missed = missed.concat(batch.map(function (f) { return f.name + ' (' + e.message + ')'; })); })
                .finally(function () { done += batch.length; bar.firstElementChild.style.width = Math.round(done / total * 100) + '%'; next(); });
        }
        next();
    });
})();
</script>
@endpush
