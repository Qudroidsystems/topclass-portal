@extends('layouts.master')

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $T = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl(\App\Services\Website\Yearbooks::thumb($p));
    $v = fn ($f) => old($f, $e->{$f});
    $isNew = !$e->exists;
    $action = $isNew ? route('yearbook.admin.entry.store', $book->id) : route('yearbook.admin.entry.save', [$book->id, $e->id]);
    $pending = (array) $e->pending;
@endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$isNew ? 'Add graduate' : $e->name" icon="ri-user-star-line" :subtitle="$book->title" :back="route('yearbook.admin.show', $book->id)" back-label="Graduates">
        @unless($isNew)
            <x-slot:actions>
                @if($prevId ?? null)<a class="cb-hero-btn" href="{{ route('yearbook.admin.entry', [$book->id, $prevId]) }}"><i class="ri-arrow-left-s-line"></i> Previous</a>@endif
                @if($nextId ?? null)<a class="cb-hero-btn" href="{{ route('yearbook.admin.entry', [$book->id, $nextId]) }}">Next <i class="ri-arrow-right-s-line"></i></a>@endif
                @if($e->published)<a class="cb-hero-btn" target="_blank" href="{{ route('site.yearbook.entry', [$book->slug, $e->id]) }}"><i class="ri-external-link-line me-1"></i>View page</a>@endif
            </x-slot:actions>
        @endunless
    </x-cb.hero>
    @include('website.admin.yearbooks._nav')

    @if(!$isNew && $e->hasPending())
        <x-cb.card title="Sent through the upload link — waiting for approval" icon="ri-time-line" class="border border-warning">
            <div class="row g-3 align-items-start">
                @if($e->pending_photo)
                    <div class="col-md-3"><a href="{{ $F($e->pending_photo) }}" target="_blank"><img src="{{ $T($e->pending_photo) }}" class="w-100 rounded-3" style="aspect-ratio:4/5;object-fit:cover"></a><div class="small text-muted text-center mt-1">New photo</div></div>
                @endif
                <div class="col">
                    <div class="small text-muted mb-2">By <b>{{ $e->pending_by }}</b> · {{ $e->pending_at->format('d M Y, H:i') }}</div>
                    @if($pending)
                        <table class="table table-sm small mb-2">
                            @foreach($pending as $k => $val)<tr><th style="width:150px" class="text-capitalize">{{ str_replace('_', ' ', $k) }}</th><td>{!! nl2br(e($val)) !!}@if($e->{$k} && $e->{$k} !== $val)<div class="text-muted">was: {{ \Illuminate\Support\Str::limit($e->{$k}, 120) }}</div>@endif</td></tr>@endforeach
                        </table>
                    @endif
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('yearbook.admin.entry.approve', [$book->id, $e->id]) }}">@csrf<button class="btn btn-success"><i class="ri-check-line me-1"></i>Approve &amp; use</button></form>
                        <form method="POST" action="{{ route('yearbook.admin.entry.reject', [$book->id, $e->id]) }}" onsubmit="return confirm('Reject what was sent?')">@csrf<button class="btn btn-outline-danger">Reject</button></form>
                    </div>
                    <div class="form-text">Approving replaces the photo and fills in the details sent. You can still edit them below afterwards.</div>
                </div>
            </div>
        </x-cb.card>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">@csrf
        <div class="row g-3">
            <div class="col-xl-4">
                <x-cb.card title="Graduation photo" icon="ri-camera-line">
                    <div class="rounded-3 overflow-hidden mb-2" style="aspect-ratio:4/5;background:#f1f5f9">
                        <img id="phPrev" src="{{ $e->displayPhoto() ? $F($e->displayPhoto()) : '' }}" style="width:100%;height:100%;object-fit:cover;object-position:top;{{ $e->displayPhoto() ? '' : 'display:none' }}">
                        @unless($e->displayPhoto())<div id="phNone" class="d-flex h-100 align-items-center justify-content-center text-muted"><div class="text-center"><i class="ri-user-3-line" style="font-size:3rem"></i><div class="small">No photo yet</div></div></div>@endunless
                    </div>
                    @if(!$e->photo && $e->portal_photo)<div class="small text-muted mb-2"><i class="ri-information-line"></i> Showing the portal passport until a graduation photo is added.</div>@endif
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control" id="phIn">
                    <div class="form-text">The graduate in their gown, suit or graduation attire — portrait, face clearly visible.</div>
                    @if($e->photo)<label class="small mt-1"><input type="checkbox" name="remove_photo" value="1"> Remove the graduation photo</label>@endif
                </x-cb.card>

                @unless($isNew)
                    <x-cb.card title="Upload link" icon="ri-links-line">
                        <p class="small text-muted mb-2">Send this private link to {{ $e->name }} or a parent to upload the photo and details themselves.</p>
                        <div class="input-group input-group-sm mb-2"><input class="form-control" readonly value="{{ $e->uploadUrl() }}" id="upl"><button type="button" class="btn btn-outline-secondary" onclick="var i=document.getElementById('upl');i.select();(navigator.clipboard&&window.isSecureContext)?navigator.clipboard.writeText(i.value):document.execCommand('copy');this.innerHTML='Copied'">Copy</button></div>
                        <div class="d-flex gap-2 flex-wrap">
                            @if($phone ?? null)<a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="https://wa.me/{{ $phone }}?text={{ rawurlencode(\App\Services\Website\Yearbooks::linkMessage($e, $book)) }}"><i class="ri-whatsapp-line me-1"></i>WhatsApp parent</a>@endif
                            <a class="btn btn-sm btn-light" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode(\App\Services\Website\Yearbooks::linkMessage($e, $book)) }}"><i class="ri-share-line me-1"></i>Share…</a>
                            @if($e->student_id)
                                <button type="submit" form="notifyOne" class="btn btn-sm btn-light"><i class="ri-notification-3-line me-1"></i>Send via portal</button>
                            @endif
                        </div>
                        @if($e->link_sent_at)<div class="small text-muted mt-2">Last sent {{ $e->link_sent_at->diffForHumans() }}.</div>@endif
                        <button type="submit" form="newLink" class="btn btn-link btn-sm px-0 mt-1 text-danger">Make a new link (the old one stops working)</button>
                    </x-cb.card>
                @endunless
            </div>

            <div class="col-xl-8">
                <x-cb.card title="Profile" icon="ri-profile-line">
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label">Full name *</label><input name="name" class="form-control" required maxlength="150" value="{{ $v('name') }}"></div>
                        <div class="col-md-3"><label class="form-label">Nickname</label><input name="nickname" class="form-control" maxlength="80" value="{{ $v('nickname') }}"></div>
                        <div class="col-md-3"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">—</option>@foreach(['Male', 'Female'] as $g)<option @selected($v('gender') === $g)>{{ $g }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Admission no.</label><input name="admission_no" class="form-control" maxlength="60" value="{{ $v('admission_no') }}"></div>
                        <div class="col-md-4"><label class="form-label">Class</label><input name="class_name" class="form-control" maxlength="60" value="{{ $v('class_name') }}" placeholder="SS 3 A"></div>
                        <div class="col-md-4"><label class="form-label">House</label><input name="house" class="form-control" maxlength="60" value="{{ $v('house') }}"></div>
                        <div class="col-md-6"><label class="form-label">Future ambition</label><input name="ambition" class="form-control" maxlength="150" value="{{ $v('ambition') }}"></div>
                        <div class="col-md-6"><label class="form-label">Next step</label><input name="next_step" class="form-control" maxlength="150" value="{{ $v('next_step') }}" placeholder="University / course / plans"></div>
                        <div class="col-md-6"><label class="form-label">Hobbies</label><input name="hobbies" class="form-control" maxlength="200" value="{{ $v('hobbies') }}"></div>
                        <div class="col-md-6"><label class="form-label">Favourite subject</label><input name="favourite_subject" class="form-control" maxlength="100" value="{{ $v('favourite_subject') }}"></div>
                        <div class="col-12"><label class="form-label">Favourite quote / motto</label><input name="quote" class="form-control" maxlength="300" value="{{ $v('quote') }}"></div>
                        <div class="col-12"><label class="form-label">Student’s testimonial — their time in school</label><textarea name="testimonial" class="form-control" rows="3" maxlength="2000">{{ $v('testimonial') }}</textarea></div>
                        <div class="col-12"><label class="form-label">Best memory</label><textarea name="best_memory" class="form-control" rows="2" maxlength="1500">{{ $v('best_memory') }}</textarea></div>
                    </div>
                </x-cb.card>
                <x-cb.card title="Honours & the school’s testimonial" icon="ri-award-line">
                    <div class="row g-2">
                        <div class="col-md-5"><label class="form-label">Position / office</label><input name="position" class="form-control" maxlength="100" value="{{ $v('position') }}" list="positions" placeholder="e.g. Head Girl">
                            <datalist id="positions">@foreach(['Head Boy', 'Head Girl', 'Assistant Head Boy', 'Assistant Head Girl', 'Senior Prefect', 'Labour Prefect', 'Games Prefect', 'Library Prefect', 'Social Prefect', 'Chapel Prefect', 'Health Prefect', 'Class Captain', 'House Captain'] as $p)<option value="{{ $p }}">@endforeach</datalist></div>
                        <div class="col-md-7"><label class="form-label">Awards &amp; superlatives <small class="text-muted">(separate with commas)</small></label><input name="awards" class="form-control" maxlength="300" value="{{ $v('awards') }}" placeholder="Best Graduating Student, Most Punctual, Most Likely to Succeed"></div>
                        <div class="col-12"><label class="form-label">The school’s testimonial / principal’s remark</label><textarea name="school_remark" class="form-control" rows="3" maxlength="2000" placeholder="A brief character testimonial from the school">{{ $v('school_remark') }}</textarea></div>
                        <div class="col-md-3"><label class="form-label">Order</label><input type="number" name="sort" class="form-control" min="0" value="{{ $v('sort') }}"></div>
                        <div class="col-md-9 d-flex align-items-end gap-4">
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="published" value="1" id="epub" @checked(old('published', $e->published))><label class="form-check-label" for="epub">Show in the yearbook</label></div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="featured" value="1" id="efeat" @checked(old('featured', $e->featured))><label class="form-check-label" for="efeat">Featured</label></div>
                        </div>
                    </div>
                </x-cb.card>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-primary"><i class="ri-save-3-line me-1"></i>Save</button>
                    @if($isNew)<button class="btn btn-light" name="then" value="new">Save &amp; add another</button>
                    @else<button class="btn btn-light" name="then" value="next">Save &amp; next <i class="ri-arrow-right-s-line"></i></button>@endif
                </div>
            </div>
        </div>
    </form>

    @unless($isNew)
        <form id="notifyOne" method="POST" action="{{ route('yearbook.admin.bulk', $book->id) }}">@csrf<input type="hidden" name="action" value="notify"><input type="hidden" name="ids[]" value="{{ $e->id }}"></form>
        <form id="newLink" method="POST" action="{{ route('yearbook.admin.entry.link', [$book->id, $e->id]) }}" onsubmit="return confirm('Make a new link? The link already sent will stop working.')">@csrf</form>
        <form method="POST" action="{{ route('yearbook.admin.entry.delete', [$book->id, $e->id]) }}" onsubmit="return confirm('Remove {{ addslashes($e->name) }} from this yearbook?')" class="text-end">@csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line me-1"></i>Remove from yearbook</button>
        </form>
    @endunless
</div></div></div>
@endsection

@push('scripts')
<script>
document.getElementById('phIn').addEventListener('change', function () {
    var f = this.files && this.files[0]; if (!f) return;
    var img = document.getElementById('phPrev'); img.src = URL.createObjectURL(f); img.style.display = '';
    var n = document.getElementById('phNone'); if (n) n.style.display = 'none';
});
</script>
@endpush
