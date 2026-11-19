@extends('website.layout', ['pageTitle' => 'Yearbooks', 'metaDescription' => 'Graduating classes, their photos, stories and testimonials — find your classmates and the year they graduated.'])

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $T = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl(\App\Services\Website\Yearbooks::thumb($p));
    $user = auth()->user();
@endphp

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ \App\Services\Website\WebsiteContent::homeUrl() }}">Home</a><span>/</span><span style="color:#fff">Yearbooks</span></nav>
            <h1 class="reveal">Yearbooks</h1>
            <p class="reveal d1">Every graduating set, their faces, stories and farewell words. Find your classmates and the year they graduated.</p>
            <form class="yb-search reveal d2" method="GET" action="{{ route('site.yearbooks') }}">
                <input type="search" name="q" value="{{ $q }}" placeholder="Search a classmate by name…" aria-label="Search a classmate">
                <button class="btn btn-accent"><i class="ri-search-line"></i> Search</button>
            </form>
        </div>
    </section>

    <section class="section">
        <div class="container">
            @foreach($mine as $m)
                <div class="yb-mine reveal">
                    @if($m->displayPhoto())<img src="{{ $T($m->displayPhoto()) }}" alt="">@else<span class="ini">{{ $m->initials() }}</span>@endif
                    <div style="flex:1;min-width:220px">
                        <b style="font-family:var(--head);color:var(--ink)">{{ $m->name }} — {{ $m->yearbook->title }}</b>
                        <div class="muted" style="font-size:.95rem">
                            @if($m->hasPending()) Your photo and details were received and are waiting for the school's approval.
                            @elseif(!$m->photo) Your graduation photo has not been added yet.
                            @else Your yearbook page is ready. @endif
                        </div>
                    </div>
                    @if($m->yearbook->acceptingSubmissions())<a class="btn btn-primary btn-sm" href="{{ $m->uploadUrl() }}"><i class="ri-upload-2-line"></i> {{ $m->photo ? 'Update my photo & details' : 'Upload graduation photo' }}</a>@endif
                    @if($m->published && $m->yearbook->visibleTo($user))<a class="btn btn-outline btn-sm" href="{{ route('site.yearbook.entry', [$m->yearbook->slug, $m->id]) }}">View my page</a>@endif
                </div>
            @endforeach

            @if($q !== '')
                <div class="section-head" style="margin:0 0 22px;max-width:none">
                    <span class="eyebrow">Search</span>
                    <h3 style="margin:0">{{ $results->count() }} {{ \Illuminate\Support\Str::plural('result', $results->count()) }} for “{{ $q }}”</h3>
                    @if(mb_strlen($q) < 2)<p class="muted">Type at least two letters.</p>@endif
                </div>
                <div class="yb-results">
                    @foreach($results as $r)
                        <a class="yb-honour" href="{{ route('site.yearbook.entry', [$r->yearbook->slug, $r->id]) }}">
                            @if($r->displayPhoto())<img src="{{ $T($r->displayPhoto()) }}" alt="" loading="lazy">@else<span class="ini">{{ $r->initials() }}</span>@endif
                            <div><b>{{ $r->name }}</b><div class="aw">Class of {{ $r->yearbook->graduation_year }}{{ $r->class_name ? ' · ' . $r->class_name : '' }}</div></div>
                        </a>
                    @endforeach
                </div>
            @endif

            @if($books->count())
                <div class="yb-books">
                    @foreach($books as $i => $b)
                        <a class="yb-book reveal d{{ $i % 4 + 1 }}" href="{{ route('site.yearbook', $b->slug) }}">
                            <div class="cover">
                                @if($b->cover)<img src="{{ $F($b->cover) }}" alt="{{ $b->title }}" loading="lazy">@elseif($b->class_photo)<img src="{{ $F($b->class_photo) }}" alt="{{ $b->title }}" loading="lazy">@endif
                                <div class="shade"></div>
                                @if($b->isPortalOnly())<span class="lock"><i class="ri-lock-line"></i> Portal</span>@endif
                                <div class="txt"><div class="lbl">Class of</div><div class="yr">{{ $b->graduation_year }}</div>@if($b->class_label)<div class="lbl" style="margin-top:4px">{{ $b->class_label }}</div>@endif</div>
                            </div>
                            <h3>{{ $b->title }}</h3>
                            <small>{{ $b->graduates_count }} {{ \Illuminate\Support\Str::plural('graduate', $b->graduates_count) }}@if($b->theme) · {{ \Illuminate\Support\Str::limit($b->theme, 40) }}@endif</small>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="empty"><i class="ri-book-open-line"></i>Our yearbooks are being prepared. Please check back soon.</div>
            @endif
        </div>
    </section>

    <section class="section" style="padding-top:0">
        <div class="container">
            <div class="cta-band reveal">
                <div>
                    <h2>Once a student, always family.</h2>
                    <p>{{ $alumniCount ? number_format($alumniCount) . ' old students are already in our alumni network.' : 'Join our alumni network and reconnect with your set.' }}</p>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <a href="{{ route('site.alumni') }}" class="btn btn-light">Meet our alumni</a>
                    <a href="{{ route('site.alumni.join') }}" class="btn btn-accent">Join the network</a>
                </div>
            </div>
        </div>
    </section>
@endsection
