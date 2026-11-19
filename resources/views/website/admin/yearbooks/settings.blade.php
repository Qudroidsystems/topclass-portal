@extends('layouts.master')

@php $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p); @endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Settings — ' . $book->title" icon="ri-settings-4-line" subtitle="Title, cover, class photo, the principal’s message, who can see it, and publishing." :back="route('yearbook.admin.show', $book->id)" back-label="Graduates" />
    @include('website.admin.yearbooks._nav')

    <form method="POST" action="{{ route('yearbook.admin.update', $book->id) }}" enctype="multipart/form-data">@csrf
        <div class="row g-3">
            <div class="col-xl-8">
                <x-cb.card title="Yearbook" icon="ri-book-open-line">
                    <div class="row g-2">
                        <div class="col-md-8"><label class="form-label">Title *</label><input name="title" class="form-control" required maxlength="150" value="{{ old('title', $book->title) }}"><div class="form-text">e.g. “Class of {{ $book->graduation_year }}” or “Class of {{ $book->graduation_year }} — The Trailblazers”.</div></div>
                        <div class="col-md-4"><label class="form-label">Graduation year *</label><input type="number" name="graduation_year" class="form-control" required min="1950" max="2100" value="{{ old('graduation_year', $book->graduation_year) }}"></div>
                        <div class="col-md-4"><label class="form-label">Graduating class</label><input name="class_label" class="form-control" maxlength="100" value="{{ old('class_label', $book->class_label) }}" placeholder="SS 3"></div>
                        <div class="col-md-4"><label class="form-label">Session</label><select name="session_id" class="form-select"><option value="">—</option>@foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('session_id', $book->session_id) == $s->id)>{{ $s->session }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Order</label><input type="number" name="sort" min="0" class="form-control" value="{{ old('sort', $book->sort) }}"></div>
                        <div class="col-12"><label class="form-label">Set theme / motto</label><input name="theme" class="form-control" maxlength="200" value="{{ old('theme', $book->theme) }}" placeholder="e.g. Rising to Excellence"></div>
                        <div class="col-12"><label class="form-label">About the set</label><textarea name="description" class="form-control" rows="3" maxlength="3000">{{ old('description', $book->description) }}</textarea></div>
                    </div>
                </x-cb.card>

                <x-cb.card title="Principal’s message" icon="ri-quill-pen-line">
                    <div class="row g-2 mb-2">
                        <div class="col-md-8"><label class="form-label">Principal’s name</label><input name="principal_name" class="form-control" maxlength="150" value="{{ old('principal_name', $book->principal_name) }}"></div>
                        <div class="col-md-4"><label class="form-label">Principal’s photo</label><input type="file" name="principal_photo" accept="image/*" class="form-control">
                            @if($book->principal_photo)<div class="d-flex align-items-center gap-2 mt-1"><img src="{{ $F($book->principal_photo) }}" style="height:44px;border-radius:6px"><label class="small mb-0"><input type="checkbox" name="remove_principal_photo" value="1"> Remove</label></div>@endif</div>
                    </div>
                    @include('website.admin._editor', ['name' => 'principal_message', 'value' => old('principal_message', $book->principal_message), 'height' => 300])
                    <div class="form-text">A farewell letter to the graduating set. Simple formatting (bold, lists, paragraphs) is kept.</div>
                </x-cb.card>
            </div>
            <div class="col-xl-4">
                <x-cb.card title="Publishing" icon="ri-eye-line">
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="published" value="1" id="pub" @checked(old('published', $book->published))><label class="form-check-label" for="pub"><b>Published</b> — show this yearbook</label></div>
                    <label class="form-label mt-2">Who can see it</label>
                    <select name="visibility" class="form-select mb-1">
                        <option value="public" @selected($book->visibility === 'public')>Everyone — on the school website</option>
                        <option value="portal" @selected($book->visibility === 'portal')>Portal users only (signed-in students, parents, staff)</option>
                    </select>
                    <div class="form-text mb-3">Admission numbers are only ever shown to signed-in portal users.</div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="allow_submissions" value="1" id="subm" @checked(old('allow_submissions', $book->allow_submissions))><label class="form-check-label" for="subm">Accept photos &amp; details through upload links</label></div>
                    <label class="form-label">Close submissions on</label>
                    <input type="date" name="submissions_close_on" class="form-control" value="{{ old('submissions_close_on', optional($book->submissions_close_on)->format('Y-m-d')) }}">
                </x-cb.card>

                <x-cb.card title="Pictures" icon="ri-image-line">
                    <label class="form-label">Cover (portrait or wide)</label>
                    <input type="file" name="cover" accept="image/*" class="form-control mb-1">
                    @if($book->cover)<div class="d-flex align-items-center gap-2 mb-2"><img src="{{ $F($book->cover) }}" style="height:60px;border-radius:6px"><label class="small mb-0"><input type="checkbox" name="remove_cover" value="1"> Remove</label></div>@endif
                    <label class="form-label mt-2">Class / group photo</label>
                    <input type="file" name="class_photo" accept="image/*" class="form-control mb-1">
                    @if($book->class_photo)<div class="d-flex align-items-center gap-2 mb-2"><img src="{{ $F($book->class_photo) }}" style="height:60px;border-radius:6px"><label class="small mb-0"><input type="checkbox" name="remove_class_photo" value="1"> Remove</label></div>@endif
                    <label class="form-label mt-2">Memories album</label>
                    <select name="album_id" class="form-select"><option value="">— None —</option>@foreach($albums as $al)<option value="{{ $al->id }}" @selected($book->album_id == $al->id)>{{ $al->title }}</option>@endforeach</select>
                    <div class="form-text">Pick a website gallery album (send-forth, valedictory service, excursions…).</div>
                </x-cb.card>

                <button class="btn btn-primary w-100 mb-3"><i class="ri-save-3-line me-1"></i>Save settings</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('yearbook.admin.destroy', $book->id) }}" onsubmit="return confirm('Delete this yearbook, all its graduates and their photos? This cannot be undone.')" class="text-end">@csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line me-1"></i>Delete yearbook</button>
    </form>
</div></div></div>
@endsection
