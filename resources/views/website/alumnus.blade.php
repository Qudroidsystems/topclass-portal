@extends('website.layout', ['pageTitle' => $a->name . ' — Alumni', 'metaDescription' => trim($a->name . ', Class of ' . $a->graduation_year . '. ' . $a->headline())])

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $T = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl(\App\Services\Website\Yearbooks::thumb($p));
    $facts = array_filter([
        'Class of' => $a->graduation_year, 'Set / class' => $a->class_set, 'Occupation' => $a->occupation, 'Organisation' => $a->organisation,
        'Field' => $a->industry, 'Higher institution' => $a->higher_institution, 'Location' => $a->location,
    ]);
    $grad = $a->entry && $a->entry->displayPhoto() && $a->photo ? $a->entry->displayPhoto() : null;
@endphp

@section('content')
    @include('website.partials.page-hero', ['title' => $a->name, 'lead' => 'Class of ' . $a->graduation_year . ($a->headline() ? ' · ' . $a->headline() : ''), 'crumbs' => [['Alumni', route('site.alumni')], [$a->name, null]]])

    <section class="section">
        <div class="container">
            <div class="yb-profile">
                <div class="portrait reveal">
                    <div class="ph">@if($a->displayPhoto())<img src="{{ $F($a->displayPhoto()) }}" alt="{{ $a->name }}">@else<span class="ini">{{ $a->initials() }}</span>@endif</div>
                    @if($grad)
                        <div style="display:flex;gap:12px;align-items:center;margin-top:14px">
                            <img src="{{ $T($grad) }}" alt="" style="width:64px;height:80px;object-fit:cover;border-radius:12px">
                            <small class="muted">On graduation day, {{ $a->graduation_year }}</small>
                        </div>
                    @endif
                </div>
                <div class="reveal d1">
                    <span class="eyebrow">Alumni · Class of {{ $a->graduation_year }}</span>
                    <h1>{{ $a->name }}</h1>
                    @if($a->maiden_name)<div class="nick">née {{ $a->maiden_name }}</div>@endif
                    <div class="yb-badges">
                        @if($a->mentor)<span><i class="ri-hand-heart-line"></i> Mentor for current students</span>@endif
                        @if($a->featured)<span class="gold"><i class="ri-star-line"></i> Alumni spotlight</span>@endif
                    </div>
                    @if($facts)<div class="yb-facts">@foreach($facts as $k => $v)<div><small>{{ $k }}</small><b>{{ $v }}</b></div>@endforeach</div>@endif
                    @if($a->bio)<div class="yb-block"><h3>About</h3><p>{!! nl2br(e($a->bio)) !!}</p></div>@endif
                    @if($a->message)<blockquote class="yb-quote">“{{ $a->message }}”<br><small style="font-family:var(--text);font-style:normal;font-size:.9rem;color:var(--muted)">— a message to current students</small></blockquote>@endif

                    @if($a->show_contact && ($a->email || $a->phone || $a->linkedin))
                        <div class="al-contact">
                            @if($a->email)<a class="btn btn-outline btn-sm" href="mailto:{{ $a->email }}"><i class="ri-mail-line"></i> Email</a>@endif
                            @if($a->phone)<a class="btn btn-outline btn-sm" href="tel:{{ preg_replace('/\s+/', '', $a->phone) }}"><i class="ri-phone-line"></i> {{ $a->phone }}</a>@endif
                            @if($a->linkedin)<a class="btn btn-outline btn-sm" href="{{ $a->linkedin }}" target="_blank" rel="noopener nofollow"><i class="ri-linkedin-box-line"></i> LinkedIn</a>@endif
                        </div>
                    @endif

                    <div class="yb-nav">
                        <a class="link-arrow" href="{{ route('site.alumni') }}"><i class="ri-arrow-left-line"></i> All alumni</a>
                        @if($book)<a class="link-arrow" href="{{ $a->entry && $a->entry->yearbook_id === $book->id && $a->entry->published ? route('site.yearbook.entry', [$book->slug, $a->entry->id]) : route('site.yearbook', $book->slug) }}">{{ $book->graduation_year }} yearbook <i class="ri-arrow-right-line"></i></a>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($mates->count())
        <section class="section soft">
            <div class="container">
                <div class="section-head center reveal"><span class="eyebrow">Same set</span><h2>Class of {{ $a->graduation_year }}</h2></div>
                <div class="al-grid">
                    @foreach($mates as $i => $m)
                        <a class="al-card reveal d{{ $i % 4 + 1 }}" href="{{ route('site.alumnus', $m->id) }}">
                            <div class="av">@if($m->displayPhoto())<img src="{{ $T($m->displayPhoto()) }}" alt="" loading="lazy">@else{{ $m->initials() }}@endif</div>
                            <h4>{{ $m->name }}</h4>
                            @if($m->headline())<div class="job">{{ $m->headline() }}</div>@endif
                        </a>
                    @endforeach
                </div>
                <p class="center" style="margin-top:30px"><a class="btn btn-outline" href="{{ route('site.alumni', ['year' => $a->graduation_year]) }}">Everyone from {{ $a->graduation_year }}</a></p>
            </div>
        </section>
    @endif
@endsection
