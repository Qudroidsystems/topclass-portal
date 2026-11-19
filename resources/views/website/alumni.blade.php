@extends('website.layout', ['pageTitle' => 'Alumni', 'metaDescription' => 'Our alumni network — old students, the sets they graduated with and what they are doing now.'])

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $T = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl(\App\Services\Website\Yearbooks::thumb($p));
    $filtered = request()->hasAny(['q', 'year', 'industry', 'mentors']);
@endphp

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ \App\Services\Website\WebsiteContent::homeUrl() }}">Home</a><span>/</span><span style="color:#fff">Alumni</span></nav>
            <h1 class="reveal">Our Alumni</h1>
            <p class="reveal d1">Once a student, always family. Reconnect with your set, see where our old students are today, and give back to the next generation.</p>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:26px" class="reveal d2">
                <a href="{{ route('site.alumni.join') }}" class="btn btn-accent"><i class="ri-user-add-line"></i> Join the alumni network</a>
                <a href="{{ route('site.yearbooks') }}" class="btn btn-light"><i class="ri-book-open-line"></i> Yearbooks</a>
            </div>
        </div>
    </section>

    @if($stats['members'])
        <section class="stats">
            <div class="container grid">
                <div class="stat"><b>{{ number_format($stats['members']) }}</b><span>Alumni members</span></div>
                <div class="stat"><b>{{ $stats['sets'] }}</b><span>Graduating sets</span></div>
                @if($stats['mentors'])<div class="stat"><b>{{ $stats['mentors'] }}</b><span>Mentors for students</span></div>@endif
            </div>
        </section>
    @endif

    @if(!$filtered && $featured->count())
        <section class="section cream">
            <div class="container">
                <div class="section-head center reveal"><span class="eyebrow">Alumni spotlight</span><h2>Making us proud</h2></div>
                <div class="grid-{{ min(3, $featured->count()) }}" style="gap:24px">
                    @foreach($featured as $i => $a)
                        <a class="al-spot reveal d{{ $i + 1 }}" href="{{ route('site.alumnus', $a->id) }}" style="color:inherit">
                            @if($a->displayPhoto())<img src="{{ $T($a->displayPhoto()) }}" alt="{{ $a->name }}">@else<span class="ini">{{ $a->initials() }}</span>@endif
                            <div>
                                <span class="eyebrow" style="margin-bottom:6px">Class of {{ $a->graduation_year }}</span>
                                <h3 style="margin:0 0 4px">{{ $a->name }}</h3>
                                @if($a->headline())<div style="color:var(--primary);font-family:var(--head);font-size:.95rem">{{ $a->headline() }}</div>@endif
                                <p style="font-size:.95rem;margin:8px 0 0">{{ \Illuminate\Support\Str::limit($a->bio, 150) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="container">
            <div class="section-head center reveal"><span class="eyebrow">Directory</span><h2>Find an old student</h2></div>
            <form class="al-filter" method="GET" action="{{ route('site.alumni') }}">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, profession, company or city" aria-label="Search alumni">
                <select name="year" aria-label="Class of"><option value="">Any year</option>@foreach($years as $y)<option value="{{ $y }}" @selected(request('year') == $y)>Class of {{ $y }}</option>@endforeach</select>
                <select name="industry" aria-label="Field"><option value="">Any field</option>@foreach($industries as $ind)<option @selected(request('industry') === $ind)>{{ $ind }}</option>@endforeach</select>
                <button class="btn btn-primary"><i class="ri-search-line"></i> Search</button>
            </form>
            <div class="chips" style="justify-content:flex-start;margin-top:-16px">
                <a class="chip {{ request()->boolean('mentors') ? 'on' : '' }}" href="{{ route('site.alumni', array_merge(request()->except('page', 'mentors'), request()->boolean('mentors') ? [] : ['mentors' => 1])) }}"><i class="ri-hand-heart-line"></i> Mentors only</a>
                @if($filtered)<a class="chip" href="{{ route('site.alumni') }}">Clear filters</a>@endif
                @if(request('year') && isset($bookYears[request('year')]))<a class="chip" href="{{ route('site.yearbook', $bookYears[request('year')]) }}"><i class="ri-book-open-line"></i> {{ request('year') }} yearbook</a>@endif
            </div>

            @if($alumni->count())
                <div class="al-grid">
                    @foreach($alumni as $i => $a)
                        <a class="al-card reveal d{{ $i % 4 + 1 }}" href="{{ route('site.alumnus', $a->id) }}">
                            <div class="av">@if($a->displayPhoto())<img src="{{ $T($a->displayPhoto()) }}" alt="{{ $a->name }}" loading="lazy">@else{{ $a->initials() }}@endif</div>
                            <h4>{{ $a->name }}</h4>
                            <div class="set">Class of {{ $a->graduation_year ?: '—' }}</div>
                            @if($a->headline())<div class="job">{{ $a->headline() }}</div>@elseif($a->higher_institution)<div class="job">{{ $a->higher_institution }}</div>@endif
                            @if($a->location)<div class="loc"><i class="ri-map-pin-line"></i> {{ $a->location }}</div>@endif
                            @if($a->mentor)<span class="mentor"><i class="ri-hand-heart-line"></i> Mentor</span>@endif
                        </a>
                    @endforeach
                </div>
                @include('website.partials.pager', ['pg' => $alumni])
            @else
                <div class="empty"><i class="ri-user-search-line"></i>{{ $filtered ? 'No alumni match your search.' : 'Our alumni directory is just getting started — be the first to join!' }}</div>
            @endif
        </div>
    </section>

    @if($news->count())
        <section class="section soft">
            <div class="container">
                <div class="section-head center reveal"><span class="eyebrow">Alumni news &amp; events</span><h2>What’s happening</h2></div>
                <div class="grid-3">@foreach($news as $i => $post)@include('website.partials.post-card', ['p' => $post, 'delay' => $i % 3 + 1])@endforeach</div>
            </div>
        </section>
    @endif

    <section class="section" @if($news->count()) style="padding-top:clamp(64px, 9vw, 112px)" @else style="padding-top:0" @endif>
        <div class="container">
            <div class="cta-band reveal">
                <div><h2>Are you an old student?</h2><p>Add your profile, find your classmates and offer to mentor current students.</p></div>
                <a href="{{ route('site.alumni.join') }}" class="btn btn-light">Join the alumni network</a>
            </div>
        </div>
    </section>
@endsection
