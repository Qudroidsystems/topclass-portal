{{-- resources/views/studentreports/partials/student_rows.blade.php --}}

@forelse ($allstudents as $index => $student)

@php
    $avatarColors = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#30cfd0','#a18cd1','#fda085'];
    $color1 = $avatarColors[$index % count($avatarColors)];
    $color2 = $avatarColors[($index + 2) % count($avatarColors)];
    $initials = strtoupper(
        substr($student->firstname ?? '', 0, 1) .
        substr($student->lastname  ?? '', 0, 1)
    );
    if (empty(trim($initials))) $initials = 'ST';
    $genderClass = strtolower($student->gender ?? '') === 'male' ? 'male' : 'female';
    $genderIcon  = strtolower($student->gender ?? '') === 'male' ? 'bi-gender-male' : 'bi-gender-female';
    $avatarUrl   = $student->picture ? asset('storage/' . $student->picture) : null;
    $fullName    = trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? ''));
    $fallbackSvg = $defaultAvatarSvg ?? '';
@endphp

<tr style="animation: rowIn .3s ease {{ $index * 0.03 }}s both;">

    {{-- Checkbox --}}
    <td class="id" data-id="{{ $student->stid }}">
        <input type="checkbox" name="chk_child" value="{{ $student->stid }}"
               style="accent-color:var(--r-accent);width:15px;height:15px;cursor:pointer;">
    </td>

    {{-- Admission No --}}
    <td class="admissionno" data-admissionno="{{ $student->admissionno }}">
        <span style="font-family:'JetBrains Mono',monospace;font-size:12px;
                     font-weight:600;color:var(--r-primary);
                     background:var(--r-bg);border:1px solid var(--r-border);
                     padding:3px 8px;border-radius:6px;">
            {{ $student->admissionno ?? 'N/A' }}
        </span>
    </td>

    {{-- Photo --}}
    <td class="picture" data-picture="{{ $student->picture }}">
        <a href="#" data-bs-toggle="modal" data-bs-target="#imageViewModal"
           data-image="{{ $avatarUrl ?? $fallbackSvg }}"
           class="r-avatar-wrap" title="Click to enlarge">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}"
                     alt="{{ $fullName }}'s picture"
                     class="r-avatar"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                <div class="r-avatar-placeholder"
                     style="display:none;background:linear-gradient(135deg,{{ $color1 }},{{ $color2 }})">
                    {{ $initials }}
                </div>
            @else
                <div class="r-avatar-placeholder"
                     style="background:linear-gradient(135deg,{{ $color1 }},{{ $color2 }})">
                    {{ $initials }}
                </div>
            @endif
            <div class="r-avatar-zoom-btn"><i class="bi bi-zoom-in"></i></div>
        </a>
    </td>

    {{-- Last Name --}}
    <td class="lastname" data-lastname="{{ $student->lastname }}">
        <div style="font-weight:700;color:var(--r-primary);">
            {{ $student->lastname ?? '—' }}
        </div>
    </td>

    {{-- First Name --}}
    <td class="firstname" data-firstname="{{ $student->firstname }}">
        <div style="color:#374151;">{{ $student->firstname ?? '—' }}</div>
    </td>

    {{-- Other Name --}}
    <td class="othername" data-othername="{{ $student->othername }}">
        <div style="color:var(--r-muted);font-size:12px;">
            {{ $student->othername ?? '—' }}
        </div>
    </td>

    {{-- Gender --}}
    <td class="gender" data-gender="{{ $student->gender }}">
        <span class="r-gender-badge {{ $genderClass }}">
            <i class="bi {{ $genderIcon }}"></i>
            {{ ucfirst(strtolower($student->gender ?? '—')) }}
        </span>
    </td>

    {{-- Class --}}
    <td class="schoolclass" data-schoolclass="{{ $student->schoolclass }}">
        <span class="r-class-badge">
            <i class="ri-building-line"></i>
            {{ $student->schoolclass ?? '—' }}
        </span>
    </td>

    {{-- Arm --}}
    <td class="schoolarm" data-schoolarm="{{ $student->schoolarm }}">
        <span class="r-arm-badge">
            {{ $student->schoolarm ?? '—' }}
        </span>
    </td>

    {{-- Session --}}
    <td class="session" data-session="{{ $student->session }}">
        <span style="color:var(--r-muted);font-size:12px;">
            {{ $student->session ?? '—' }}
        </span>
    </td>
</tr>

@empty
<tr>
    <td colspan="10">
        <div class="r-empty">
            <i class="ri-user-search-line r-empty-icon"></i>
            <h6>No Students Found</h6>
            <p>Select a class and session above to load student records.</p>
        </div>
    </td>
</tr>
@endforelse
