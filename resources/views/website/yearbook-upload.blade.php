@extends('website.layout', ['pageTitle' => 'Yearbook photo — ' . $e->name])

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $p = (array) $e->pending;
    $val = fn ($f) => old($f, $p[$f] ?? $e->{$f});
    $current = $e->pending_photo ?: $e->displayPhoto();
@endphp

@push('head')<meta name="robots" content="noindex, nofollow">@endpush

@section('content')
    @include('website.partials.page-hero', ['title' => 'Yearbook photo & details', 'lead' => $book->title . ' · Class of ' . $book->graduation_year, 'crumbs' => [['Yearbooks', route('site.yearbooks')], ['Upload', null]]])

    <section class="section">
        <div class="container" style="max-width:820px">
            <div class="panel yb-upload">
                <div class="current">
                    @if($current)<img src="{{ $F(\App\Services\Website\Yearbooks::thumb($current)) }}" alt="">@else<span class="ini">{{ $e->initials() }}</span>@endif
                    <div>
                        <h3 style="margin:0">{{ $e->name }}</h3>
                        <div class="muted">{{ $e->class_name }}{{ $e->admission_no ? ' · ' . $e->admission_no : '' }}</div>
                        @if($e->hasPending())<div style="color:#b45309;font-size:.92rem;margin-top:4px"><i class="ri-time-line"></i> Received {{ $e->pending_at->diffForHumans() }} — waiting for the school’s approval. You can still make changes below.</div>
                        @elseif($e->photo)<div style="color:#1f7a46;font-size:.92rem;margin-top:4px"><i class="ri-checkbox-circle-line"></i> The graduation photo is already in the yearbook. You can send a better one.</div>@endif
                    </div>
                </div>

                @if(!$open)
                    <div class="yb-note"><i class="ri-lock-line"></i> Photo and detail submissions for this yearbook are closed. Please contact the school if something needs to change.</div>
                @else
                    <div class="yb-note" style="margin-bottom:24px">
                        <b>Photo guide:</b> a clear, recent photo of the graduate in their <b>graduation gown, suit or graduation attire</b>.
                        Portrait (standing or half-length), face clearly visible, plain or school background. JPG or PNG, up to 10 MB.
                    </div>
                    <form class="form" method="POST" action="{{ route('site.yearbook.upload.save', $e->upload_token) }}" enctype="multipart/form-data">@csrf
                        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
                        <div>
                            <label for="ybPhoto" class="drop" id="ybDrop">
                                <img id="ybPrev" alt="" style="display:none">
                                <i class="ri-camera-line" id="ybIcon"></i>
                                <div style="font-family:var(--head);color:var(--ink)" id="ybTxt">Choose the graduation photo</div>
                                <small class="muted">Tap to take a photo or pick one from your gallery</small>
                            </label>
                            <input type="file" name="photo" id="ybPhoto" accept="image/jpeg,image/png,image/webp" hidden>
                            @error('photo')<div class="err">{{ $message }}</div>@enderror
                        </div>
                        <div class="row2">
                            <div><label>Nickname</label><input name="nickname" maxlength="80" value="{{ $val('nickname') }}" placeholder="What friends call them"></div>
                            <div><label>Future ambition</label><input name="ambition" maxlength="150" value="{{ $val('ambition') }}" placeholder="e.g. Medical doctor"></div>
                        </div>
                        <div class="row2">
                            <div><label>Hobbies</label><input name="hobbies" maxlength="200" value="{{ $val('hobbies') }}" placeholder="e.g. Reading, football, music"></div>
                            <div><label>Favourite subject</label><input name="favourite_subject" maxlength="100" value="{{ $val('favourite_subject') }}"></div>
                        </div>
                        <div><label>Next step after school</label><input name="next_step" maxlength="150" value="{{ $val('next_step') }}" placeholder="e.g. Studying Law at university"></div>
                        <div><label>Favourite quote / personal motto</label><input name="quote" maxlength="300" value="{{ $val('quote') }}"></div>
                        <div><label>Testimonial — my time in this school</label><textarea name="testimonial" rows="4" maxlength="2000" placeholder="A few words about the school, teachers and friends">{{ $val('testimonial') }}</textarea></div>
                        <div><label>Best memory</label><textarea name="best_memory" rows="3" maxlength="1500">{{ $val('best_memory') }}</textarea></div>
                        <div class="row2">
                            <div><label>Your name *</label><input name="submitter" required maxlength="120" value="{{ old('submitter') }}"></div>
                            <div><label>You are the *</label>
                                <select name="relationship" required>@foreach(['Student', 'Parent', 'Guardian'] as $r)<option @selected(old('relationship') === $r)>{{ $r }}</option>@endforeach</select>
                            </div>
                        </div>
                        <label class="yb-check"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required> <span>I agree that this photo and these details may be shown in the school’s yearbook{{ $book->isPortalOnly() ? ' (visible to signed-in portal users)' : ' on the school website' }}.</span></label>
                        @error('consent')<div class="err">{{ $message }}</div>@enderror
                        <button class="btn btn-primary" style="justify-self:start"><i class="ri-upload-2-line"></i> Send to the school</button>
                    </form>
                @endif
            </div>
            <p class="muted center" style="margin-top:18px;font-size:.9rem"><i class="ri-shield-keyhole-line"></i> This link is private to {{ $e->name }}. Please don’t share it.</p>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    var inp = document.getElementById('ybPhoto'); if (!inp) return;
    inp.addEventListener('change', function () {
        var f = inp.files && inp.files[0]; if (!f) return;
        var img = document.getElementById('ybPrev');
        img.src = URL.createObjectURL(f); img.style.display = 'block';
        document.getElementById('ybIcon').style.display = 'none';
        document.getElementById('ybTxt').textContent = f.name + ' — tap to change';
    });
})();
</script>
@endpush
