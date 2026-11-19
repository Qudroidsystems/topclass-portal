{{-- Tabs for Portal › Yearbook & Alumni. --}}
@php
    $ybPending = 0; $alPending = 0;
    try {
        $ybPending = \App\Models\Website\YearbookEntry::whereNotNull('pending_at')->count();
        $alPending = \App\Models\Website\Alumnus::where(fn ($q) => $q->where('status', 'pending')->orWhereNotNull('pending_at'))->count();
    } catch (\Throwable $e) {}
@endphp
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('yearbook.admin.index') }}" class="btn btn-sm {{ request()->routeIs('yearbook.admin.*') ? 'btn-primary' : 'btn-light' }}"><i class="ri-book-open-line me-1"></i>Yearbooks
        @if($ybPending)<span class="badge bg-warning text-dark ms-1" title="Photos waiting for approval">{{ $ybPending }}</span>@endif</a>
    <a href="{{ route('alumni.admin.index') }}" class="btn btn-sm {{ request()->routeIs('alumni.admin.*') ? 'btn-primary' : 'btn-light' }}"><i class="ri-graduation-cap-line me-1"></i>Alumni
        @if($alPending)<span class="badge bg-warning text-dark ms-1" title="Waiting for approval">{{ $alPending }}</span>@endif</a>
    @can('Manage website')<a href="{{ route('website.admin.index') }}" class="btn btn-sm btn-light"><i class="ri-global-line me-1"></i>Website admin</a>@endcan
    <a href="{{ route('site.yearbooks') }}" target="_blank" class="btn btn-sm btn-outline-success ms-auto"><i class="ri-external-link-line me-1"></i>Yearbooks page</a>
    <a href="{{ route('site.alumni') }}" target="_blank" class="btn btn-sm btn-outline-success"><i class="ri-external-link-line me-1"></i>Alumni page</a>
</div>
@if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
