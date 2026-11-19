@extends('website.layout', ['pageTitle' => $book->title, 'metaDescription' => 'Yearbook of the Class of ' . $book->graduation_year . ($book->theme ? ' — ' . $book->theme : ''), 'ogImage' => $book->cover ? \App\Services\Website\WebsiteContent::fileUrl($book->cover) : null])

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $T = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl(\App\Services\Website\Yearbooks::thumb($p));
    $heroImg = $book->cover ?: $book->class_photo;
@endphp

@section('content')
    <section class="yb-hero" @if($heroImg) style="background-image:url('{{ $F($heroImg) }}')" @endif>
        <div class="container">
            <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ \App\Services\Website\WebsiteContent::homeUrl() }}">Home</a><span>/</span><a href="{{ route('site.yearbooks') }}">Yearbooks</a><span>/</span><span style="color:#fff">{{ $book->graduation_year }}</span></nav>
            @unless($book->published)<p style="margin:14px 0 0"><span style="background:#f59e0b;color:#111;border-radius:99px;padding:4px 12px;font-size:.85rem">Preview — not yet published</span></p>@endunless
            <div class="big reveal"><span>Class of</span>{{ $book->graduation_year }}</div>
            <h1 class="reveal d1">{{ $book->title }}</h1>
            @if($book->theme)<p class="theme reveal d1">“{{ $book->theme }}”</p>@endif
            <div class="facts reveal d2">
                <div><b>{{ $entries->count() }}</b><span>Graduates</span></div>
                @if($classes->count() > 1)<div><b>{{ $classes->count() }}</b><span>Classes</span></div>@endif
                @if($honours->count())<div><b>{{ $honours->count() }}</b><span>Honours</span></div>@endif
            </div>
            <div class="acts reveal d2">
                <a href="#graduates" class="btn btn-primary"><i class="ri-team-line"></i> Meet the graduates</a>
                <a href="{{ route('site.yearbook.print', $book->slug) }}" target="_blank" class="btn btn-light"><i class="ri-printer-line"></i> Print / save as PDF</a>
                @if($canManage)<a href="{{ route('yearbook.admin.show', $book->id) }}" class="btn btn-light"><i class="ri-edit-line"></i> Manage</a>@endif
            </div>
        </div>
    </section>

    @if($book->description || $book->class_photo)
        <section class="section">
            <div class="container">
                @if($book->description)<div class="section-head center reveal"><span class="eyebrow">The set</span><p class="lead">{!! nl2br(e($book->description)) !!}</p></div>@endif
                @if($book->class_photo)
                    <a href="{{ $F($book->class_photo) }}" data-lightbox="classphoto" data-caption="{{ $book->title }}" class="yb-classphoto reveal" style="display:block"><img src="{{ $F($book->class_photo) }}" alt="Class photo — {{ $book->title }}"></a>
                @endif
            </div>
        </section>
    @endif

    @if($book->principal_message)
        <section class="section cream">
            <div class="container">
                <div class="yb-letter">
                    <div class="who reveal">
                        @if($book->principal_photo)<img src="{{ $F($book->principal_photo) }}" alt="{{ $book->principal_name }}">@endif
                        @if($book->principal_name)<b>{{ $book->principal_name }}</b><small>Principal</small>@endif
                    </div>
                    <div class="reveal d1">
                        <span class="eyebrow">A word to the graduates</span>
                        <h2>From the Principal’s desk</h2>
                        <div class="prose">{!! $book->principal_message !!}</div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($honours->count())
        <section class="section">
            <div class="container">
                <div class="section-head center reveal"><span class="eyebrow">Honours &amp; leadership</span><h2>Prefects, prizes and superlatives</h2></div>
                <div class="yb-honours">
                    @foreach($honours as $i => $h)
                        <a class="yb-honour reveal d{{ $i % 4 + 1 }}" href="{{ route('site.yearbook.entry', [$book->slug, $h->id]) }}">
                            @if($h->displayPhoto())<img src="{{ $T($h->displayPhoto()) }}" alt="" loading="lazy">@else<span class="ini">{{ $h->initials() }}</span>@endif
                            <div><b>{{ $h->name }}</b><div class="aw">{{ collect([$h->position])->merge($h->awardList())->filter()->implode(' · ') }}</div></div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section soft" id="graduates">
        <div class="container">
            <div class="section-head center reveal"><span class="eyebrow">The graduates</span><h2>Class of {{ $book->graduation_year }}</h2></div>
            @if($entries->count())
                <div class="yb-tools">
                    <div class="chips" role="tablist">
                        @if($classes->count() > 1)
                            <button type="button" class="chip on" data-class="">All</button>
                            @foreach($classes as $c)<button type="button" class="chip" data-class="{{ $c }}">{{ $c }}</button>@endforeach
                        @endif
                    </div>
                    <input type="search" id="ybFind" placeholder="Find a graduate…" aria-label="Find a graduate">
                </div>
                <div class="yb-grid" id="ybGrid">
                    @foreach($entries as $e)
                        <a class="yb-card" href="{{ route('site.yearbook.entry', [$book->slug, $e->id]) }}" data-class="{{ $e->class_name }}" data-name="{{ mb_strtolower($e->name . ' ' . $e->nickname) }}">
                            <div class="ph">
                                @if($e->displayPhoto())<img src="{{ $T($e->displayPhoto()) }}" alt="{{ $e->name }}" loading="lazy">@else<span class="ini">{{ $e->initials() }}</span>@endif
                                @if($e->position)<span class="badge">{{ $e->position }}</span>@endif
                            </div>
                            <h4>{{ $e->name }}</h4>
                            @if($e->nickname)<div class="nick">“{{ $e->nickname }}”</div>@endif
                            @if($e->quote)<div class="q">{{ $e->quote }}</div>@elseif($e->ambition)<div class="q">Future {{ $e->ambition }}</div>@endif
                        </a>
                    @endforeach
                </div>
                <p class="empty" id="ybNone" style="display:none"><i class="ri-user-search-line"></i>No graduate matches that name.</p>
            @else
                <div class="empty"><i class="ri-team-line"></i>The graduates will appear here soon.</div>
            @endif
        </div>
    </section>

    @if($album && $album->photos->count())
        <section class="section">
            <div class="container">
                <div class="section-head center reveal"><span class="eyebrow">Memories</span><h2>{{ $album->title }}</h2></div>
                <div class="masonry">
                    @foreach($album->photos->take(12) as $p)
                        <a href="{{ $F($p->path) }}" data-lightbox="memories" data-caption="{{ $p->caption }}" class="reveal"><img src="{{ $F($p->thumb ?: $p->path) }}" alt="{{ $p->caption }}" loading="lazy"></a>
                    @endforeach
                </div>
                @if($album->photos->count() > 12)<p class="center" style="margin-top:26px"><a class="btn btn-outline" href="{{ route('site.album', $album->slug) }}">See all {{ $album->photos->count() }} photos</a></p>@endif
            </div>
        </section>
    @endif

    <section class="section" style="padding-top:{{ $album && $album->photos->count() ? '0' : '' }}">
        <div class="container">
            <div class="cta-band reveal">
                <div><h2>Graduated with this set?</h2><p>Join the alumni network, tell us what you are doing now and stay connected with your classmates.</p></div>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <a href="{{ route('site.alumni', ['year' => $book->graduation_year]) }}" class="btn btn-light">Alumni of {{ $book->graduation_year }}</a>
                    <a href="{{ route('site.alumni.join') }}" class="btn btn-accent">Join the network</a>
                </div>
            </div>
            @if($others->count())
                <div class="chips" style="margin:40px 0 0">
                    <span class="muted" style="align-self:center;margin-right:6px">Other yearbooks:</span>
                    @foreach($others as $o)<a class="chip" href="{{ route('site.yearbook', $o->slug) }}">{{ $o->graduation_year }}</a>@endforeach
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    var grid = document.getElementById('ybGrid'); if (!grid) return;
    var cards = [].slice.call(grid.children), find = document.getElementById('ybFind'), none = document.getElementById('ybNone'), cls = '';
    function apply() {
        var q = (find.value || '').trim().toLowerCase(), shown = 0;
        cards.forEach(function (c) {
            var ok = (!cls || c.dataset.class === cls) && (!q || c.dataset.name.indexOf(q) > -1);
            c.style.display = ok ? '' : 'none'; if (ok) shown++;
        });
        none.style.display = shown ? 'none' : '';
    }
    find.addEventListener('input', apply);
    [].forEach.call(document.querySelectorAll('.yb-tools .chip'), function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('.yb-tools .chip').forEach(function (x) { x.classList.remove('on'); });
            b.classList.add('on'); cls = b.dataset.class; apply();
        });
    });
})();
</script>
@endpush
