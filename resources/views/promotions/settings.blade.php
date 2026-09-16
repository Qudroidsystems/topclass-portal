<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ════════════════════════════════════════════════════════════════════════
   RULE INTERPRETER (unchanged)
   ════════════════════════════════════════════════════════════════════════ */
const RuleInterpreter = (() => {
    const GRADE_LABELS_SENIOR = {
        A1: 'A1 (Distinction)', B2: 'B2 (Very Good)', B3: 'B3 (Good)',
        C4: 'C4 (Credit)', C5: 'C5 (Credit)', C6: 'C6 (Credit)',
        D7: 'D7 (Pass)', E8: 'E8 (Below Pass)', F9: 'F9 (Fail)',
    };
    const GRADE_LABELS_JUNIOR = {
        A: 'A (Excellent)', B: 'B (Good)', C: 'C (Credit)', D: 'D (Pass)', F: 'F (Fail)',
    };
    const GROUP_LABELS_SENIOR = {
        A: 'distinctions (A1)', B: 'very-good/good grades (B2–B3)',
        C: 'credit grades (C4–C6)', D: 'pass grades (D7)',
        E: 'below-pass grades (E8)', F: 'fail grades (F9)',
    };
    const GROUP_LABELS_JUNIOR = {
        A: 'A grades (Excellent)', B: 'B grades (Good)',
        C: 'C grades (Credit)', D: 'D grades (Pass)', F: 'F grades (Fail)',
    };
    function gradeLabel(g, grouping, senior) {
        g = (g || '').toUpperCase();
        if (grouping === 'grouped') return (senior ? GROUP_LABELS_SENIOR : GROUP_LABELS_JUNIOR)[g] || g;
        return (senior ? GRADE_LABELS_SENIOR : GRADE_LABELS_JUNIOR)[g] || g;
    }
    function opPhrase(operator, count, noun) {
        const n = parseInt(count, 10);
        switch (operator) {
            case '>=': return n === 0 ? `any number of ${noun}` : `at least ${n} ${noun}`;
            case '<=': return n === 0 ? `zero ${noun}` : `at most ${n} ${noun}`;
            case '=':  return n === 0 ? `exactly zero ${noun}` : `exactly ${n} ${noun}`;
            case '>':  return `more than ${n} ${noun}`;
            case '<':  return n === 1 ? `zero ${noun}` : `fewer than ${n} ${noun}`;
            default:   return `${operator}${n} ${noun}`;
        }
    }
    function scopePhrase(scope) {
        switch (scope) {
            case 'compulsory_only': return 'compulsory subjects';
            case 'other_only':      return 'non-compulsory subjects';
            default:                return 'all subjects';
        }
    }
    function describeCompSubjectMins(subjects, senior) {
        const withMin = (subjects || []).filter(s => s.min_grade);
        if (!withMin.length) return null;
        if (withMin.every(s => s.min_grade === withMin[0].min_grade)) {
            const label = (senior ? GRADE_LABELS_SENIOR : GRADE_LABELS_JUNIOR)[withMin[0].min_grade.toUpperCase()] || withMin[0].min_grade;
            if (withMin.length === subjects.length && subjects.length > 0) {
                return `every compulsory subject scores at least <strong>${label}</strong>`;
            }
            return `${withMin.length} compulsory subject${withMin.length > 1 ? 's' : ''} score${withMin.length === 1 ? 's' : ''} at least <strong>${label}</strong>`;
        }
        const lines = withMin.map(s => {
            const label = (senior ? GRADE_LABELS_SENIOR : GRADE_LABELS_JUNIOR)[s.min_grade.toUpperCase()] || s.min_grade;
            const name  = s.subject_name ? `<em>${s.subject_name}</em>` : `subject #${s.subject_id}`;
            return `${name} ≥ <strong>${label}</strong>`;
        });
        return lines.join(', ');
    }
    function describeCountCond(cond, grouping, senior) {
        const g = (cond.grade || '').toUpperCase();
        const op = cond.operator || '>=';
        const count = cond.count ?? 1;
        const scope = cond.scope || 'all';
        const gradeTxt = gradeLabel(g, grouping, senior);
        const scopeTxt = scopePhrase(scope);
        const noun = `${gradeTxt} in ${scopeTxt}`;
        return opPhrase(op, count, noun);
    }
    function describeAvgCond(avgCond) {
        if (!avgCond || !avgCond.enabled) return null;
        const min = avgCond.min_average ?? '?';
        const logic = (avgCond.logic || 'AND').toUpperCase();
        const base = `overall average ≥ <strong>${min}%</strong>`;
        return logic === 'OR'
            ? `${base} <em>(OR — this alone can qualify the student)</em>`
            : `${base} <em>(AND — must also be met)</em>`;
    }
    function interpret(rule, snr, ruleIndex) {
        if (!rule || !rule.rule_name) {
            return { summary:'', bullets:[], firesWhen:'Rule has no name yet.', neverFires:false, isCatchAll:false, outcomeKey:'repeat' };
        }
        const grouping = rule.grade_grouping || 'grouped';
        const compSubj = rule.compulsory_section?.subjects || [];
        const compConds = rule.compulsory_section?.count_conditions || [];
        const otherConds = rule.other_section?.count_conditions || [];
        const avgCond = rule.average_condition;
        const clauses = [];
        const subjMinLine = describeCompSubjectMins(compSubj, snr);
        if (subjMinLine) clauses.push(subjMinLine);
        for (const c of compConds) {
            if (!(c.grade || '').trim()) continue;
            clauses.push(describeCountCond({ ...c, scope: c.scope || 'compulsory_only' }, grouping, snr));
        }
        for (const c of otherConds) {
            if (!(c.grade || '').trim()) continue;
            clauses.push(describeCountCond(c, grouping, snr));
        }
        const avgLine = describeAvgCond(avgCond);
        if (avgLine) clauses.push(avgLine);
        const hasRealConditions = !!(subjMinLine || compConds.some(c => c.grade) || otherConds.some(c => c.grade) || (avgCond && avgCond.enabled));
        let neverFires = false;
        const allConds = [...compConds, ...otherConds];
        const byScopeGrade = {};
        for (const c of allConds) {
            const key = `${c.scope || 'all'}__${(c.grade || '').toUpperCase()}`;
            if (!byScopeGrade[key]) byScopeGrade[key] = [];
            byScopeGrade[key].push(c);
        }
        for (const conds of Object.values(byScopeGrade)) {
            if (conds.length < 2) continue;
            const mins = conds.filter(c => ['>=', '>'].includes(c.operator)).map(c => parseInt(c.count ?? 0));
            const maxs = conds.filter(c => ['<=', '<'].includes(c.operator)).map(c => parseInt(c.count ?? 0));
            if (mins.length && maxs.length) {
                const maxMin = Math.max(...mins);
                const minMax = Math.min(...maxs);
                if (maxMin > minMax) neverFires = true;
            }
        }
        const outcome = rule.status_label || 'promoted';
        const outcomeMap = { promoted:'Promoted', trial:'Promoted on Trial', see_principal:'See Principal', repeat:'Repeat' };
        const outcomeTxt = outcomeMap[outcome] || outcome;
        let firesWhen;
        if (!hasRealConditions) {
            firesWhen = `<strong>Always matches (catch-all)</strong> — no conditions set. Every student who reaches this rule gets <em>${outcomeTxt}</em>.`;
        } else if (neverFires) {
            firesWhen = `<span style="color:#dc2626;font-weight:700;">⚠ Contradictory conditions</span> — this rule can never match any student.`;
        } else {
            const joined = clauses.map((c, i) => i === 0 ? c : `<span class="ri-interp-and">AND</span> ${c}`).join(' ');
            firesWhen = `<strong>Fires when:</strong> ${joined} → <strong>${outcomeTxt}</strong>`;
        }
        return { summary:`Rule ${ruleIndex}: ${rule.rule_name}`, bullets:clauses, firesWhen, neverFires, isCatchAll: !hasRealConditions, outcomeKey: outcome };
    }
    function interpretAll(rules, snr, ruleLogic, requiredAverage) {
        const results = rules.map((r, i) => interpret(r, snr, i + 1));
        const catchAllIdx = results.findIndex(r => r.isCatchAll);
        const hasUnreachable = catchAllIdx >= 0 && catchAllIdx < rules.length - 1;
        const logicLabels = {
            grade_count: 'Grade count rules only — checked top-to-bottom, first match wins. If no rule matches → Advice to Repeat.',
            average_only: `Minimum average only — student passes if overall average ≥ ${requiredAverage !== undefined && requiredAverage !== null && requiredAverage !== '' ? requiredAverage : '?'}%.`,
            both: 'Grade count AND average — both evaluated together. Average logic (AND/OR) is set per-rule.',
        };
        return { rules: results, logicDescription: logicLabels[ruleLogic] || '', hasUnreachable, unreachableFrom: catchAllIdx >= 0 ? catchAllIdx + 2 : null };
    }
    function renderPanel(interp) {
        if (!interp) return '';
        const { firesWhen, neverFires, isCatchAll, bullets, outcomeKey } = interp;
        const colorMap = { promoted:'#dcfce7', trial:'#fef9c3', see_principal:'#e0f2fe', repeat:'#fee2e2' };
        const borderMap = { promoted:'#16a34a', trial:'#ca8a04', see_principal:'#0284c7', repeat:'#dc2626' };
        const bg = neverFires ? '#fff1f2' : (colorMap[outcomeKey] || '#f8fafc');
        const border = neverFires ? '#dc2626' : (borderMap[outcomeKey] || '#cbd5e1');
        const icon = neverFires ? 'ri-error-warning-line' : isCatchAll ? 'ri-git-branch-line' : 'ri-lightbulb-line';
        let html = `<div class="rule-interp-panel" style="background:${bg};border:1.5px solid ${border};border-radius:10px;padding:12px 16px;margin-top:14px;font-size:12.5px;line-height:1.7;">
            <div style="display:flex;align-items:flex-start;gap:8px;">
                <i class="${icon}" style="font-size:16px;color:${border};flex-shrink:0;margin-top:2px;"></i>
                <div style="flex:1;">${firesWhen}</div>
            </div>`;
        if (bullets.length > 1 && !neverFires && !isCatchAll) {
            html += `<ul style="margin:8px 0 0 24px;padding:0;list-style:disc;">`;
            for (const b of bullets) html += `<li style="margin-bottom:3px;">${b}</li>`;
            html += `</ul>`;
        }
        html += `</div>`;
        return html;
    }
    return { interpret, interpretAll, renderPanel };
})();

