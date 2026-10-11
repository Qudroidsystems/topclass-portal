{{--
    "Record leaving" modal, shared by Promotion Management and Former Students.
    Open it with openStudentExitModal([studentIds], onDone).
--}}
@php
    $exitSessions = \App\Models\Schoolsession::orderByDesc('id')->get(['id', 'session', 'status']);
    $exitTerms    = \App\Models\Schoolterm::orderBy('id')->get();
    $exitNow      = \App\Http\Controllers\StudentExitController::currentPoint();
@endphp
<div class="modal fade" id="studentExitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger-subtle">
                <h5 class="modal-title"><i class="ri-door-open-line me-1"></i>Record Leaving the School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    <span id="studentExitCount">0</span> student(s) selected. From the session and term below they are
                    removed from class lists, promotion and score entry, including any class they were already promoted
                    into. Their earlier results stay. You can reactivate them later from <strong>Former Students</strong>.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" id="studentExitStatus">
                        <option value="Left">Left / Withdrawn</option>
                        <option value="Transferred">Transferred to another school</option>
                        <option value="Graduated">Graduated</option>
                        <option value="Expelled">Expelled</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Not in school from: session</label>
                        <select class="form-select" id="studentExitSession">
                            @foreach($exitSessions as $s)
                                <option value="{{ $s->id }}" @selected($s->id == $exitNow['session_id'])>{{ $s->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">term</label>
                        <select class="form-select" id="studentExitTerm">
                            @foreach($exitTerms as $t)
                                <option value="{{ $t->id }}" @selected($t->id == $exitNow['term_id'])>{{ $t->term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><small class="text-muted">The first term they are no longer attending.</small></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Date left</label>
                    <input type="date" class="form-control" id="studentExitDate" value="{{ now()->toDateString() }}">
                </div>
                <div class="mb-3" id="studentExitDestinationWrap" style="display:none;">
                    <label class="form-label fw-semibold">New school</label>
                    <input type="text" class="form-control" id="studentExitDestination" maxlength="255" placeholder="Where they transferred to">
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold">Reason / note</label>
                    <textarea class="form-control" id="studentExitReason" rows="2" maxlength="2000" placeholder="e.g. Family relocated"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="studentExitSubmit"><i class="ri-check-line me-1"></i>Save</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    let exitIds = [], exitDone = null;
    const el = id => document.getElementById(id);

    el('studentExitStatus').addEventListener('change', function () {
        el('studentExitDestinationWrap').style.display = this.value === 'Transferred' ? '' : 'none';
    });

    window.openStudentExitModal = function (ids, onDone) {
        exitIds  = (ids || []).map(String).filter(Boolean);
        exitDone = onDone || null;
        if (!exitIds.length) { alert('Select at least one student'); return; }
        el('studentExitCount').innerText = exitIds.length;
        bootstrap.Modal.getOrCreateInstance(el('studentExitModal')).show();
    };

    el('studentExitSubmit').addEventListener('click', async function () {
        const btn = this;
        const body = new FormData();
        exitIds.forEach((id, i) => body.append(`student_ids[${i}]`, id));
        body.append('status',      el('studentExitStatus').value);
        body.append('session_id',  el('studentExitSession').value);
        body.append('term_id',     el('studentExitTerm').value);
        body.append('exit_date',   el('studentExitDate').value);
        body.append('reason',      el('studentExitReason').value);
        if (el('studentExitStatus').value === 'Transferred') body.append('destination', el('studentExitDestination').value);

        btn.disabled = true;
        try {
            const res = await fetch('{{ route('students.exit') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(firstError || data.message || 'Could not save');
            }
            bootstrap.Modal.getInstance(el('studentExitModal'))?.hide();
            (window.showToast || alert)(data.message, 'success');
            if (exitDone) exitDone(data);
        } catch (e) {
            (window.showToast || alert)(e.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
