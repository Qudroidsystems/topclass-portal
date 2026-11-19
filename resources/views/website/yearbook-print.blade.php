@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $primary = ($site['primary_color'] ?? '') ?: '#AE152D';
    $secondary = ($site['secondary_color'] ?? '') ?: '#0B3D2E';
    $honours = $entries->filter(fn ($e) => $e->position || $e->awards);
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $book->title }} — {{ $school->name }}</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Source+Sans+3:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<style>
    :root { --p: {{ $primary }}; --s: {{ $secondary }}; }
    * { box-sizing: border-box; }
    @page { size: A4; margin: 12mm; }
    body { margin: 0; font-family: 'Source Sans 3', sans-serif; color: #1f2937; background: #e5e7eb; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    h1, h2, h3, h4 { font-family: 'Outfit', 'Segoe UI', sans-serif; margin: 0; color: #111827; }
    .bar { position: sticky; top: 0; z-index: 5; background: #111827; color: #fff; padding: 10px 16px; display: flex; gap: 12px; align-items: center; justify-content: center; font-family: 'Outfit', 'Segoe UI', sans-serif; }
    .bar button, .bar a { background: var(--p); color: #fff; border: 0; border-radius: 99px; padding: 8px 18px; font: inherit; cursor: pointer; text-decoration: none; }
    .bar a { background: transparent; border: 1px solid rgba(255,255,255,.4); }
    .page { width: 210mm; min-height: 297mm; margin: 14px auto; background: #fff; padding: 14mm; position: relative; page-break-after: always; break-after: page; overflow: hidden; box-shadow: 0 6px 30px rgba(0,0,0,.15); }
    .page:last-child { page-break-after: auto; break-after: auto; }
    .cover { padding: 0; background: var(--s) center/cover no-repeat; color: #fff; display: flex; flex-direction: column; justify-content: flex-end; }
    .cover::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,.15), rgba(0,0,0,.82)); }
    .cover .in { position: relative; z-index: 1; padding: 22mm 18mm; }
    .cover .logo { position: absolute; z-index: 1; top: 16mm; left: 18mm; display: flex; align-items: center; gap: 12px; font-family: 'Outfit', 'Segoe UI', sans-serif; font-size: 15pt; font-weight: 600; }
    .cover .logo img { width: 54px; height: 54px; object-fit: contain; background: #fff; border-radius: 12px; padding: 4px; }
    .cover .of { font-family: 'Outfit', 'Segoe UI', sans-serif; letter-spacing: .4em; text-transform: uppercase; font-size: 12pt; opacity: .85; }
    .cover .yr { font-family: 'Outfit', 'Segoe UI', sans-serif; font-weight: 800; font-size: 96pt; line-height: .9; color: #fff; }
    .cover h1 { color: #fff; font-size: 24pt; margin-top: 8px; }
    .cover .theme { font-style: italic; font-size: 14pt; opacity: .9; margin-top: 8px; }
    .head { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 2px solid var(--p); padding-bottom: 6px; margin-bottom: 10mm; }
    .head h2 { font-size: 20pt; }
    .head span { color: #6b7280; font-size: 10pt; letter-spacing: .1em; text-transform: uppercase; font-family: 'Outfit', 'Segoe UI', sans-serif; }
    .letter { display: grid; grid-template-columns: 48mm 1fr; gap: 10mm; font-size: 11.5pt; line-height: 1.6; }
    .letter img { width: 100%; border-radius: 10px; }
    .classphoto img { width: 100%; border-radius: 10px; margin-bottom: 8mm; }
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7mm 6mm; }
    .g { text-align: center; break-inside: avoid; }
    .g .ph { width: 100%; aspect-ratio: 4 / 5; border-radius: 10px; overflow: hidden; background: #f3f4f6; display: grid; place-items: center; font-family: 'Outfit', 'Segoe UI', sans-serif; font-size: 28pt; color: #d1d5db; font-weight: 700; }
    .g .ph img { width: 100%; height: 100%; object-fit: cover; object-position: top; }
    .g h4 { font-size: 11pt; margin-top: 6px; }
    .g .n { color: var(--p); font-family: 'Outfit', 'Segoe UI', sans-serif; font-size: 9pt; }
    .g .t { font-size: 8.5pt; color: #4b5563; line-height: 1.35; margin-top: 3px; }
    .g .q { font-size: 8.5pt; font-style: italic; color: #6b7280; line-height: 1.35; margin-top: 3px; }
    .hon { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; }
    .hon div { display: flex; gap: 10px; align-items: center; border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px; break-inside: avoid; }
    .hon img, .hon .i { width: 18mm; height: 22mm; object-fit: cover; border-radius: 8px; background: #f3f4f6; flex: 0 0 auto; }
    .hon b { font-family: 'Outfit', 'Segoe UI', sans-serif; display: block; }
    .hon small { color: var(--p); }
    .foot { position: absolute; bottom: 8mm; left: 14mm; right: 14mm; display: flex; justify-content: space-between; font-size: 8pt; color: #9ca3af; font-family: 'Outfit', 'Segoe UI', sans-serif; }
    @media print { body { background: #fff; } .bar { display: none; } .page { margin: 0; box-shadow: none; width: auto; min-height: 273mm; padding: 0; } .cover { min-height: 273mm; } .foot { left: 0; right: 0; bottom: 0; } }
</style>
</head>
<body>
<div class="bar">
    <span>{{ $book->title }} · {{ $entries->count() }} graduates</span>
    <button onclick="window.print()">Print / Save as PDF</button>
    <a href="{{ route('site.yearbook', $book->slug) }}">Back to the yearbook</a>
</div>

<section class="page cover" @if($book->cover ?: $book->class_photo) style="background-image:url('{{ $F($book->cover ?: $book->class_photo) }}')" @endif>
    <div class="logo">@if($school->logo)<img src="{{ $school->logo }}" alt="">@endif {{ $school->name }}</div>
    <div class="in">
        <div class="of">Class of</div>
        <div class="yr">{{ $book->graduation_year }}</div>
        <h1>{{ $book->title }}</h1>
        @if($book->theme)<div class="theme">“{{ $book->theme }}”</div>@endif
    </div>
</section>

@if($book->principal_message || $book->class_photo)
<section class="page">
    @if($book->class_photo)
        <div class="head"><h2>The Set</h2><span>Class of {{ $book->graduation_year }}</span></div>
        <div class="classphoto"><img src="{{ $F($book->class_photo) }}" alt=""></div>
    @endif
    @if($book->principal_message)
        <div class="head"><h2>From the Principal’s desk</h2><span>{{ $book->principal_name }}</span></div>
        <div class="letter" @unless($book->principal_photo) style="grid-template-columns:1fr" @endunless>
            @if($book->principal_photo)<div><img src="{{ $F($book->principal_photo) }}" alt=""><p style="font-family:'Outfit','Segoe UI',sans-serif;margin:6px 0 0"><b>{{ $book->principal_name }}</b><br><small>Principal</small></p></div>@endif
            <div>{!! $book->principal_message !!}</div>
        </div>
    @endif
    <div class="foot"><span>{{ $school->name }}</span><span>{{ $book->title }}</span></div>
</section>
@endif

@if($honours->count())
<section class="page">
    <div class="head"><h2>Honours &amp; Leadership</h2><span>Prefects, prizes &amp; superlatives</span></div>
    <div class="hon">
        @foreach($honours as $h)
            <div>
                @if($h->displayPhoto())<img src="{{ $F(\App\Services\Website\Yearbooks::thumb($h->displayPhoto())) }}" alt="">@else<span class="i"></span>@endif
                <span><b>{{ $h->name }}</b><small>{{ collect([$h->position])->merge($h->awardList())->filter()->implode(' · ') }}</small></span>
            </div>
        @endforeach
    </div>
    <div class="foot"><span>{{ $school->name }}</span><span>{{ $book->title }}</span></div>
</section>
@endif

@foreach($entries->chunk(9) as $pi => $chunk)
<section class="page">
    <div class="head"><h2>The Graduates</h2><span>{{ $pi * 9 + 1 }}–{{ $pi * 9 + $chunk->count() }} of {{ $entries->count() }}</span></div>
    <div class="grid">
        @foreach($chunk as $e)
            <div class="g">
                <div class="ph">@if($e->displayPhoto())<img src="{{ $F(\App\Services\Website\Yearbooks::thumb($e->displayPhoto())) }}" alt="">@else{{ $e->initials() }}@endif</div>
                <h4>{{ $e->name }}</h4>
                @if($e->nickname)<div class="n">“{{ $e->nickname }}”</div>@endif
                <div class="t">{{ collect([$e->class_name, $e->ambition ? 'Future ' . $e->ambition : null])->filter()->implode(' · ') }}</div>
                @if($e->quote)<div class="q">“{{ \Illuminate\Support\Str::limit($e->quote, 110) }}”</div>@endif
            </div>
        @endforeach
    </div>
    <div class="foot"><span>{{ $school->name }}</span><span>{{ $book->title }}</span></div>
</section>
@endforeach
</body>
</html>