/* ════════════════════════════════════════════════════════════════════════
   STATE
   ════════════════════════════════════════════════════════════════════════ */
let promotionRules = [];
let gradeScale = ['A1','B2','B3','C4','C5','C6','D7','E8','F9'];
let isSenior = true;
let totalSubjects = 0;
let compulsoryCount = 0;
let otherCount = 0;
let classPassAvg = null;
let compulsorySubjects = [];
let refreshInFlight = false;

const GRADE_SCALES = {
    senior: ['A1','B2','B3','C4','C5','C6','D7','E8','F9'],
    junior: ['A','B','C','D','F'],
};
const GROUPED_SENIOR = { A:['A1'], B:['B2','B3'], C:['C4','C5','C6'], D:['D7'], E:['E8'], F:['F9'] };
const GROUPED_JUNIOR = { A:['A'], B:['B'], C:['C'], D:['D'], F:['F'] };

const STATUS_LABELS = [
    { key:'promoted',       label:'Promoted',                 cls:'lp-promoted',  icon:'ri-checkbox-circle-line' },
    { key:'trial',          label:'Promoted on Trial',        cls:'lp-trial',     icon:'ri-time-line' },
    { key:'see_principal',  label:'Advised to See Principal', cls:'lp-principal', icon:'ri-user-star-line' },
    { key:'repeat',         label:'Advice to Repeat',         cls:'lp-repeat',    icon:'ri-repeat-line' },
];
const SCOPE_OPTIONS = [
    ['all',             '📚 All Subjects'],
    ['compulsory_only', '⭐ Compulsory Only'],
    ['other_only',      '📖 Other Only'],
];

