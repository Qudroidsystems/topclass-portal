{{-- resources/views/subjectoperation/partials/student_rows.blade.php --}}
@php
    $i = isset($students) && $students instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? ($students->currentPage() - 1) * $students->perPage()
        : 0;

    // The index page has no term filter, so links open on the term in the query
    // string when there is one, otherwise the first term.
    $linkTermId = request('term_id') ?: 1;
@endphp

@forelse ($students ?? [] as $sc)
    @php
        $photo = $sc->picture
            ? asset('storage/student_avatars/' . basename($sc->picture))
            : asset('storage/student_avatars/unnamed.jpg');

        $fullName = trim(($sc->lastname ?? '') . ' ' . ($sc->firstname ?? '') . ' ' . ($sc->othername ?? ''));
        $infoUrl  = route('subjects.subjectinfo', [$sc->id, $sc->schoolclassid, $linkTermId, $sc->sessionid]);
    @endphp
    <tr>
        <td class="id" data-id="{{ $sc->id }}">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="chk_child" aria-label="Select {{ $fullName }}">
            </div>
        </td>
        <td class="sn">{{ ++$i }}</td>
        <td class="admissionno" data-admissionno="{{ $sc->admissionno }}">{{ $sc->admissionno }}</td>
        <td class="name" data-name="{{ $fullName }}">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <img src="{{ $photo }}"
                         alt="{{ $fullName }}"
                         class="rounded-circle avatar-sm"
                         data-image="{{ $photo }}"
                         onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                </div>
                <div>
                    <h6 class="mb-0">
                        <a href="{{ $infoUrl }}" class="text-reset">{{ $fullName }}</a>
                    </h6>
                </div>
            </div>
        </td>
        <td class="class" data-class="{{ $sc->class_name }} {{ $sc->arm_name }}">
            <span class="badge bg-primary-subtle text-primary">{{ $sc->class_name }} {{ $sc->arm_name }}</span>
        </td>
        <td class="gender" data-gender="{{ $sc->gender }}">{{ $sc->gender }}</td>
        <td>
            <ul class="d-flex gap-2 list-unstyled mb-0">
                @can('Update subject-operation')
                    <li>
                        <a href="{{ $infoUrl }}"
                           class="btn btn-subtle-primary btn-icon btn-sm"
                           title="Open subject registration for {{ $fullName }}">
                            <i class="ph-eye"></i>
                        </a>
                    </li>
                @endcan
            </ul>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center text-muted py-4">Choose a class and session to list students.</td>
    </tr>
@endforelse