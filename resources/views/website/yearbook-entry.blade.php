@extends('website.layout', ['pageTitle' => $e->name . ' — Class of ' . $book->graduation_year, 'metaDescription' => $e->quote ?: ($e->name . ', ' . $book->title), 'ogImage' => $e->displayPhoto() ? \App\Services\Website\WebsiteContent::fileUrl($e->displayPhoto()) : null])

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $facts = array_filter([
        'Class' => $e->class_name, 'House' => $e->house, 'Future ambition' => $e->ambition, 'Favourite subject' => $e->favourite_subject,
        'Hobbies' => $e->hobbies, 'Next step' => $e->next_step, 'Admission no.' => $signedIn ? $e->admission_no : null,
    ]);
@endphp

@section('content')
    @include('website.partials.page-hero', ['title' => $book->title, 'lead' => 'Class of ' . $book->graduation_year, 'crumbs' => [['Yearbooks', route('site.yearbooks')], [$book->graduation_year, route('site.yearbook', $book->slug)], [$e->name, null]]])

    <section class="section">
        <div class="container">
            <div class="yb-profile">
                <div class="portrait reveal">
                    <div class="ph">
                        @if($e->displayPhoto())<a href="{{ $F($e->displayPhoto()) }}" data-lightbox="me" data-caption="{{ $e->name }}"><img src="{{ $F($e->displayPhoto()) }}" alt="{{ $e->name }}"></a>
                        @else<span class="ini">{{ $e->initials() }}</span>@endif
                    </div>
                </div>
                <div class="reveal d1">
                    <span class="eyebrow">Class of {{ $book->graduation_year }}</span>
                    <h1>{{ $e->name }}</h1>
                    @if($e->nickname)<div class="nick">a.k.a. “{{ $e->nickname }}”</div>@endif

                    @if($e->position || $e->awards)
                        <div class="yb-badges">
                            @if($e->position)<span><i class="ri-shield-star-line"></i> {{ $e->position }}</span>@endif
                            @foreach($e->awardList() as $aw)<span class="gold"><i class="ri-trophy-line"></i> {{ $aw }}</span>@endforeach
                        </div>
                    @endif

                    @if($e->quote)<blockquote class="yb-quote">“{{ $e->quote }}”</blockquote>@endif

                    @if($facts)
                        <div class="yb-facts">@foreach($facts as $k => $v)<div><small>{{ $k }}</small><b>{{ $v }}</b></div>@endforeach</div>
                    @endif

                    @if($e->testimonial)
                        <div class="yb-block"><h3>My time in school</h3><p>{!! nl2br(e($e->testimonial)) !!}</p></div>
                    @endif
                    @if($e->best_memory)
                        <div class="yb-block"><h3>Best memory</h3><p>{!! nl2br(e($e->best_memory)) !!}</p></div>
                    @endif
                    @if($e->school_remark)
                        <div class="yb-block remark"><h3>The school’s testimonial</h3><p style="margin:0">{!! nl2br(e($e->school_remark)) !!}</p></div>
                    @endif

                    @if($alumnus)
                        <div class="yb-note" style="margin-top:28px"><i class="ri-graduation-cap-line"></i> <b>Now:</b> {{ $alumnus->headline() ?: 'In our alumni network' }}{{ $alumnus->location ? ' · ' . $alumnus->location : '' }} — <a href="{{ route('site.alumnus', $alumnus->id) }}">see alumni profile</a></div>
                    @endif

                    <div class="yb-nav">
                        @if($prev)<a class="link-arrow" href="{{ route('site.yearbook.entry', [$book->slug, $prev->id]) }}"><i class="ri-arrow-left-line"></i> {{ $prev->name }}</a>@else<span></span>@endif
                        <a href="{{ route('site.yearbook', $book->slug) }}#graduates" class="muted">All graduates</a>
                        @if($next)<a class="link-arrow" href="{{ route('site.yearbook.entry', [$book->slug, $next->id]) }}">{{ $next->name }} <i class="ri-arrow-right-line"></i></a>@else<span></span>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