function getGroupedGrades() { return isSenior ? Object.keys(GROUPED_SENIOR) : Object.keys(GROUPED_JUNIOR); }
function getExactGrades()   { return gradeScale; }
function getGradesForGrouping(g) { return g === 'grouped' ? getGroupedGrades() : getExactGrades(); }
function escH(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function _getConds(ruleIdx, sec) {
    if (!promotionRules[ruleIdx]) return [];
    return sec === 'comp'
        ? promotionRules[ruleIdx].compulsory_section.count_conditions
        : promotionRules[ruleIdx].other_section.count_conditions;
}

/* ════════════════════════════════════════════════════════════════════════
   HTML BUILDERS
   ════════════════════════════════════════════════════════════════════════ */
function buildCondRows(conds, ruleIdx, section, availGrades) {
    if (!conds.length) return '';
    return conds.map((cond, ci) => {
        const operators = ['>=', '<=', '=', '>', '<'];
        const opOpts = operators.map(op => `<option value="${op}" ${cond.operator === op ? 'selected' : ''}>${op}</option>`).join('');
        const gradeOpts = availGrades.map(g => `<option value="${g}" ${cond.grade === g ? 'selected' : ''}>${g}</option>`).join('');
        const scopeOpts = SCOPE_OPTIONS.map(([v, l]) => `<option value="${v}" ${(cond.scope ?? 'all') === v ? 'selected' : ''}>${l}</option>`).join('');
        return `<div class="cond-row" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">
            <span class="grade-pill gp-${cond.grade || 'F'}" id="gp_${ruleIdx}_${section}_${ci}">${escH(cond.grade || '?')}</span>
            <select class="form-select form-select-sm cond-grade-sel" style="width:85px;" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">${gradeOpts}</select>
            <span class="cond-text">count</span>
            <select class="form-select form-select-sm cond-op-sel" style="width:65px;" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">${opOpts}</select>
            <input type="number" class="form-control form-control-sm cond-count-inp" style="width:65px;" min="0" max="99" value="${cond.count ?? 1}" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">
            <span class="cond-text">subj. in</span>
            <select class="form-select form-select-sm cond-scope-sel" style="width:160px;" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}">${scopeOpts}</select>
            <button class="btn btn-sm btn-outline-danger remove-cond-btn ms-auto" data-rule="${ruleIdx}" data-sec="${section}" data-ci="${ci}" title="Remove"><i class="ri-close-line"></i></button>
        </div>`;
    }).join('');
}

function buildRuleHTML(rule, idx) {
    const selSt = STATUS_LABELS.find(s => s.key === rule.status_label) || STATUS_LABELS[0];
    const grouping = rule.grade_grouping ?? 'grouped';
    const availGrades = getGradesForGrouping(grouping);
    const labelPills = STATUS_LABELS.map(sl => `<span class="label-pill ${sl.cls} ${rule.status_label === sl.key ? 'active' : ''}" data-idx="${idx}" data-status="${sl.key}"><i class="${sl.icon} me-1"></i>${sl.label}</span>`).join('');
    const groupingOpts = [
        ['grouped', isSenior ? 'Grouped (A=A1, B=B2+B3…)' : 'Grouped (A, B, C…)'],
        ['exact',   isSenior ? 'Exact (A1, B2, B3 separately)' : 'Exact (A, B, C separately)'],
    ].map(([v, l]) => `<option value="${v}" ${grouping === v ? 'selected' : ''}>${l}</option>`).join('');

    const subjects = rule.compulsory_section?.subjects ?? [];
    const compSubjRowsHtml = !subjects.length
        ? `<div class="text-muted small p-3"><i class="ri-information-line me-1"></i>No compulsory subjects assigned to this class.</div>`
        : subjects.map((subj, si) => `<div class="comp-subj-row">
            <input type="hidden" class="subject-id-field" data-idx="${idx}" data-si="${si}" value="${subj.subject_id || ''}">
            <div>
                <span class="subj-name">${escH(subj.subject_name)}</span>
                ${subj.subject_code ? `<span class="subj-code ms-1">(${escH(subj.subject_code)})</span>` : ''}
                ${subj.default_min_grade ? `<span class="default-badge ms-2"><i class="ri-information-line"></i> default: ${subj.default_min_grade}</span>` : ''}
                ${subj.override && subj.min_grade && subj.min_grade !== subj.default_min_grade ? `<span class="badge bg-warning text-dark ms-1">overridden</span>` : ''}
            </div>
            <select class="grade-sel comp-subj-grade-sel" data-idx="${idx}" data-si="${si}">
                ${['', ...gradeScale].map(g => `<option value="${g}" ${subj.min_grade === g ? 'selected' : ''}>${g === '' ? '— Any (default pass/fail)' : g}</option>`).join('')}
            </select>
        </div>`).join('');

    const gradeOptsComp  = availGrades.map(g => `<option>${g}</option>`).join('');
    const gradeOptsOther = availGrades.map(g => `<option>${g}</option>`).join('');
    const compCondRows   = buildCondRows(rule.compulsory_section?.count_conditions ?? [], idx, 'comp',  availGrades);
    const otherCondRows  = buildCondRows(rule.other_section?.count_conditions ?? [], idx, 'other', availGrades);
    const avg = rule.average_condition ?? { enabled: false, min_average: classPassAvg ?? 50, logic: 'AND' };
    const statusBadgeClass = selSt.key === 'promoted' ? 'success' : selSt.key === 'trial' ? 'warning' : selSt.key === 'see_principal' ? 'info' : 'danger';

    return `<div class="rule-card" data-rule-idx="${idx}">
      <div class="rule-card-header">
        <span class="rule-num-badge">Rule ${idx + 1}</span>
        <span class="badge bg-${statusBadgeClass}" id="statusBadge_${idx}"><i class="${selSt.icon} me-1"></i>${selSt.label}</span>
        <input type="text" class="form-control form-control-sm rule-name-input" data-idx="${idx}" value="${escH(rule.rule_name)}" placeholder="Rule name">
        <div class="d-flex gap-1 align-items-center ms-auto">
          <span class="text-muted small me-1">Priority:</span>
          <input type="number" class="form-control form-control-sm priority-input" data-idx="${idx}" value="${rule.priority ?? idx + 1}" min="1" style="width:65px;">
          <button class="btn btn-sm btn-outline-secondary move-up-btn"   data-idx="${idx}" title="Move up"><i class="ri-arrow-up-line"></i></button>
          <button class="btn btn-sm btn-outline-secondary move-down-btn" data-idx="${idx}" title="Move down"><i class="ri-arrow-down-line"></i></button>
          <button class="btn btn-sm btn-outline-danger remove-rule-btn"  data-idx="${idx}" title="Remove rule"><i class="ri-delete-bin-line"></i></button>
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
          <div class="rule-section-header">
            <span><i class="ri-star-fill text-warning me-2"></i>Section 1 — Compulsory Subjects <small class="text-muted fw-normal ms-2">(${subjects.length} subjects)</small></span>
          </div>
          <div class="rule-section-body">
            <div class="mb-3">
              <div class="fw-semibold small mb-2"><i class="ri-shield-check-line text-warning me-1"></i>Per-subject minimum grade</div>
              <div id="compSubjRows_${idx}">${compSubjRowsHtml}</div>
            </div>
            <hr class="my-2">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold small"><i class="ri-bar-chart-line me-1"></i>Grade count conditions across compulsory subjects</div>
                <div class="d-flex gap-1 align-items-center">
                  <select class="form-select form-select-sm" id="addCompGrade_${idx}" style="width:80px;">${gradeOptsComp}</select>
                  <button class="btn btn-outline-primary btn-sm add-comp-cond-btn" data-idx="${idx}"><i class="ri-add-line"></i> Add</button>
                </div>
              </div>
              <div id="compCondRows_${idx}">${compCondRows || '<div class="text-muted small ps-1 py-1">No count conditions — click Add above.</div>'}</div>
            </div>
          </div>
        </div>
        <div class="rule-section mb-3">
          <div class="rule-section-header">
            <span><i class="ri-book-open-line text-primary me-2"></i>Section 2 — Other / All Subjects Grade Count <small class="text-muted fw-normal ms-2">(${otherCount} other subjects)</small></span>
          </div>
          <div class="rule-section-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-semibold small"><i class="ri-bar-chart-line me-1"></i>Grade count conditions</div>
              <div class="d-flex gap-1 align-items-center">
                <select class="form-select form-select-sm" id="addOtherGrade_${idx}" style="width:80px;">${gradeOptsOther}</select>
                <button class="btn btn-outline-primary btn-sm add-other-cond-btn" data-idx="${idx}"><i class="ri-add-line"></i> Add</button>
              </div>
            </div>
            <div id="otherCondRows_${idx}">${otherCondRows || '<div class="text-muted small ps-1 py-1">No count conditions — click Add above.</div>'}</div>
          </div>
        </div>
        <div class="avg-box">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input avg-toggle-cb" type="checkbox" role="switch" id="avgCb_${idx}" data-idx="${idx}" ${avg.enabled ? 'checked' : ''}>
            <label class="form-check-label fw-semibold small" for="avgCb_${idx}"><i class="ri-percent-line text-info me-1"></i>Section 3 — Minimum Average Condition (optional)</label>
          </div>
          <div id="avgFields_${idx}" style="${avg.enabled ? '' : 'opacity:.4;pointer-events:none;'}">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Min Average (%)</label>
                <input type="number" class="form-control form-control-sm avg-min-inp" data-idx="${idx}" min="0" max="100" step="0.5" value="${avg.min_average ?? (classPassAvg ?? 50)}">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Logic with Sections 1+2</label>
                <select class="form-select form-select-sm avg-logic-sel" data-idx="${idx}">
                  <option value="AND" ${avg.logic === 'AND' ? 'selected' : ''}>AND (all sections must pass)</option>
                  <option value="OR"  ${avg.logic === 'OR'  ? 'selected' : ''}>OR (average alone qualifies)</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div id="ruleInterp_${idx}"></div>
      </div>
    </div>`;
}

/* ════════════════════════════════════════════════════════════════════════
   RENDER
   ════════════════════════════════════════════════════════════════════════ */
function rerenderRules() {
    const container = document.getElementById('rulesContainer');
    const noMsg = document.getElementById('noRulesMsg');
    if (!promotionRules.length) {
        if (container) {
            container.innerHTML = '';
            if (noMsg) { container.appendChild(noMsg); noMsg.style.display = 'block'; }
        }
        updateGlobalInterpPanel();
        return;
    }
    if (noMsg) noMsg.style.display = 'none';
    if (container) {
        container.innerHTML = '';
        promotionRules.forEach((rule, idx) => {
            const div = document.createElement('div');
            div.innerHTML = buildRuleHTML(rule, idx);
            container.appendChild(div.firstElementChild);
        });
    }
    updateRuleInterpretations();
}

function updateRuleInterpretations() {
    promotionRules.forEach((rule, idx) => {
        const c = document.getElementById(`ruleInterp_${idx}`);
        if (!c) return;
        c.innerHTML = RuleInterpreter.renderPanel(RuleInterpreter.interpret(rule, isSenior, idx + 1));
    });
    updateGlobalInterpPanel();
}

function updateGlobalInterpPanel() {
    const panel = document.getElementById('globalInterpPanel');
    if (!panel) return;
    if (!promotionRules.length) { panel.innerHTML = ''; return; }
    const ruleLogic = document.getElementById('rule_logic')?.value || 'grade_count';
    const reqAvg = document.getElementById('promotion_pass_average')?.value;
    const { rules, logicDescription, hasUnreachable, unreachableFrom } = RuleInterpreter.interpretAll(promotionRules, isSenior, ruleLogic, reqAvg);
    let html = `<div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:10px;padding:12px 16px;margin-bottom:14px;font-size:12.5px;line-height:1.7;">
        <div style="font-weight:700;color:#0c4a6e;margin-bottom:6px;"><i class="ri-route-line me-1"></i>Evaluation flow — ${rules.length} rule${rules.length > 1 ? 's' : ''}</div>
        <div style="color:#075985;">${logicDescription}</div>`;
    if (hasUnreachable) {
        html += `<div style="margin-top:8px;color:#b45309;font-weight:600;background:#fef9c3;padding:6px 10px;border-radius:8px;">
            <i class="ri-alert-line me-1"></i>Rule ${unreachableFrom} onwards is unreachable — Rule ${unreachableFrom - 1} has no conditions.
        </div>`;
    }
    const colorMap = { promoted:'#16a34a', trial:'#ca8a04', see_principal:'#0284c7', repeat:'#dc2626' };
    html += `<div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;">`;
    rules.forEach((r, i) => {
        const c = r.neverFires ? '#dc2626' : (colorMap[r.outcomeKey] || '#6b7280');
        const name = r.summary.replace(/^Rule \d+: /, '') || `Rule ${i+1}`;
        html += `<span style="background:${c};color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;max-width:140px;overflow:hidden;text-overflow:ellipsis;display:inline-block;">
            ${i+1}. ${escH(name.length > 16 ? name.slice(0,14)+'…' : name)}
            ${r.neverFires ? ' ⚠' : ''}
        </span>`;
        if (i < rules.length - 1) html += `<i class="ri-arrow-right-s-line" style="color:#94a3b8;font-size:16px;"></i>`;
    });
    html += `<i class="ri-arrow-right-s-line" style="color:#94a3b8;font-size:16px;"></i>`;
    html += `<span style="background:#6b7280;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">No match → Repeat</span>`;
    html += `</div></div>`;
    panel.innerHTML = html;
}

/* ════════════════════════════════════════════════════════════════════════
   EVENT DELEGATION
   ════════════════════════════════════════════════════════════════════════ */
function setupEventDelegation() {
    const container = document.getElementById('rulesContainer');
    if (!container) return;
    container.removeEventListener('click', handleContainerClick);
    container.addEventListener('click', handleContainerClick);
    container.removeEventListener('change', handleContainerChange);
    container.addEventListener('change', handleContainerChange);
    container.removeEventListener('input', handleContainerInput);
    container.addEventListener('input', handleContainerInput);
}

function handleContainerClick(e) {
    const addCompBtn = e.target.closest('.add-comp-cond-btn');
    if (addCompBtn) {
        e.preventDefault();
        const idx = parseInt(addCompBtn.dataset.idx);
        const gs = document.getElementById(`addCompGrade_${idx}`);
        if (gs && gs.value) {
            if (!promotionRules[idx].compulsory_section.count_conditions) promotionRules[idx].compulsory_section.count_conditions = [];
            promotionRules[idx].compulsory_section.count_conditions.push({ grade: gs.value, operator: '>=', count: 1, scope: 'compulsory_only' });
            rerenderRules();
        } else Swal.fire('Warning', 'Please select a grade first.', 'warning');
        return;
    }
    const addOtherBtn = e.target.closest('.add-other-cond-btn');
    if (addOtherBtn) {
        e.preventDefault();
        const idx = parseInt(addOtherBtn.dataset.idx);
        const gs = document.getElementById(`addOtherGrade_${idx}`);
        if (gs && gs.value) {
            if (!promotionRules[idx].other_section.count_conditions) promotionRules[idx].other_section.count_conditions = [];
            promotionRules[idx].other_section.count_conditions.push({ grade: gs.value, operator: '>=', count: 1, scope: 'other_only' });
            rerenderRules();
        } else Swal.fire('Warning', 'Please select a grade first.', 'warning');
        return;
    }
    const removeCondBtn = e.target.closest('.remove-cond-btn');
    if (removeCondBtn) {
        e.preventDefault();
        _getConds(parseInt(removeCondBtn.dataset.rule), removeCondBtn.dataset.sec).splice(parseInt(removeCondBtn.dataset.ci), 1);
        rerenderRules(); return;
    }
    const removeRuleBtn = e.target.closest('.remove-rule-btn');
    if (removeRuleBtn) {
        e.preventDefault();
        promotionRules.splice(parseInt(removeRuleBtn.dataset.idx), 1);
        rerenderRules(); return;
    }
    const moveUpBtn = e.target.closest('.move-up-btn');
    if (moveUpBtn) {
        e.preventDefault();
        const idx = parseInt(moveUpBtn.dataset.idx);
        if (idx > 0) { [promotionRules[idx - 1], promotionRules[idx]] = [promotionRules[idx], promotionRules[idx - 1]]; rerenderRules(); }
        return;
    }
    const moveDownBtn = e.target.closest('.move-down-btn');
    if (moveDownBtn) {
        e.preventDefault();
        const idx = parseInt(moveDownBtn.dataset.idx);
        if (idx < promotionRules.length - 1) { [promotionRules[idx], promotionRules[idx + 1]] = [promotionRules[idx + 1], promotionRules[idx]]; rerenderRules(); }
        return;
    }
    const statusPill = e.target.closest('.label-pill');
    if (statusPill) {
        e.preventDefault();
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
        updateRuleInterpretations();
        return;
    }
}

function handleContainerChange(e) {
    const compGradeSel = e.target.closest('.comp-subj-grade-sel');
    if (compGradeSel) {
        const idx = parseInt(compGradeSel.dataset.idx), si = parseInt(compGradeSel.dataset.si);
        promotionRules[idx].compulsory_section.subjects[si].min_grade = compGradeSel.value;
        promotionRules[idx].compulsory_section.subjects[si].override = !!compGradeSel.value;
        updateRuleInterpretations(); return;
    }
    const groupingSel = e.target.closest('.grouping-sel');
    if (groupingSel) {
        const idx = parseInt(groupingSel.dataset.idx);
        promotionRules[idx].grade_grouping = groupingSel.value;
        promotionRules[idx].compulsory_section.count_conditions = [];
        promotionRules[idx].other_section.count_conditions = [];
        rerenderRules(); return;
    }
    const condGradeSel = e.target.closest('.cond-grade-sel');
    if (condGradeSel) {
        const ri = parseInt(condGradeSel.dataset.rule), sec = condGradeSel.dataset.sec, ci = parseInt(condGradeSel.dataset.ci);
        const conds = _getConds(ri, sec);
        if (conds[ci]) {
            conds[ci].grade = condGradeSel.value;
            const pill = document.getElementById(`gp_${ri}_${sec}_${ci}`);
            if (pill) { pill.textContent = condGradeSel.value; pill.className = `grade-pill gp-${condGradeSel.value}`; }
        }
        updateRuleInterpretations(); return;
    }
    const condOpSel = e.target.closest('.cond-op-sel');
    if (condOpSel) {
        const ri = parseInt(condOpSel.dataset.rule), sec = condOpSel.dataset.sec, ci = parseInt(condOpSel.dataset.ci);
        const conds = _getConds(ri, sec);
        if (conds[ci]) conds[ci].operator = condOpSel.value;
        updateRuleInterpretations(); return;
    }
    const condScopeSel = e.target.closest('.cond-scope-sel');
    if (condScopeSel) {
        const ri = parseInt(condScopeSel.dataset.rule), sec = condScopeSel.dataset.sec, ci = parseInt(condScopeSel.dataset.ci);
        const conds = _getConds(ri, sec);
        if (conds[ci]) conds[ci].scope = condScopeSel.value;
        updateRuleInterpretations(); return;
    }
    const avgToggle = e.target.closest('.avg-toggle-cb');
    if (avgToggle) {
        const idx = parseInt(avgToggle.dataset.idx);
        if (!promotionRules[idx].average_condition) promotionRules[idx].average_condition = {};
        promotionRules[idx].average_condition.enabled = avgToggle.checked;
        const f = document.getElementById(`avgFields_${idx}`);
        if (f) { f.style.opacity = avgToggle.checked ? '1' : '0.4'; f.style.pointerEvents = avgToggle.checked ? 'auto' : 'none'; }
        updateRuleInterpretations(); return;
    }
    const avgLogicSel = e.target.closest('.avg-logic-sel');
    if (avgLogicSel) {
        const idx = parseInt(avgLogicSel.dataset.idx);
        if (!promotionRules[idx].average_condition) promotionRules[idx].average_condition = {};
        promotionRules[idx].average_condition.logic = avgLogicSel.value;
        updateRuleInterpretations(); return;
    }
}

function handleContainerInput(e) {
    const ruleName = e.target.closest('.rule-name-input');
    if (ruleName) { promotionRules[parseInt(ruleName.dataset.idx)].rule_name = ruleName.value; updateRuleInterpretations(); return; }
    const priorityInp = e.target.closest('.priority-input');
    if (priorityInp) { promotionRules[parseInt(priorityInp.dataset.idx)].priority = parseInt(priorityInp.value); return; }
    const condCountInp = e.target.closest('.cond-count-inp');
    if (condCountInp) {
        const ri = parseInt(condCountInp.dataset.rule), sec = condCountInp.dataset.sec, ci = parseInt(condCountInp.dataset.ci);
        const conds = _getConds(ri, sec);
        if (conds[ci]) conds[ci].count = parseInt(condCountInp.value);
        updateRuleInterpretations(); return;
    }
    const avgMinInp = e.target.closest('.avg-min-inp');
    if (avgMinInp) {
        const idx = parseInt(avgMinInp.dataset.idx);
        if (!promotionRules[idx].average_condition) promotionRules[idx].average_condition = {};
        promotionRules[idx].average_condition.min_average = parseFloat(avgMinInp.value);
        updateRuleInterpretations(); return;
    }
}

/* ════════════════════════════════════════════════════════════════════════
   REFRESH CLASS INFO
   ════════════════════════════════════════════════════════════════════════ */
async function refreshClassInfo() {
    const classId   = document.getElementById('schoolclass_id').value;
    const termId    = document.getElementById('term_id').value;
    const sessionId = document.getElementById('session_id').value;
    const addBtn    = document.getElementById('addRuleBtn');
    const loadEl    = document.getElementById('subjectLoadStatus');
    const summaryEl = document.getElementById('subjectSummary');
    const scopeInfo = document.getElementById('ruleScopeInfo');

    // GUARD: no class → do nothing
    if (!classId) {
        scopeInfo.textContent = '';
        summaryEl.style.display = 'none';
        addBtn.disabled = true;
        return;
    }

    // GUARD: prevent overlapping fetches
    if (refreshInFlight) return;
    refreshInFlight = true;

    const currentAvg = document.getElementById('promotion_pass_average').value;
    const hasCurrentAvg = currentAvg !== '' && currentAvg !== null && currentAvg !== undefined;

    addBtn.disabled = true;
    summaryEl.style.display = 'none';
    loadEl.style.display = 'block';

    try {
        let url = `/promotion-settings/class-promotion-data?classid=${encodeURIComponent(classId)}`;
        if (termId && termId !== '')       url += `&termid=${encodeURIComponent(termId)}`;
        if (sessionId && sessionId !== '') url += `&sessionid=${encodeURIComponent(sessionId)}`;

        const res = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
        });

        loadEl.style.display = 'none';

        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await res.text();
            console.error('[refreshClassInfo] Non-JSON response:', {
                status: res.status,
                contentType,
                body: text.substring(0, 800),
            });
            let hint = '';
            if (res.status === 302 || res.status === 401) hint = 'Session expired. Please refresh the page.';
            else if (res.status === 404) hint = 'Endpoint not found. Check routes.';
            else if (res.status === 500) hint = 'Server error. Check storage/logs/laravel.log.';
            else hint = `HTTP ${res.status} — ${res.statusText}`;

            summaryEl.innerHTML = `<div class="alert alert-danger py-2 mb-0">
                <i class="ri-error-warning-line me-1"></i><strong>${escH(hint)}</strong>
            </div>`;
            summaryEl.style.display = 'block';
            return;
        }

        const data = await res.json();

        if (!data.success) {
            summaryEl.innerHTML = `<div class="alert alert-danger py-2 mb-0">
                <i class="ri-error-warning-line me-1"></i>${escH(data.message || 'Failed to load class data.')}
            </div>`;
            summaryEl.style.display = 'block';
            return;
        }

        isSenior          = !!data.is_senior;
        totalSubjects     = data.total_subjects    ?? 0;
        compulsoryCount   = data.compulsory_count  ?? 0;
        otherCount        = data.other_count       ?? 0;
        classPassAvg      = data.pass_average      ?? null;
        gradeScale        = (data.grade_scale && data.grade_scale.length)
                                ? data.grade_scale
                                : GRADE_SCALES[isSenior ? 'senior' : 'junior'];

        compulsorySubjects = (data.compulsory_subjects ?? []).map(cs => ({
            subject_id:        cs.id,
            subject_name:      cs.subject,
            subject_code:      cs.subject_code,
            default_min_grade: cs.default_min_grade || '',
            min_grade:         cs.default_min_grade || '',
            override:          false,
        }));

        if (classPassAvg !== null && !hasCurrentAvg) {
            document.getElementById('promotion_pass_average').value = classPassAvg;
            document.getElementById('avg_slider').value = classPassAvg;
        } else if (hasCurrentAvg) {
            document.getElementById('promotion_pass_average').value = currentAvg;
            document.getElementById('avg_slider').value = currentAvg;
        }

        const scaleLabel = isSenior ? 'Senior (A1–F9)' : 'Junior (A–F)';
        scopeInfo.textContent = `${totalSubjects} total | ${compulsoryCount} compulsory | ${otherCount} other | ${scaleLabel}`;

        let summaryHtml = `<div class="alert alert-success py-2 mb-0">
            <i class="ri-checkbox-circle-line me-1"></i>
            <strong>${totalSubjects}</strong> total &nbsp;|&nbsp;
            <strong>${compulsoryCount}</strong> compulsory &nbsp;|&nbsp;
            <strong>${otherCount}</strong> other &nbsp;|&nbsp;
            <strong>${scaleLabel}</strong>`;
        if (compulsoryCount > 0) {
            summaryHtml += `<br><small class="text-muted mt-1 d-block"><i class="ri-star-fill text-warning me-1"></i>${compulsoryCount} compulsory subject${compulsoryCount > 1 ? 's' : ''} loaded.</small>`;
        } else {
            summaryHtml += `<br><small class="text-muted mt-1 d-block"><i class="ri-information-line me-1"></i>No compulsory subjects assigned to this class.</small>`;
        }
        summaryHtml += `</div>`;
        summaryEl.innerHTML = summaryHtml;
        summaryEl.style.display = 'block';
        addBtn.disabled = false;

        // Re-sync existing rules with fresh subject list
        if (promotionRules.length > 0) {
            promotionRules = promotionRules.map(rule => {
                if (!rule.compulsory_section) rule.compulsory_section = { subjects: [], count_conditions: [] };
                if (!rule.other_section)      rule.other_section      = { count_conditions: [] };

                const existing = (rule.compulsory_section.subjects || []).reduce((m, s) => {
                    if (s.subject_id) m[String(s.subject_id)] = s;
                    return m;
                }, {});

                rule.compulsory_section.subjects = compulsorySubjects.map(cs => ({
                    subject_id:        cs.subject_id,
                    subject_name:      cs.subject_name,
                    subject_code:      cs.subject_code,
                    default_min_grade: cs.default_min_grade ?? '',
                    min_grade:         existing[String(cs.subject_id)]?.min_grade ?? cs.default_min_grade ?? '',
                    override:          !!(existing[String(cs.subject_id)]?.min_grade),
                }));
                return rule;
            });
        }

        rerenderRules();

    } catch (err) {
        loadEl.style.display = 'none';
        console.error('[refreshClassInfo] Exception:', err);
        summaryEl.innerHTML = `<div class="alert alert-danger py-2 mb-0">
            <i class="ri-error-warning-line me-1"></i>
            <strong>Network error:</strong> ${escH(err.message)}
        </div>`;
        summaryEl.style.display = 'block';
    } finally {
        refreshInFlight = false;
    }
}

