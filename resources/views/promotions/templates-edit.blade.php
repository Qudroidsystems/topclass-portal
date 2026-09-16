{{-- resources/views/promotions/templates-edit.blade.php --}}
@extends('layouts.master')

@section('content')
{{-- Same CSS as templates-create.blade.php — copy the entire <style> block from there --}}
<style>
:root {
    --ps-primary: #1e3a5f;
    --ps-accent: #2563eb;
    --ps-success: #16a34a;
    --ps-warning: #d97706;
    --ps-danger: #dc2626;
    --ps-muted: #6b7280;
    --ps-border: #e2e8f0;
    --ps-bg: #f8fafc;
    --ps-radius: 12px;
}

.ps-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--ps-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.ps-hero h1 { font-size: 22px; font-weight: 700; color: #fff; margin: 0 0 6px; }
.ps-hero p { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; }

.form-card { background: #fff; border: 1px solid var(--ps-border); border-radius: var(--ps-radius); padding: 28px; }

.form-section { background: var(--ps-bg); border-radius: 12px; padding: 20px; margin-bottom: 18px; }

.form-section-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--ps-primary);
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--ps-border);
    display: flex;
    align-items: center;
    gap: 8px;
}

.rule-card {
    background: #fff;
    border: 2px solid var(--ps-border);
    border-radius: 12px;
    margin-bottom: 18px;
    overflow: hidden;
    transition: all .2s;
}
.rule-card:hover { border-color: var(--ps-accent); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.rule-card-header {
    background: linear-gradient(90deg, #f8fafc, #fff);
    border-bottom: 1px solid var(--ps-border);
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.rule-num-badge {
    background: var(--ps-primary);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 12px;
    border-radius: 20px;
}
.rule-card-body { padding: 18px; }

.label-selector { display: flex; gap: 8px; flex-wrap: wrap; }

.label-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all .2s;
    user-select: none;
}
.label-pill:hover { transform: translateY(-1px); }
.label-pill.active { box-shadow: 0 0 0 3px rgba(0,0,0,.12); transform: scale(1.03); }
.label-pill.lp-promoted  { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
.label-pill.lp-promoted.active   { background: #16a34a; color: #fff; }
.label-pill.lp-trial     { background: #fef9c3; color: #854d0e; border-color: #fde68a; }
.label-pill.lp-trial.active      { background: #ca8a04; color: #fff; }
.label-pill.lp-principal { background: #e0f2fe; color: #075985; border-color: #bae6fd; }
.label-pill.lp-principal.active  { background: #0284c7; color: #fff; }
.label-pill.lp-repeat    { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
.label-pill.lp-repeat.active     { background: #dc2626; color: #fff; }

.rule-section { border: 1px solid var(--ps-border); border-radius: 10px; margin-bottom: 14px; overflow: hidden; }
.rule-section-header {
    background: linear-gradient(90deg, #f1f5f9, #f8fafc);
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 700;
    color: var(--ps-primary);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--ps-border);
}
.rule-section-body { padding: 14px; }

.cond-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
}
.cond-row:last-child { border-bottom: none; }

.grade-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 800;
    flex-shrink: 0;
}
.gp-A, .gp-A1 { background: #dcfce7; color: #166534; }
.gp-B, .gp-B2, .gp-B3 { background: #dbeafe; color: #1e40af; }
.gp-C, .gp-C4, .gp-C5, .gp-C6 { background: #fef9c3; color: #854d0e; }
.gp-D, .gp-D7 { background: #ffedd5; color: #9a3412; }
.gp-E, .gp-E8 { background: #f3e8ff; color: #6b21a8; }
.gp-F, .gp-F9 { background: #fee2e2; color: #991b1b; }
.cond-text { font-size: 12px; color: var(--ps-muted); white-space: nowrap; }

.avg-box { background: #f0f9ff; border: 1.5px solid #bae6fd; border-radius: 10px; padding: 14px; margin-top: 12px; }

.no-rules-ph {
    text-align: center;
    padding: 36px 20px;
    color: var(--ps-muted);
    background: var(--ps-bg);
    border-radius: 12px;
    border: 2px dashed var(--ps-border);
}
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

<div class="ps-hero">
    <h1><i class="ri-edit-circle-line me-2"></i>Edit Promotion Rule Template</h1>
    <p>{{ $template->name }}</p>
</div>

<form id="editTemplateForm">
    @csrf
    <input type="hidden" id="template_id" value="{{ $template->id }}">

    <div class="form-card">
        <div class="form-section">
            <div class="form-section-title"><i class="ri-information-line"></i>Template Information</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="template_name" value="{{ $template->name }}" required maxlength="255">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Grade Scale <span class="text-danger">*</span></label>
                    <select class="form-select" name="grade_scale" id="template_grade_scale" required>
                        <option value="senior" {{ $template->grade_scale === 'senior' ? 'selected' : '' }}>Senior (A1–F9)</option>
                        <option value="junior" {{ $template->grade_scale === 'junior' ? 'selected' : '' }}>Junior (A–F)</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" name="description" id="template_description" rows="2" maxlength="1000">{{ $template->description }}</textarea>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title" style="justify-content: space-between;">
                <span><i class="ri-price-tag-3-line me-2"></i>Promotion Rules</span>
                <button type="button" class="btn btn-sm btn-primary" id="addRuleBtn">
                    <i class="ri-add-line me-1"></i>Add Rule
                </button>
            </div>

            <div id="rulesContainer">
                <div class="no-rules-ph">
                    <i class="ri-clipboard-line d-block mb-2" style="font-size:2rem;opacity:.3;"></i>
                    Click <strong>Add Rule</strong> to start building.
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="template_is_active" {{ $template->is_active ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="template_is_active">Active</label>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('promotion.templates.index') }}" class="btn btn-light">
                <i class="ri-close-line me-1"></i>Cancel
            </a>
            <button type="submit" class="btn btn-primary" id="saveTemplateBtn">
                <i class="ri-save-line me-1"></i>Update Template
            </button>
        </div>
    </div>
</form>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Identical logic to templates-create.blade.php
// Only difference: loads existing rules from server + uses PUT on submit

let promotionRules = @json($template->promotion_rules ?? []);
let currentGradeScale = '{{ $template->grade_scale }}';

const GRADE_SCALES = {
    senior: ['A1','B2','B3','C4','C5','C6','D7','E8','F9'],
    junior: ['A','B','C','D','F']
};
const GROUPED_SENIOR = { A:['A1'], B:['B2','B3'], C:['C4','C5','C6'], D:['D7'], E:['E8'], F:['F9'] };
const GROUPED_JUNIOR = { A:['A'], B:['B'], C:['C'], D:['D'], F:['F'] };

const STATUS_LABELS = [
    {key:'promoted', label:'Promoted', cls:'lp-promoted', icon:'ri-checkbox-circle-line'},
    {key:'trial', label:'Promoted on Trial', cls:'lp-trial', icon:'ri-time-line'},
    {key:'see_principal', label:'Advised to See Principal', cls:'lp-principal', icon:'ri-user-star-line'},
    {key:'repeat', label:'Advice to Repeat', cls:'lp-repeat', icon:'ri-repeat-line'},
];

const SCOPE_OPTIONS = [
    ['all', '📚 All Subjects'],
    ['compulsory_only', '⭐ Compulsory Only'],
    ['other_only', '📖 Other Only'],
];

function escH(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'"');
}

function getGradesForGrouping(grouping) {
    if (grouping === 'grouped') {
        return currentGradeScale === 'senior' ? Object.keys(GROUPED_SENIOR) : Object.keys(GROUPED_JUNIOR);
    }
    return GRADE_SCALES[currentGradeScale];
}

// Same build functions as templates-create
function buildCondRows(conds, ruleIdx, section, availGrades) {
    if (!conds.length) return '';
    return conds.map((cond, ci) => {
        const operators = ['>=', '<=', '=', '>', '<'];
        const opOpts = operators.map(op => `<option value="${op}" ${cond.operator === op ? 'selected' : ''}>${op}</option>`).join('');
        const gradeOpts = availGrades.map(g => `<option value="${g}" ${cond.grade === g ? 'selected' : ''}>${g}</option>`).join('');
        const scopeOpts = SCOPE_OPTIONS.map(([v, l]) => `<option value="${v}" ${(cond.scope ?? 'all') === v ? 'selected' : ''}>${l}</option>`).join('');
        return `<div class="cond-row" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">
            <span class="grade-pill gp-${cond.grade || 'F'}">${escH(cond.grade || '?')}</span>
            <select class="form-select form-select-sm cond-grade-sel" style="width:85px;">${gradeOpts}</select>
            <span class="cond-text">count</span>
            <select class="form-select form-select-sm cond-op-sel" style="width:65px;">${opOpts}</select>
            <input type="number" class="form-control form-control-sm cond-count-inp" style="width:65px;" min="0" max="99" value="${cond.count ?? 1}">
            <span class="cond-text">subj. in</span>
            <select class="form-select form-select-sm cond-scope-sel" style="width:160px;">${scopeOpts}</select>
            <button type="button" class="btn btn-sm btn-outline-danger remove-cond-btn ms-auto" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}"><i class="ri-close-line"></i></button>
        </div>`;
    }).join('');
}

function buildRuleHTML(rule, idx) {
    const selSt = STATUS_LABELS.find(s => s.key === rule.status_label) || STATUS_LABELS[0];
    const grouping = rule.grade_grouping ?? 'grouped';
    const availGrades = getGradesForGrouping(grouping);
    const labelPills = STATUS_LABELS.map(sl => `<span class="label-pill ${sl.cls} ${rule.status_label === sl.key ? 'active' : ''}" data-idx="${idx}" data-status="${sl.key}"><i class="${sl.icon} me-1"></i>${sl.label}</span>`).join('');
    const groupingOpts = [
        ['grouped', currentGradeScale === 'senior' ? 'Grouped (A=A1, B=B2+B3…)' : 'Grouped (A, B, C…)'],
        ['exact', currentGradeScale === 'senior' ? 'Exact (A1, B2, B3 separately)' : 'Exact (A, B, C separately)']
    ].map(([v, l]) => `<option value="${v}" ${grouping === v ? 'selected' : ''}>${l}</option>`).join('');

    const compCondRows = buildCondRows(rule.compulsory_section?.count_conditions ?? [], idx, 'comp', availGrades);
    const otherCondRows = buildCondRows(rule.other_section?.count_conditions ?? [], idx, 'other', availGrades);
    const gradeOptsComp = availGrades.map(g => `<option>${g}</option>`).join('');
    const gradeOptsOther = availGrades.map(g => `<option>${g}</option>`).join('');
    const avg = rule.average_condition ?? { enabled: false, min_average: 50, logic: 'AND' };
    const statusBadgeClass = selSt.key === 'promoted' ? 'success' : selSt.key === 'trial' ? 'warning' : selSt.key === 'see_principal' ? 'info' : 'danger';

    return `<div class="rule-card" data-rule-idx="${idx}">
        <div class="rule-card-header">
            <span class="rule-num-badge">Rule ${idx + 1}</span>
            <span class="badge bg-${statusBadgeClass}" id="statusBadge_${idx}"><i class="${selSt.icon} me-1"></i>${selSt.label}</span>
            <input type="text" class="form-control form-control-sm rule-name-input" data-idx="${idx}" value="${escH(rule.rule_name)}" placeholder="Rule name">
            <div class="d-flex gap-1 align-items-center ms-auto">
                <button type="button" class="btn btn-sm btn-outline-secondary move-up-btn" data-idx="${idx}"><i class="ri-arrow-up-line"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary move-down-btn" data-idx="${idx}"><i class="ri-arrow-down-line"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-rule-btn" data-idx="${idx}"><i class="ri-delete-bin-line"></i></button>
            </div>
        </div>
        <div class="rule-card-body">
            <div class="mb-3">
                <label class="fw-semibold small d-block mb-2"><i class="ri-award-line me-1"></i>Promotion Outcome</label>
                <div class="label-selector">${labelPills}</div>
            </div>
            <div class="mb-3">
                <label class="fw-semibold small"><i class="ri-git-branch-line me-1"></i>Grade Grouping</label>
                <select class="form-select form-select-sm grouping-sel mt-1" data-idx="${idx}" style="max-width:300px;">${groupingOpts}</select>
            </div>
            <div class="rule-section mb-3">
                <div class="rule-section-header"><span><i class="ri-star-fill text-warning me-2"></i>Section 1 — Compulsory Count</span></div>
                <div class="rule-section-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold small"><i class="ri-bar-chart-line me-1"></i>Grade count conditions</div>
                        <div class="d-flex gap-1">
                            <select class="form-select form-select-sm" id="addCompGrade_${idx}" style="width:80px;">${gradeOptsComp}</select>
                            <button type="button" class="btn btn-outline-primary btn-sm add-comp-cond-btn" data-idx="${idx}"><i class="ri-add-line"></i> Add</button>
                        </div>
                    </div>
                    <div id="compCondRows_${idx}">${compCondRows || '<div class="text-muted small ps-1 py-1">No count conditions.</div>'}</div>
                </div>
            </div>
            <div class="rule-section mb-3">
                <div class="rule-section-header"><span><i class="ri-book-open-line text-primary me-2"></i>Section 2 — Other / All Subjects</span></div>
                <div class="rule-section-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold small"><i class="ri-bar-chart-line me-1"></i>Grade count conditions</div>
                        <div class="d-flex gap-1">
                            <select class="form-select form-select-sm" id="addOtherGrade_${idx}" style="width:80px;">${gradeOptsOther}</select>
                            <button type="button" class="btn btn-outline-primary btn-sm add-other-cond-btn" data-idx="${idx}"><i class="ri-add-line"></i> Add</button>
                        </div>
                    </div>
                    <div id="otherCondRows_${idx}">${otherCondRows || '<div class="text-muted small ps-1 py-1">No count conditions.</div>'}</div>
                </div>
            </div>
            <div class="avg-box">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input avg-toggle-cb" type="checkbox" role="switch" id="avgCb_${idx}" data-idx="${idx}" ${avg.enabled ? 'checked' : ''}>
                    <label class="form-check-label fw-semibold small" for="avgCb_${idx}"><i class="ri-percent-line text-info me-1"></i>Section 3 — Minimum Average (optional)</label>
                </div>
                <div id="avgFields_${idx}" style="${avg.enabled ? '' : 'opacity:.4;pointer-events:none;'}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="number" class="form-control form-control-sm avg-min-inp" data-idx="${idx}" min="0" max="100" step="0.5" value="${avg.min_average ?? 50}">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select form-select-sm avg-logic-sel" data-idx="${idx}">
                                <option value="AND" ${avg.logic === 'AND' ? 'selected' : ''}>AND</option>
                                <option value="OR" ${avg.logic === 'OR' ? 'selected' : ''}>OR</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

function rerenderRules() {
    const container = document.getElementById('rulesContainer');
    if (!promotionRules.length) {
        container.innerHTML = `<div class="no-rules-ph">
            <i class="ri-clipboard-line d-block mb-2" style="font-size:2rem;opacity:.3;"></i>
            Click <strong>Add Rule</strong> to start building.
        </div>`;
        return;
    }
    container.innerHTML = '';
    promotionRules.forEach((rule, idx) => {
        const div = document.createElement('div');
        div.innerHTML = buildRuleHTML(rule, idx);
        container.appendChild(div.firstElementChild);
    });
}

function _getConds(ruleIdx, sec) {
    if (!promotionRules[ruleIdx]) return [];
    return sec === 'comp' ? promotionRules[ruleIdx].compulsory_section.count_conditions : promotionRules[ruleIdx].other_section.count_conditions;
}

document.getElementById('addRuleBtn')?.addEventListener('click', () => {
    promotionRules.push({
        rule_name: '', status_label: 'promoted', priority: promotionRules.length + 1,
        grade_grouping: 'grouped',
        compulsory_section: { subjects: [], count_conditions: [] },
        other_section: { count_conditions: [] },
        average_condition: { enabled: false, min_average: 50, logic: 'AND' },
    });
    rerenderRules();
});

document.getElementById('template_grade_scale')?.addEventListener('change', function () {
    currentGradeScale = this.value;
    rerenderRules();
});

// Same click/change/input handlers as create — copy them verbatim from create
document.addEventListener('click', function (e) {
    const addCompBtn = e.target.closest('.add-comp-cond-btn');
    if (addCompBtn) {
        const idx = parseInt(addCompBtn.dataset.idx);
        const gradeSelect = document.getElementById(`addCompGrade_${idx}`);
        if (gradeSelect && gradeSelect.value) {
            promotionRules[idx].compulsory_section.count_conditions.push({
                grade: gradeSelect.value, operator: '>=', count: 1, scope: 'compulsory_only'
            });
            rerenderRules();
        }
        return;
    }
    const addOtherBtn = e.target.closest('.add-other-cond-btn');
    if (addOtherBtn) {
        const idx = parseInt(addOtherBtn.dataset.idx);
        const gradeSelect = document.getElementById(`addOtherGrade_${idx}`);
        if (gradeSelect && gradeSelect.value) {
            promotionRules[idx].other_section.count_conditions.push({
                grade: gradeSelect.value, operator: '>=', count: 1, scope: 'other_only'
            });
            rerenderRules();
        }
        return;
    }
    const removeCondBtn = e.target.closest('.remove-cond-btn');
    if (removeCondBtn) {
        _getConds(parseInt(removeCondBtn.dataset.rule), removeCondBtn.dataset.sec)
            .splice(parseInt(removeCondBtn.dataset.ci), 1);
        rerenderRules(); return;
    }
    const removeRuleBtn = e.target.closest('.remove-rule-btn');
    if (removeRuleBtn) {
        promotionRules.splice(parseInt(removeRuleBtn.dataset.idx), 1);
        rerenderRules(); return;
    }
    const moveUpBtn = e.target.closest('.move-up-btn');
    if (moveUpBtn) {
        const idx = parseInt(moveUpBtn.dataset.idx);
        if (idx > 0) { [promotionRules[idx - 1], promotionRules[idx]] = [promotionRules[idx], promotionRules[idx - 1]]; rerenderRules(); }
        return;
    }
    const moveDownBtn = e.target.closest('.move-down-btn');
    if (moveDownBtn) {
        const idx = parseInt(moveDownBtn.dataset.idx);
        if (idx < promotionRules.length - 1) { [promotionRules[idx], promotionRules[idx + 1]] = [promotionRules[idx + 1], promotionRules[idx]]; rerenderRules(); }
        return;
    }
    const statusPill = e.target.closest('.label-pill');
    if (statusPill) {
        const idx = parseInt(statusPill.dataset.idx);
        const stat = statusPill.dataset.status;
        promotionRules[idx].status_label = stat;
        statusPill.closest('.label-selector').querySelectorAll('.label-pill').forEach(p => p.classList.toggle('active', p.dataset.status === stat));
        const selSt = STATUS_LABELS.find(s => s.key === stat);
        const badgeEl = document.getElementById(`statusBadge_${idx}`);
        if (badgeEl && selSt) {
            const cls = stat === 'promoted' ? 'success' : stat === 'trial' ? 'warning' : stat === 'see_principal' ? 'info' : 'danger';
            badgeEl.className = `badge bg-${cls}`;
            badgeEl.innerHTML = `<i class="${selSt.icon} me-1"></i>${selSt.label}`;
        }
    }
});

document.addEventListener('change', function (e) {
    const condGradeSel = e.target.closest('.cond-grade-sel');
    if (condGradeSel) {
        const row = condGradeSel.closest('.cond-row');
        const conds = _getConds(parseInt(row.dataset.rule), row.dataset.sec);
        const ci = parseInt(row.dataset.ci);
        if (conds[ci]) {
            conds[ci].grade = condGradeSel.value;
            const pill = row.querySelector('.grade-pill');
            if (pill) { pill.textContent = condGradeSel.value; pill.className = `grade-pill gp-${condGradeSel.value}`; }
        }
        return;
    }
    const condOpSel = e.target.closest('.cond-op-sel');
    if (condOpSel) {
        const row = condOpSel.closest('.cond-row');
        const conds = _getConds(parseInt(row.dataset.rule), row.dataset.sec);
        const ci = parseInt(row.dataset.ci);
        if (conds[ci]) conds[ci].operator = condOpSel.value;
        return;
    }
    const condScopeSel = e.target.closest('.cond-scope-sel');
    if (condScopeSel) {
        const row = condScopeSel.closest('.cond-row');
        const conds = _getConds(parseInt(row.dataset.rule), row.dataset.sec);
        const ci = parseInt(row.dataset.ci);
        if (conds[ci]) conds[ci].scope = condScopeSel.value;
        return;
    }
    const avgToggle = e.target.closest('.avg-toggle-cb');
    if (avgToggle) {
        const idx = parseInt(avgToggle.dataset.idx);
        if (!promotionRules[idx].average_condition) promotionRules[idx].average_condition = {};
        promotionRules[idx].average_condition.enabled = avgToggle.checked;
        const f = document.getElementById(`avgFields_${idx}`);
        if (f) { f.style.opacity = avgToggle.checked ? '1' : '0.4'; f.style.pointerEvents = avgToggle.checked ? 'auto' : 'none'; }
        return;
    }
    const avgLogicSel = e.target.closest('.avg-logic-sel');
    if (avgLogicSel) {
        const idx = parseInt(avgLogicSel.dataset.idx);
        if (!promotionRules[idx].average_condition) promotionRules[idx].average_condition = {};
        promotionRules[idx].average_condition.logic = avgLogicSel.value;
        return;
    }
    const groupingSel = e.target.closest('.grouping-sel');
    if (groupingSel) {
        const idx = parseInt(groupingSel.dataset.idx);
        promotionRules[idx].grade_grouping = groupingSel.value;
        promotionRules[idx].compulsory_section.count_conditions = [];
        promotionRules[idx].other_section.count_conditions = [];
        rerenderRules();
        return;
    }
});

document.addEventListener('input', function (e) {
    const ruleName = e.target.closest('.rule-name-input');
    if (ruleName) { promotionRules[parseInt(ruleName.dataset.idx)].rule_name = ruleName.value; return; }
    const condCountInp = e.target.closest('.cond-count-inp');
    if (condCountInp) {
        const row = condCountInp.closest('.cond-row');
        const conds = _getConds(parseInt(row.dataset.rule), row.dataset.sec);
        const ci = parseInt(row.dataset.ci);
        if (conds[ci]) conds[ci].count = parseInt(condCountInp.value);
        return;
    }
    const avgMinInp = e.target.closest('.avg-min-inp');
    if (avgMinInp) {
        const idx = parseInt(avgMinInp.dataset.idx);
        if (!promotionRules[idx].average_condition) promotionRules[idx].average_condition = {};
        promotionRules[idx].average_condition.min_average = parseFloat(avgMinInp.value);
        return;
    }
});

document.getElementById('editTemplateForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const name = document.getElementById('template_name').value.trim();
    if (!name) { Swal.fire('Validation', 'Please enter a template name.', 'warning'); return; }
    if (!promotionRules.length) { Swal.fire('Validation', 'Add at least one rule.', 'warning'); return; }

    for (const [i, rule] of promotionRules.entries()) {
        if (!rule.rule_name?.trim()) { Swal.fire('Validation', `Rule ${i + 1} needs a name.`, 'warning'); return; }
    }

    const id = document.getElementById('template_id').value;
    const fd = new FormData();
    fd.append('_token', '{{ csrf_token() }}');
    fd.append('_method', 'PUT');
    fd.append('name', name);
    fd.append('description', document.getElementById('template_description').value || '');
    fd.append('grade_scale', document.getElementById('template_grade_scale').value);
    fd.append('promotion_rules', JSON.stringify(promotionRules));
    fd.append('is_active', document.getElementById('template_is_active').checked ? '1' : '0');

    Swal.fire({ title: 'Updating...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`/promotion-templates/${id}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: fd,
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Updated!', text: data.message, timer: 1500, showConfirmButton: false })
                .then(() => window.location.href = '{{ route("promotion.templates.index") }}');
        } else {
            Swal.fire('Error', data.message || 'Failed to update template.', 'error');
        }
    } catch (err) {
        Swal.fire('Error', 'Network error: ' + err.message, 'error');
    }
});

// Initial render
rerenderRules();
</script>
@endsection