/* ════════════════════════════════════════════════════════════════════════
   MODAL OPEN / RESET
   ════════════════════════════════════════════════════════════════════════ */
function openModal() {
    new bootstrap.Modal(document.getElementById('settingModal')).show();
}

function resetModal() {
    ['setting_id','session_id','term_id','template_id_input','promotion_pass_average'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('schoolclass_id').value = '';
    document.getElementById('promoted_label').value = 'Promoted';
    document.getElementById('trial_label').value = 'Promoted on Trial';
    document.getElementById('see_principal_label').value = 'Advised to See Principal';
    document.getElementById('repeat_label').value = 'Advice to Repeat';
    document.getElementById('rule_logic').value = 'grade_count';
    document.getElementById('avg_slider').value = 50;
    document.getElementById('modal_is_active').checked = true;
    document.getElementById('globalAvgSection').style.display = 'none';
    document.getElementById('subjectSummary').style.display = 'none';
    document.getElementById('subjectSummary').innerHTML = '';
    document.getElementById('subjectLoadStatus').style.display = 'none';
    document.getElementById('addRuleBtn').disabled = true;
    document.getElementById('templateSelect').value = '';
    document.getElementById('loadTemplateBtn').disabled = true;
    document.getElementById('templateStatus').textContent = '';
    document.getElementById('ruleScopeInfo').textContent = '';
    const badge = document.getElementById('modalActiveBadge');
    if (badge) { badge.className = 'active-badge is-active'; badge.innerHTML = '<i class="ri-checkbox-circle-line"></i> Active'; }
    promotionRules = [];
    gradeScale = GRADE_SCALES.senior;
    isSenior = true;
    totalSubjects = 0;
    compulsoryCount = 0;
    otherCount = 0;
    classPassAvg = null;
    compulsorySubjects = [];
    rerenderRules();
}

/* ════════════════════════════════════════════════════════════════════════
   EDIT
   ════════════════════════════════════════════════════════════════════════ */
async function handleEditClick(e) {
    const d = e.currentTarget.dataset;
    resetModal();

    document.getElementById('setting_id').value = d.id || '';
    document.getElementById('schoolclass_id').value = d.schoolclass_id || '';
    document.getElementById('session_id').value = d.session_id || '';
    document.getElementById('term_id').value = d.term_id || '';
    document.getElementById('promoted_label').value = d.promoted_label || 'Promoted';
    document.getElementById('trial_label').value = d.trial_label || 'Promoted on Trial';
    document.getElementById('see_principal_label').value = d.see_principal_label || 'Advised to See Principal';
    document.getElementById('repeat_label').value = d.repeat_label || 'Advice to Repeat';

    const ruleLogic = d.rule_logic || 'grade_count';
    document.getElementById('rule_logic').value = ruleLogic;

    const avgValue = (d.promotion_pass_average !== undefined && d.promotion_pass_average !== null && d.promotion_pass_average !== '')
        ? d.promotion_pass_average : '';
    document.getElementById('promotion_pass_average').value = avgValue;
    document.getElementById('avg_slider').value = (avgValue !== '' ? avgValue : 50);

    document.getElementById('template_id_input').value = d.template_id || '';
    if (d.template_id) document.getElementById('templateSelect').value = d.template_id;

    const isActive = d.is_active === '1';
    document.getElementById('modal_is_active').checked = isActive;
    const badge = document.getElementById('modalActiveBadge');
    if (badge) {
        badge.className = isActive ? 'active-badge is-active' : 'active-badge is-inactive';
        badge.innerHTML = isActive ? '<i class="ri-checkbox-circle-line"></i> Active' : '<i class="ri-close-circle-line"></i> Inactive';
    }

    document.getElementById('rule_logic').dispatchEvent(new Event('change'));

    try {
        promotionRules = JSON.parse(d.promotion_rules || '[]');
    } catch (err) {
        console.error('Failed to parse promotion_rules:', err);
        promotionRules = [];
    }

    openModal();
    await refreshClassInfo();
}

function bindEditButtons() {
    document.querySelectorAll('.edit-setting').forEach(btn => {
        btn.removeEventListener('click', handleEditClick);
        btn.addEventListener('click', handleEditClick);
    });
}

/* ════════════════════════════════════════════════════════════════════════
   DELETE
   ════════════════════════════════════════════════════════════════════════ */
async function handleDeleteClick(e) {
    const btn = e.currentTarget;
    const result = await Swal.fire({
        title: 'Confirm Delete', icon: 'warning',
        html: `Delete rules for <strong>${escH(btn.dataset.name)}</strong>?`,
        showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, Delete',
    });
    if (!result.isConfirmed) return;
    Swal.fire({ title: 'Deleting…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const res = await fetch(`/promotion-settings/${btn.dataset.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message, timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Failed.', 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Network error.', 'error');
    }
}

function bindDeleteButtons() {
    document.querySelectorAll('.delete-setting').forEach(btn => {
        btn.removeEventListener('click', handleDeleteClick);
        btn.addEventListener('click', handleDeleteClick);
    });
}

/* ════════════════════════════════════════════════════════════════════════
   SAVE
   ════════════════════════════════════════════════════════════════════════ */
document.getElementById('saveSettingBtn')?.addEventListener('click', async function () {
    // Prevent double-submission
    if (this.disabled) return;
    this.disabled = true;
    setTimeout(() => { this.disabled = false; }, 3000);

    const classId = document.getElementById('schoolclass_id').value;
    if (!classId) {
        Swal.fire('Validation', 'Please select a class.', 'warning');
        this.disabled = false;
        return;
    }

    const ruleLogic = document.getElementById('rule_logic').value;
    let avgValue = document.getElementById('promotion_pass_average').value;

    if (ruleLogic === 'average_only' || ruleLogic === 'both') {
        if (avgValue === '' || avgValue === null || avgValue === undefined) {
            Swal.fire('Validation', 'Minimum average is required for Average Only or Both evaluation modes.', 'warning');
            this.disabled = false;
            return;
        }
        avgValue = parseFloat(avgValue);
        if (isNaN(avgValue)) {
            Swal.fire('Validation', 'Minimum average must be a valid number.', 'warning');
            this.disabled = false;
            return;
        }
    }

    for (const [i, rule] of promotionRules.entries()) {
        if (!rule.rule_name?.trim()) {
            Swal.fire('Validation', `Rule ${i + 1} needs a name.`, 'warning');
            this.disabled = false;
            return;
        }
        const hasCompSubjGrades = (rule.compulsory_section?.subjects ?? []).some(s => s.min_grade);
        const hasCompConds = (rule.compulsory_section?.count_conditions ?? []).length > 0;
        const hasOtherConds = (rule.other_section?.count_conditions ?? []).length > 0;
        const hasAvg = rule.average_condition?.enabled;
        if (!hasCompSubjGrades && !hasCompConds && !hasOtherConds && !hasAvg) {
            Swal.fire('Validation', `Rule ${i + 1} has no conditions.`, 'warning');
            this.disabled = false;
            return;
        }
    }

    document.getElementById('promotion_rules_input').value = JSON.stringify(promotionRules);
    const fd = new FormData(document.getElementById('settingForm'));
    fd.set('schoolclass_id', classId);
    fd.set('session_id', document.getElementById('session_id').value || '');
    fd.set('term_id', document.getElementById('term_id').value || '');
    fd.set('promoted_label', document.getElementById('promoted_label').value);
    fd.set('trial_label', document.getElementById('trial_label').value);
    fd.set('see_principal_label', document.getElementById('see_principal_label').value);
    fd.set('repeat_label', document.getElementById('repeat_label').value);
    fd.set('rule_logic', ruleLogic);

    if (ruleLogic === 'average_only' || ruleLogic === 'both') {
        fd.set('promotion_pass_average', avgValue.toString());
    } else {
        fd.set('promotion_pass_average', '');
    }

    fd.set('is_active', document.getElementById('modal_is_active').checked ? '1' : '0');
    fd.set('template_id', document.getElementById('template_id_input').value || '');

    const id = document.getElementById('setting_id').value;
    let url = '/promotion-settings';
    if (id) {
        url = `/promotion-settings/${id}`;
        fd.append('_method', 'PUT');  // Laravel method override
    }

    console.log('[SAVE] Submitting:', {
        url: url,
        method: 'POST',  // always POST — Laravel interprets _method override
        hasId: !!id,
        ruleLogic: ruleLogic,
        avgValue: avgValue,
    });

    Swal.fire({ title: 'Saving…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(url, {
            method: 'POST',  // ⚠️ ALWAYS POST — never GET
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: fd,
        });

        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            const txt = await res.text();
            console.error('[SAVE] Non-JSON response:', {
                status: res.status,
                body: txt.substring(0, 500),
            });
            Swal.fire('Error', `Server returned ${res.status}. Check console for details.`, 'error');
            this.disabled = false;
            return;
        }

        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Saved!', text: data.message, timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Failed.', 'error');
            this.disabled = false;
        }
    } catch (err) {
        console.error('[SAVE] Exception:', err);
        Swal.fire('Error', 'An error occurred: ' + err.message, 'error');
        this.disabled = false;
    }
});

/* ════════════════════════════════════════════════════════════════════════
   TOGGLE ACTIVE
   ════════════════════════════════════════════════════════════════════════ */
document.addEventListener('change', async function (e) {
    if (!e.target.classList.contains('toggle-active-switch')) return;
    const toggle = e.target;
    const sid = toggle.dataset.id;
    const isActive = toggle.checked;
    const badge = document.getElementById('ab' + sid);
    const card = toggle.closest('.setting-card');
    try {
        const res = await fetch(`/promotion-settings/${sid}/toggle-active`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ is_active: isActive }),
        });
        const data = await res.json();
        if (data.success) {
            if (badge) {
                badge.className = isActive ? 'active-badge is-active' : 'active-badge is-inactive';
                badge.innerHTML = isActive ? '<i class="ri-checkbox-circle-line"></i> Active' : '<i class="ri-close-circle-line"></i> Inactive';
            }
            card?.classList.toggle('inactive', !isActive);
        } else {
            toggle.checked = !isActive;
            Swal.fire('Error', data.message, 'error');
        }
    } catch (err) {
        console.error(err);
        toggle.checked = !isActive;
        Swal.fire('Error', 'Network error.', 'error');
    }
});

/* ════════════════════════════════════════════════════════════════════════
   INITIAL WIRING
   ════════════════════════════════════════════════════════════════════════ */
document.getElementById('openAddBtn')?.addEventListener('click', openModal);
document.getElementById('openAddBtn2')?.addEventListener('click', openModal);
document.getElementById('settingModal')?.addEventListener('hidden.bs.modal', resetModal);

document.getElementById('modal_is_active')?.addEventListener('change', function () {
    const b = document.getElementById('modalActiveBadge');
    if (b) {
        b.className = this.checked ? 'active-badge is-active' : 'active-badge is-inactive';
        b.innerHTML = this.checked ? '<i class="ri-checkbox-circle-line"></i> Active' : '<i class="ri-close-circle-line"></i> Inactive';
    }
});

document.getElementById('rule_logic')?.addEventListener('change', function () {
    const showAvg = this.value === 'average_only' || this.value === 'both';
    document.getElementById('globalAvgSection').style.display = showAvg ? 'block' : 'none';
    updateGlobalInterpPanel();
});

document.getElementById('avg_slider')?.addEventListener('input', e => {
    document.getElementById('promotion_pass_average').value = e.target.value;
    updateGlobalInterpPanel();
});
document.getElementById('promotion_pass_average')?.addEventListener('input', e => {
    document.getElementById('avg_slider').value = e.target.value;
    updateGlobalInterpPanel();
});

document.getElementById('addRuleBtn')?.addEventListener('click', () => {
    promotionRules.push({
        rule_name: '',
        status_label: 'promoted',
        priority: promotionRules.length + 1,
        grade_grouping: 'grouped',
        compulsory_section: {
            subjects: compulsorySubjects.map(cs => ({
                subject_id:        cs.subject_id,
                subject_name:      cs.subject_name,
                subject_code:      cs.subject_code,
                default_min_grade: cs.default_min_grade ?? '',
                min_grade:         cs.default_min_grade ?? '',
                override:          false,
            })),
            count_conditions: [],
        },
        other_section: { count_conditions: [] },
        average_condition: { enabled: false, min_average: classPassAvg ?? 50, logic: 'AND' },
    });
    rerenderRules();
});

document.getElementById('templateSelect')?.addEventListener('change', function () {
    document.getElementById('loadTemplateBtn').disabled = !this.value;
    document.getElementById('template_id_input').value = this.value;
});

document.getElementById('loadTemplateBtn')?.addEventListener('click', async function () {
    const tplId = document.getElementById('templateSelect').value;
    const classId = document.getElementById('schoolclass_id').value;
    if (!tplId) { Swal.fire('', 'Select a template first.', 'info'); return; }
    if (!classId) { Swal.fire('', 'Select a class first.', 'info'); return; }
    const termId = document.getElementById('term_id').value;
    const sessionId = document.getElementById('session_id').value;
    const status = document.getElementById('templateStatus');
    status.textContent = 'Loading…';
    try {
        let url = `/promotion-templates/${tplId}/load-for-class?classid=${encodeURIComponent(classId)}`;
        if (termId)    url += `&termid=${encodeURIComponent(termId)}`;
        if (sessionId) url += `&sessionid=${encodeURIComponent(sessionId)}`;
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            status.textContent = '✗ Server returned non-JSON.';
            status.style.color = '#dc2626';
            return;
        }
        const data = await res.json();
        if (data.success) {
            promotionRules = data.merged_rules ?? [];
            status.textContent = `✓ ${data.template.name} loaded (${promotionRules.length} rules)`;
            status.style.color = '#16a34a';
            rerenderRules();
        } else {
            status.textContent = '✗ ' + (data.message || 'Failed');
            status.style.color = '#dc2626';
        }
    } catch (err) {
        console.error(err);
        status.textContent = '✗ Error: ' + err.message;
        status.style.color = '#dc2626';
    }
});

/* ════════════════════════════════════════════════════════════════════════
   CHANGE LISTENERS — GUARDED
   Only fire when a class is selected AND the modal is currently open.
   This prevents the "GET /promotion-settings/1" error caused by stray
   change events during page load.
   ════════════════════════════════════════════════════════════════════════ */
['schoolclass_id', 'session_id', 'term_id'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', function () {
        const classId = document.getElementById('schoolclass_id')?.value;
        const modalEl = document.getElementById('settingModal');
        const modalOpen = modalEl && modalEl.classList.contains('show');
        if (!classId || !modalOpen) return;
        refreshClassInfo();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    setupEventDelegation();
    bindEditButtons();
    bindDeleteButtons();
    rerenderRules();
});
</script>