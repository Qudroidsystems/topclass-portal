{{-- resources/views/promotions/index.blade.php --}}
@extends('layouts.master')

@section('content')
<style>
:root {
    --pay-primary: #1e3a5f;
    --pay-accent:  #2563eb;
    --pay-success: #16a34a;
    --pay-warning: #d97706;
    --pay-danger:  #dc2626;
    --pay-muted:   #6b7280;
    --pay-border:  #e2e8f0;
    --pay-bg:      #f8fafc;
    --pay-radius:  12px;
    --pay-shadow:  0 2px 8px rgba(0,0,0,.08);

    --color-background-success:   #dcfce7;
    --color-background-danger:    #fee2e2;
    --color-background-warning:   #fef9c3;
    --color-background-info:      #dbeafe;
    --color-background-secondary: #f1f5f9;

    --color-text-success:   #15803d;
    --color-text-danger:    #b91c1c;
    --color-text-warning:   #92400e;
    --color-text-info:      #1e40af;
    --color-text-secondary: #475569;
    --color-text-primary:   #1e293b;

    --color-border-success:   #86efac;
    --color-border-danger:    #fecaca;
    --color-border-warning:   #fed7aa;
    --color-border-info:      #bfdbfe;
    --color-border-tertiary:  #cbd5e1;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes modalZoomIn {
    from { opacity: 0; transform: scale(0.95) translateY(-10px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes toastSlideIn {
    from { opacity: 0; transform: translateX(100px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes toastSlideOut {
    from { opacity: 1; transform: translateX(0); }
    to   { opacity: 0; transform: translateX(100px); }
}
@keyframes skeletonLoading {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
@keyframes btnPulse {
    0%   { box-shadow: 0 0 0 0   rgba(37,99,235,0.4); }
    70%  { box-shadow: 0 0 0 8px rgba(37,99,235,0);   }
    100% { box-shadow: 0 0 0 0   rgba(37,99,235,0);   }
}
@keyframes statFlash {
    0%   { transform: scale(1);    color: inherit; }
    40%  { transform: scale(1.18); color: #2563eb; }
    100% { transform: scale(1);    color: inherit; }
}
@keyframes badgePop {
    0%   { transform: scale(1);    }
    40%  { transform: scale(1.22); }
    70%  { transform: scale(0.94); }
    100% { transform: scale(1);    }
}
@keyframes bounce {
    0%, 100% { transform: translateY(0);     }
    50%       { transform: translateY(-10px); }
}

.pay-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #4f46e5 100%);
    border-radius: var(--pay-radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    animation: slideIn 0.5s ease-out;
}
.pay-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.pay-hero h1 { font-size: 22px; font-weight: 700; color: #fff; margin: 0 0 6px; position: relative; }
.pay-hero p  { font-size: 13px; color: rgba(255,255,255,.75); margin: 0; position: relative; }

.stat-card {
    background: #fff;
    border: 1px solid var(--pay-border);
    border-radius: var(--pay-radius);
    padding: 18px 20px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
.stat-card .stat-value { font-size: 28px; font-weight: 700; color: var(--pay-primary); }
.stat-card .stat-label { font-size: 12px; color: var(--pay-muted); margin-top: 4px; }
.stat-card .stat-icon  { font-size: 32px; opacity: .12; float: right; margin-top: -8px; }
.stat-flash { animation: statFlash .45s cubic-bezier(.34,1.4,.64,1); }

.info-banner {
    background: #eff6ff; border: 1px solid #bfdbfe;
    border-radius: 10px; padding: 12px 16px;
    margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    animation: fadeInUp 0.4s ease-out;
}
.info-banner i { font-size: 20px; color: #2563eb; }
.info-banner .text { font-size: 13px; color: #1e40af; }
.info-banner .text strong { display: block; margin-bottom: 4px; }
.info-banner .text a { color: #1e40af; font-weight: 600; text-decoration: underline; }

.promotion-badge-promoted,
.promotion-badge-trial,
.promotion-badge-see_principal,
.promotion-badge-repeated,
.promotion-badge-pending {
    padding: 4px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 4px;
}
.promotion-badge-promoted    { background: #10b981; color: white; }
.promotion-badge-trial       { background: #f59e0b; color: white; }
.promotion-badge-see_principal { background: #3b82f6; color: white; }
.promotion-badge-repeated    { background: #ef4444; color: white; }
.promotion-badge-pending     { background: #6b7280; color: white; }
.badge-pop { animation: badgePop .4s cubic-bezier(.34,1.4,.64,1); }

.bulk-action-bar {
    display: none; align-items: center; gap: 12px;
    background: #fff7ed; border: 1px solid #fed7aa;
    border-radius: 10px; padding: 10px 16px; margin-bottom: 16px;
}
.bulk-action-bar.visible { display: flex; }
.bulk-action-bar .bulk-count { font-size: 13px; font-weight: 600; color: #92400e; }
.select-all-checkbox { width: 16px; height: 16px; cursor: pointer; }

.modal-content {
    border-radius: 16px; overflow: hidden;
    animation: modalZoomIn 0.3s cubic-bezier(0.34, 1.3, 0.64, 1);
}
.modal-header {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    padding: 20px 28px; border-bottom: none;
}
.modal-header .modal-title { color: #fff; font-weight: 700; }
.modal-header .btn-close { filter: invert(1); background: transparent; opacity: .8; }

.form-section { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 20px; }
.form-section-title {
    font-size: 14px; font-weight: 700; color: var(--pay-primary);
    margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--pay-border);
}

.form-check-card .form-check-input { display: none; }
.promotion-card, .trial-card, .principal-card, .repeat-card {
    transition: all 0.3s ease; background-color: #fff;
}
.promotion-card:hover { border-color: #198754 !important; box-shadow: 0 0 0 0.2rem rgba(25,135,84,.1); }
.trial-card:hover     { border-color: #ffc107 !important; box-shadow: 0 0 0 0.2rem rgba(255,193,7,.1); }
.principal-card:hover { border-color: #0dcaf0 !important; box-shadow: 0 0 0 0.2rem rgba(13,202,240,.1); }
.repeat-card:hover    { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220,53,69,.1); }

#promotionCheckbox:checked ~ label .promotion-card    { border-color: #198754 !important; background-color: #d1e7dd !important; }
#trialCheckbox:checked ~ label .trial-card            { border-color: #ffc107 !important; background-color: #fff3cd !important; }
#seePrincipalCheckbox:checked ~ label .principal-card { border-color: #0dcaf0 !important; background-color: #cff4fc !important; }
#repeatCheckbox:checked ~ label .repeat-card          { border-color: #dc3545 !important; background-color: #f8d7da !important; }

#studentTableBody tr[data-student-id] {
    opacity: 0; transform: translateY(14px);
    transition: opacity .38s cubic-bezier(.25,.46,.45,.94),
                transform .38s cubic-bezier(.25,.46,.45,.94),
                background .18s ease;
    will-change: opacity, transform;
}
#studentTableBody tr[data-student-id].row-visible { opacity: 1; transform: translateY(0); }
#studentTableBody tr[data-student-id]:hover {
    background: #f0f6ff !important;
    box-shadow: inset 3px 0 0 #2563eb;
    transform: translateY(-1px) !important;
    transition: background .14s ease, box-shadow .18s ease, transform .18s cubic-bezier(.34,1.4,.64,1);
    position: relative; z-index: 1;
}
#studentTableBody tr[data-student-id].selected             { background: #e0f2fe !important; }
#studentTableBody tr[data-student-id].selected:hover       { background: #d9ebf7 !important; }
#studentTableBody tr[data-student-id] .student-row-avatar  { transition: transform .18s ease, box-shadow .18s ease; }
#studentTableBody tr[data-student-id]:hover .student-row-avatar { transform: scale(1.12); box-shadow: 0 2px 8px rgba(0,0,0,.15); }
#studentTableBody tr[data-student-id]:hover .badge,
#studentTableBody tr[data-student-id]:hover [class*="promotion-badge-"] { transition: transform .18s cubic-bezier(.34,1.4,.64,1); transform: scale(1.06); }
#studentTableBody tr[data-student-id] .row-checkbox { opacity: .35; transform: scale(.85); transition: opacity .18s ease, transform .18s cubic-bezier(.34,1.4,.64,1); }
#studentTableBody tr[data-student-id]:hover .row-checkbox,
#studentTableBody tr[data-student-id] .row-checkbox:checked { opacity: 1; transform: scale(1); }

.score-bar-wrap { background: #e2e8f0; border-radius: 4px; height: 6px; width: 60px; display: inline-block; vertical-align: middle; margin-left: 6px; }
.score-bar-fill { height: 100%; border-radius: 4px; }

.compulsory-table { width: 100%; border-collapse: collapse; }
.compulsory-table th { background: var(--pay-primary); color: #fff; padding: 12px 16px; font-weight: 600; font-size: 13px; white-space: nowrap; text-align: left; }
.compulsory-table td { padding: 11px 16px; vertical-align: middle; border-bottom: 1px solid var(--pay-border); font-size: 13px; }

.subj-table { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
.subj-table thead th {
    background: var(--pay-primary); color: #fff;
    padding: 10px 12px; font-weight: 600; font-size: 11.5px; white-space: nowrap;
    position: sticky; top: 0; z-index: 2;
    border-right: 1px solid rgba(255,255,255,.08);
}
.subj-table thead th:last-child { border-right: none; }
.subj-table tbody td { padding: 9px 12px; vertical-align: middle; border-bottom: 1px solid var(--pay-border); }
.subj-table tbody tr:hover td { background: #f8fafc; }
.subj-table .section-row td {
    background: #f1f5f9; padding: 6px 12px;
    font-size: 10.5px; font-weight: 700; color: #475569;
    letter-spacing: .05em; text-transform: uppercase;
    border-bottom: 1px solid var(--pay-border);
    border-top: 2px solid #e2e8f0;
}

.gc-a  { color: #15803d; }
.gc-b  { color: #1d4ed8; }
.gc-c  { color: #0369a1; }
.gc-d  { color: #d97706; }
.gc-f  { color: #b91c1c; }
.gc-na { color: #9ca3af; }

.subj-table .row-pass    { border-left: 3px solid #10b981; }
.subj-table .row-fail    { border-left: 3px solid #ef4444; }
.subj-table .row-notsat  { border-left: 3px solid #f59e0b; }
.subj-table .row-credit  { border-left: 3px solid #3b82f6; }
.subj-table .row-passonly{ border-left: 3px solid #d97706; }
.subj-table .row-optfail { border-left: 3px solid #ef4444; }
.subj-table .row-optns   { border-left: 3px solid #9ca3af; }

.mini-bar { display:inline-flex; align-items:center; gap:7px; }
.mini-bar-track { height:6px; background:#e2e8f0; border-radius:3px; display:inline-block; vertical-align:middle; overflow:hidden; flex-shrink:0; }
.mini-bar-fill  { height:100%; border-radius:3px; display:block; }

.eval-tag {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 500; padding: 2px 8px;
    border-radius: 5px; white-space: nowrap;
    border: 0.5px solid transparent;
}
.eval-green  { background: #dcfce7; color: #15803d; border-color: #86efac; }
.eval-red    { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
.eval-amber  { background: #fef9c3; color: #92400e; border-color: #fde68a; }
.eval-blue   { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.eval-gray   { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
.eval-sub { display: block; font-size: 10.5px; color: #64748b; margin-top: 2px; line-height: 1.35; }

.subj-stat-row {
    display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
    padding: 10px 14px; background: #f8fafc;
    border: 1px solid var(--pay-border); border-radius: 8px; margin-bottom: 12px;
}
.subj-stat-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11.5px; font-weight: 500; padding: 3px 10px;
    border-radius: 20px;
}
.chip-total   { background: #ede9fe; color: #6d28d9; }
.chip-pass    { background: #dcfce7; color: #15803d; }
.chip-fail    { background: #fee2e2; color: #b91c1c; }
.chip-notsat  { background: #fef9c3; color: #92400e; }
.chip-credit  { background: #dbeafe; color: #1e40af; }

.credit-tally {
    display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;
}
.credit-tally-card {
    flex: 1; min-width: 120px;
    background: #fff; border: 1px solid var(--pay-border);
    border-radius: 8px; padding: 10px 14px;
    display: flex; align-items: center; gap: 10px;
}
.credit-tally-card .num { font-size: 22px; font-weight: 700; line-height: 1; }
.credit-tally-card .lbl { font-size: 11px; color: #64748b; line-height: 1.4; }
.credit-tally-card .lbl small { display: block; opacity: .75; }

.rule-match-banner {
    padding: 10px 14px; border-radius: 8px;
    font-size: 12.5px; margin-bottom: 12px;
    display: flex; flex-direction: column; gap: 5px;
    border: 0.5px solid transparent;
}
.rule-match-banner.matched   { background: #dcfce7; color: #15803d; border-color: #86efac; }
.rule-match-banner.unmatched { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
.rule-match-banner.info      { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.rule-match-banner .top      { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; font-weight: 600; }
.rule-match-banner .sub      { font-size: 11.5px; opacity: .85; }

.empty-state { text-align: center; padding: 52px 24px; color: var(--pay-muted); }
.empty-state i { font-size: 3rem; opacity: .25; display: block; margin-bottom: 14px; }

.search-box { position: relative; }
.search-box .form-control { border: 1.5px solid var(--pay-border); border-radius: 8px; padding: 9px 14px; padding-right: 36px; font-size: 13px; width: 100%; }
.search-box .form-control:focus { border-color: var(--pay-accent); outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.search-box .search-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--pay-muted); pointer-events: none; }

.student-avatar-lg { width: 120px; height: 120px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,.15); background: #f8f9fa; }
.status-badge-lg   { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 30px; font-size: 14px; font-weight: 600; }

.rule-badge { background: #1e3a5f; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }

.btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: all .15s; border: none; cursor: pointer; }
.btn-subtle-primary { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.btn-subtle-primary:hover { background: #dbeafe; color: #1d4ed8; transform: translateY(-1px); }
.btn-subtle-danger  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.btn-subtle-danger:hover  { background: #fee2e2; color: #b91c1c; transform: translateY(-1px); }

.toast-notification { position: fixed; bottom: 20px; right: 20px; z-index: 10000; animation: toastSlideIn 0.3s ease-out; }
.toast-notification.closing { animation: toastSlideOut 0.3s ease-out forwards; }

.loading-overlay  { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(3px); }
.loading-spinner  { background: white; padding: 20px 30px; border-radius: 12px; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }

.skeleton-row td { position: relative; overflow: hidden; }
.skeleton-row td::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); animation: skeletonLoading 1.5s infinite; }

.btn-pulse { animation: btnPulse 2s infinite; }
.animate-bounce { animation: bounce 2s infinite; }
.avatar-sm { height: 3rem; width: 3rem; }
.avatar-title { align-items: center; display: flex; height: 100%; justify-content: center; width: 100%; }
.bg-success-subtle { background-color: rgba(25,135,84,.1)  !important; }
.bg-warning-subtle { background-color: rgba(255,193,7,.1)  !important; }
.bg-info-subtle    { background-color: rgba(13,202,240,.1) !important; }
.bg-danger-subtle  { background-color: rgba(220,53,69,.1)  !important; }
.table-hover tbody tr:hover { background-color: rgba(0,0,0,.02); }

.recommendation-card { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
.recommendation-card.promoted      { border-left: 4px solid #10b981; }
.recommendation-card.trial         { border-left: 4px solid #f59e0b; }
.recommendation-card.see_principal { border-left: 4px solid #3b82f6; }
.recommendation-card.repeated      { border-left: 4px solid #ef4444; }
.recommendation-card .label { font-size: 12px; color: var(--pay-muted); margin-bottom: 4px; }
.recommendation-card .value { font-size: 16px; font-weight: 700; }

.filter-card {
    background: #fff;
    border: 1px solid var(--pay-border);
    border-radius: var(--pay-radius);
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: var(--pay-shadow);
}
.filter-card .form-label { font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; }
.filter-card .form-select, .filter-card .form-control {
    border: 1.5px solid var(--pay-border); border-radius: 8px; font-size: 13px; padding: 9px 12px;
}
.filter-card .form-select:focus, .filter-card .form-control:focus {
    border-color: var(--pay-accent); box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}

.table-card {
    background: #fff;
    border: 1px solid var(--pay-border);
    border-radius: var(--pay-radius);
    box-shadow: var(--pay-shadow);
    overflow: hidden;
}
.table-card .table thead th {
    background: var(--pay-primary);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 12px 14px;
    white-space: nowrap;
    border: none;
}
.table-card .table tbody td {
    padding: 11px 14px;
    font-size: 13px;
    vertical-align: middle;
    border-bottom: 1px solid var(--pay-border);
}

@media (prefers-reduced-motion: reduce) {
    #studentTableBody tr[data-student-id],
    #studentTableBody tr[data-student-id]:hover { transition: background .15s ease !important; transform: none !important; opacity: 1 !important; }
    .stat-flash, .badge-pop { animation: none !important; }
    .toast-notification, .modal-content { animation: none !important; }
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            {{-- Hero --}}
            <div class="pay-hero">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h1><i class="ri-user-star-line me-2"></i>Student Promotion Management</h1>
                        <p>Manage student promotion, repetition, and class assignments based on academic performance.</p>
                    </div>
                    <div>
                        <a href="{{ route('promotion-settings.index') }}" class="btn btn-light">
                            <i class="ri-settings-4-line me-1"></i>Promotion Settings
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="ri-user-line"></i></div>
                        <div class="stat-value" id="totalStudents">0</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="ri-arrow-up-circle-line"></i></div>
                        <div class="stat-value text-success" id="promotedCount">0</div>
                        <div class="stat-label">Recommended Promoted</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="ri-time-line"></i></div>
                        <div class="stat-value text-warning" id="trialCount">0</div>
                        <div class="stat-label">On Trial</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="ri-repeat-line"></i></div>
                        <div class="stat-value text-danger" id="repeatCount">0</div>
                        <div class="stat-label">Advice to Repeat</div>
                    </div>
                </div>
            </div>

            {{-- Info banner --}}
            <div class="info-banner">
                <i class="ri-information-line"></i>
                <div class="text">
                    <strong>Promotion evaluation uses the same rules as Broadsheet.</strong>
                    Choose <em>Average Basis</em> (Term Total or Cumulative) so overall average matches the broadsheet Grade Basis. Configure rules under
                    <a href="{{ route('promotion-settings.index') }}">Promotion Settings</a>.
                </div>
            </div>

            {{-- Filters --}}
            <div class="filter-card">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Class / Arm <span class="text-danger">*</span></label>
                        <select class="form-select" id="idclass" name="schoolclassid">
                            <option value="">— Select class —</option>
                            @foreach($schoolclasses ?? [] as $cls)
                                <option value="{{ $cls->id }}"
                                    {{ (string)($selectedClassId ?? '') === (string)$cls->id ? 'selected' : '' }}>
                                    {{ $cls->schoolclass }}{{ !empty($cls->arm) ? ' ' . $cls->arm : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <select class="form-select" id="idsession" name="sessionid">
                            <option value="">— Select session —</option>
                            @foreach($schoolsessions ?? [] as $session)
                                <option value="{{ $session->id }}"
                                    {{ (string)($selectedSessionId ?? '') === (string)$session->id ? 'selected' : '' }}>
                                    {{ $session->session }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select class="form-select" id="idterm" name="termid">
                            <option value="">— Select term —</option>
                            @foreach($schoolterms ?? [] as $term)
                                <option value="{{ $term->id }}"
                                    {{ (string)($selectedTermId ?? '') === (string)$term->id ? 'selected' : '' }}>
                                    {{ $term->term }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Average Basis</label>
                        <select class="form-select" id="average_basis" name="average_basis">
                            <option value="total" {{ ($selectedAverageBasis ?? 'total') === 'total' ? 'selected' : '' }}>Term Total</option>
                            <option value="cum" {{ ($selectedAverageBasis ?? '') === 'cum' ? 'selected' : '' }}>Cumulative</option>
                        </select>
                        <small class="text-muted" style="font-size:11px;">Matches broadsheet Grade Basis</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <div class="search-box">
                            <input type="text" class="form-control" id="searchInput" placeholder="Name or admission no…">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bulk action bar --}}
            <div class="bulk-action-bar" id="bulkActionBar">
                <span class="bulk-count"><span id="bulkSelectedCount">0</span> selected</span>
                <button type="button" class="btn btn-sm btn-primary" id="bulkPromoteActionBtn" onclick="openBulkPromoteModal()">
                    <i class="ri-user-shared-line me-1"></i>Bulk Promote
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">Clear</button>
            </div>

            {{-- Student table --}}
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="studentTable">
                        <thead>
                            <tr>
                                <th style="width:40px;">
                                    <input type="checkbox" class="select-all-checkbox" id="selectAll" title="Select all">
                                </th>
                                <th>Adm. No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Arm</th>
                                <th>Session</th>
                                <th>Overall Avg</th>
                                <th>System Recommendation</th>
                                <th>Promotion Status</th>
                                <th style="width:90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            @include('promotions.partials.student_rows', ['allstudents' => $allstudents ?? collect()])
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     PROMOTION MODAL
═══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="promotionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="modalStudentName">Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4 mb-3">
                    <div class="col-md-3 text-center">
                        <img id="modalStudentImage" src="{{ asset('storage/student_avatars/unnamed.jpg') }}"
                             class="student-avatar-lg rounded-circle mb-2" alt="Student">
                        <div id="modalStudentGender" class="text-muted small"></div>
                    </div>
                    <div class="col-md-9">
                        <div class="row g-2">
                            <div class="col-sm-4">
                                <div class="text-muted small">Current Class</div>
                                <div class="fw-semibold" id="modalCurrentClass">—</div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted small">Arm</div>
                                <div class="fw-semibold" id="modalCurrentArm">—</div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted small">Session</div>
                                <div class="fw-semibold" id="modalCurrentSession">—</div>
                            </div>
                            <div class="col-sm-4 mt-2">
                                <div class="text-muted small">Overall Average</div>
                                <div id="modalOverallAverage"><span class="text-muted">—</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="recommendationCard" style="display:none;">
                    <div id="recommendationContent"></div>
                </div>

                <div id="allSubjectsCard" class="form-section" style="display:none;">
                    <div class="form-section-title"><i class="ri-book-open-line me-1"></i>All Subjects</div>
                    <div id="allSubjectsContent"></div>
                </div>

                <div id="compulsoryCard" class="form-section" style="display:none;">
                    <div class="form-section-title"><i class="ri-bookmark-line me-1"></i>Compulsory Subjects</div>
                    <div id="compulsoryContent"></div>
                </div>

                <form id="promotionForm">
                    <div class="form-section">
                        <div class="form-section-title">New Placement</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">New Class</label>
                                <select class="form-select" id="newClassSelect" name="new_schoolclassid">
                                    <option value="">— Select —</option>
                                    @foreach($schoolclasses ?? [] as $cls)
                                        <option value="{{ $cls->id }}">
                                            {{ $cls->schoolclass }}{{ !empty($cls->arm) ? ' ' . $cls->arm : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">New Session</label>
                                <select class="form-select" id="newSessionSelect" name="new_sessionid">
                                    <option value="">— Select —</option>
                                    @foreach($schoolsessions ?? [] as $session)
                                        <option value="{{ $session->id }}">{{ $session->session }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">New Term</label>
                                <select class="form-select" id="newTermSelect" name="new_termid">
                                    <option value="">— Select —</option>
                                    @foreach($schoolterms ?? [] as $term)
                                        <option value="{{ $term->id }}">{{ $term->term }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Promotion Decision <span class="text-danger">*</span></div>
                        <div class="row g-3">
                            <div class="col-md-3 form-check-card">
                                <input type="checkbox" id="promotionCheckbox" class="form-check-input">
                                <label for="promotionCheckbox" class="w-100">
                                    <div class="promotion-card border rounded-3 p-3 text-center h-100">
                                        <i class="ri-arrow-up-circle-line text-success fs-3"></i>
                                        <div class="fw-semibold mt-1">Promote</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-3 form-check-card">
                                <input type="checkbox" id="trialCheckbox" class="form-check-input">
                                <label for="trialCheckbox" class="w-100">
                                    <div class="trial-card border rounded-3 p-3 text-center h-100">
                                        <i class="ri-time-line text-warning fs-3"></i>
                                        <div class="fw-semibold mt-1">On Trial</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-3 form-check-card">
                                <input type="checkbox" id="seePrincipalCheckbox" class="form-check-input">
                                <label for="seePrincipalCheckbox" class="w-100">
                                    <div class="principal-card border rounded-3 p-3 text-center h-100">
                                        <i class="ri-eye-line text-info fs-3"></i>
                                        <div class="fw-semibold mt-1">See Principal</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-3 form-check-card">
                                <input type="checkbox" id="repeatCheckbox" class="form-check-input">
                                <label for="repeatCheckbox" class="w-100">
                                    <div class="repeat-card border rounded-3 p-3 text-center h-100">
                                        <i class="ri-repeat-line text-danger fs-3"></i>
                                        <div class="fw-semibold mt-1">Repeat</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitPromotion()">
                    <i class="ri-save-line me-1"></i>Update Promotion
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Bulk promote modal (minimal) --}}
<div class="modal fade" id="bulkPromoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title text-white">Bulk Promote Selected</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Apply the same new class / session / term and decision to all selected students.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Class</label>
                    <select class="form-select" id="bulkNewClass">
                        <option value="">— Select —</option>
                        @foreach($schoolclasses ?? [] as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->schoolclass }}{{ !empty($cls->arm) ? ' ' . $cls->arm : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Session</label>
                    <select class="form-select" id="bulkNewSession">
                        <option value="">— Select —</option>
                        @foreach($schoolsessions ?? [] as $session)
                            <option value="{{ $session->id }}">{{ $session->session }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Term</label>
                    <select class="form-select" id="bulkNewTerm">
                        <option value="">— Select —</option>
                        @foreach($schoolterms ?? [] as $term)
                            <option value="{{ $term->id }}">{{ $term->term }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Decision</label>
                    <select class="form-select" id="bulkDecision">
                        <option value="promoted">Promote</option>
                        <option value="trial">On Trial</option>
                        <option value="see_principal">See Principal</option>
                        <option value="repeated">Repeat</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitBulkPromote()">Confirm Bulk Update</button>
            </div>
        </div>
    </div>
</div>

<div id="loadingOverlay" class="loading-overlay" style="display:none;">
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status"></div>
        <span id="loadingText">Loading…</span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
let currentStudentId = null;
let currentSchoolclassId = null;
let currentSessionId = null;
let currentTermId = null;
let currentStudentData = null;

function showLoading(msg) {
    document.getElementById('loadingText').textContent = msg || 'Loading…';
    document.getElementById('loadingOverlay').style.display = 'flex';
}
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}
function showToast(message, type) {
    const colors = { success: '#16a34a', danger: '#dc2626', warning: '#d97706', info: '#2563eb' };
    const el = document.createElement('div');
    el.className = 'toast-notification';
    el.style.cssText = `background:#fff;border-left:4px solid ${colors[type]||colors.info};padding:12px 16px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);max-width:360px;`;
    el.innerHTML = `<div style="font-size:13px;color:#1e293b;">${escapeHtml(message)}</div>`;
    document.body.appendChild(el);
    setTimeout(() => { el.classList.add('closing'); setTimeout(() => el.remove(), 300); }, 3200);
}
function escapeHtml(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatRuleDescription(d) { return d || ''; }

function normalizeImagePath(picture, gender) {
    if (picture && picture !== 'unnamed.jpg') {
        return `/storage/student_avatars/${picture.replace(/^.*[\\/]/, '')}`;
    }
    return '/storage/student_avatars/unnamed.jpg';
}
function setStudentImage(imgEl, src, gender) {
    if (!imgEl) return;
    imgEl.src = src;
    imgEl.onerror = function () { this.src = '/storage/student_avatars/unnamed.jpg'; };
}

function getAverageBasis() {
    return document.getElementById('average_basis')?.value || 'total';
}

/* ── Filter / reload table ── */
async function filterData() {
    const schoolclassid = document.getElementById('idclass').value;
    const sessionid     = document.getElementById('idsession').value;
    const termid        = document.getElementById('idterm').value;
    const search        = document.getElementById('searchInput').value || '';
    const average_basis = getAverageBasis();

    if (!schoolclassid || !sessionid || !termid) {
        return;
    }

    showLoading('Loading students…');
    try {
        const response = await axios.get('{{ route("promotions.index") }}', {
            params: {
                schoolclassid,
                sessionid,
                termid,
                search,
                average_basis,
                ajax: 1,
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html, application/json',
            },
        });

        const body = document.getElementById('studentTableBody');
        if (typeof response.data === 'string') {
            body.innerHTML = response.data;
        } else if (response.data?.html) {
            body.innerHTML = response.data.html;
        } else if (response.data?.success && response.data?.rows_html) {
            body.innerHTML = response.data.rows_html;
        }

        updateStats();
        triggerRowEntrance();
        popPromotionBadges();
        setupRowSelection();
        clearSelection();
    } catch (err) {
        console.error(err);
        showToast(err.response?.data?.message || 'Failed to load students', 'danger');
    } finally {
        hideLoading();
    }
}

function updateStats() {
    const rows = document.querySelectorAll('#studentTableBody tr[data-student-id]');
    let total = 0, promoted = 0, trial = 0, repeated = 0;
    rows.forEach(tr => {
        total++;
        const st = (tr.querySelector('[data-rec-status]')?.getAttribute('data-rec-status') || '').toLowerCase();
        if (st === 'promoted') promoted++;
        else if (st === 'trial') trial++;
        else if (st === 'repeated' || st === 'repeat') repeated++;
    });
    const set = (id, val) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = val;
        el.classList.remove('stat-flash');
        void el.offsetWidth;
        el.classList.add('stat-flash');
    };
    set('totalStudents', total);
    set('promotedCount', promoted);
    set('trialCount', trial);
    set('repeatCount', repeated);
}

function triggerRowEntrance() {
    document.querySelectorAll('#studentTableBody tr[data-student-id]').forEach((tr, i) => {
        setTimeout(() => tr.classList.add('row-visible'), 30 * i);
    });
}
function popPromotionBadges() {
    document.querySelectorAll('#studentTableBody [class*="promotion-badge-"]').forEach((b, i) => {
        setTimeout(() => b.classList.add('badge-pop'), 40 * i);
    });
}

function setupRowSelection() {
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.onchange = function () {
            this.closest('tr')?.classList.toggle('selected', this.checked);
            updateBulkBar();
        };
    });
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.onchange = function () {
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = selectAll.checked;
                cb.closest('tr')?.classList.toggle('selected', selectAll.checked);
            });
            updateBulkBar();
        };
    }
}
function updateBulkBar() {
    const n = document.querySelectorAll('.row-checkbox:checked').length;
    const bar = document.getElementById('bulkActionBar');
    const countEl = document.getElementById('bulkSelectedCount');
    if (countEl) countEl.textContent = n;
    if (bar) bar.classList.toggle('visible', n > 0);
}
function clearSelection() {
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = false;
        cb.closest('tr')?.classList.remove('selected');
    });
    const sa = document.getElementById('selectAll');
    if (sa) sa.checked = false;
    updateBulkBar();
}
function setupCheckboxHandlers() {
    const ids = ['promotionCheckbox','trialCheckbox','seePrincipalCheckbox','repeatCheckbox'];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', function () {
            if (this.checked) {
                ids.forEach(other => {
                    if (other !== id) {
                        const o = document.getElementById(other);
                        if (o) o.checked = false;
                    }
                });
            }
        });
    });
}

/* ── Subjects table builders (modal) ── */
function gradeColorClass(g) {
    if (!g) return 'gc-na';
    const u = String(g).toUpperCase();
    if (['A1','A'].includes(u)) return 'gc-a';
    if (['B2','B3','B'].includes(u)) return 'gc-b';
    if (['C4','C5','C6','C'].includes(u)) return 'gc-c';
    if (['D7','D'].includes(u)) return 'gc-d';
    return 'gc-f';
}
function scoreBar(total, grade) {
    const t = parseFloat(total) || 0;
    const pct = Math.max(0, Math.min(100, t));
    const color = pct >= 50 ? '#16a34a' : (pct >= 40 ? '#d97706' : '#dc2626');
    return `<span class="mini-bar"><span class="mini-bar-track" style="width:48px;"><span class="mini-bar-fill" style="width:${pct}%;background:${color};"></span></span><span style="font-size:11px;font-weight:600;">${t ? t.toFixed(1) : '—'}</span></span>`;
}
function buildEval(s) {
    const st = s.pass_status || s.eval_status || '';
    if (st === 'pass' || st === 'credit') return `<span class="eval-tag eval-green">✓ Pass</span>`;
    if (st === 'fail') return `<span class="eval-tag eval-red">✗ Fail</span>`;
    if (st === 'not_sat') return `<span class="eval-tag eval-amber">○ Not Sat</span>`;
    return `<span class="eval-tag eval-gray">—</span>`;
}
function rowCls(s) {
    const st = s.pass_status || '';
    if (st === 'pass') return 'row-pass';
    if (st === 'fail') return 'row-fail';
    if (st === 'not_sat') return 'row-notsat';
    if (st === 'credit') return 'row-credit';
    return '';
}
function buildSubjectsTable(allSubjects, result) {
    const compList = allSubjects.filter(s => s.is_compulsory);
    const optList  = allSubjects.filter(s => !s.is_compulsory);
    const optCred  = optList.filter(s => s.pass_status === 'credit' || s.pass_status === 'pass').length;

    let html = `<div class="table-responsive"><table class="subj-table"><thead><tr>
        <th style="width:36px;">#</th>
        <th style="width:28%;">Subject</th>
        <th style="width:10%;">Code</th>
        <th style="width:14%;">Score</th>
        <th style="width:10%;">Grade</th>
        <th style="width:12%;">Min</th>
        <th>Evaluation</th>
    </tr></thead><tbody>`;

    if (compList.length) {
        html += `<tr class="section-row"><td colspan="7">
            <i class="ri-bookmark-fill" style="color:#7c3aed;margin-right:5px;"></i>
            Compulsory subjects
        </td></tr>`;
        compList.forEach((s, i) => {
            const grade = s.grade || '—';
            const min = s.min_grade || s.required_min_grade || '—';
            html += `<tr class="${rowCls(s)}">
                <td style="text-align:center;color:#94a3b8;font-size:11px;">${i + 1}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                        <strong style="font-size:13px;">${escapeHtml(s.subject_name)}</strong>
                        <span style="background:#fef3c7;color:#92400e;border:0.5px solid #fde68a;font-size:9px;font-weight:700;padding:1px 6px;border-radius:10px;">COMPULSORY</span>
                    </div>
                </td>
                <td style="text-align:center;font-family:monospace;font-size:11px;color:#64748b;">${escapeHtml(s.subject_code || '—')}</td>
                <td style="text-align:center;">${scoreBar(s.total, grade)}</td>
                <td style="text-align:center;"><strong class="${gradeColorClass(grade)}" style="font-size:17px;">${grade}</strong></td>
                <td style="text-align:center;">
                    ${min && min !== '—'
                        ? `<span style="background:#dbeafe;color:#1e40af;border:0.5px solid #bfdbfe;font-size:11px;padding:2px 9px;border-radius:10px;font-weight:600;">≥ ${min}</span>`
                        : `<span style="color:#94a3b8;font-size:12px;">—</span>`}
                </td>
                <td>${buildEval(s)}</td>
            </tr>`;
        });
    }

    if (optList.length) {
        html += `<tr class="section-row"><td colspan="7">
            <i class="ri-book-line" style="color:#0891b2;margin-right:5px;"></i>
            Optional subjects — ${optCred} credit${optCred !== 1 ? 's' : ''} from ${optList.length} subject${optList.length !== 1 ? 's' : ''}
        </td></tr>`;
        optList.forEach((s, i) => {
            const grade = s.grade || '—';
            html += `<tr class="${rowCls(s)}">
                <td style="text-align:center;color:#94a3b8;font-size:11px;">${compList.length + i + 1}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                        <strong style="font-size:13px;">${escapeHtml(s.subject_name)}</strong>
                        <span style="background:#e0f2fe;color:#0369a1;border:0.5px solid #bae6fd;font-size:9px;font-weight:700;padding:1px 6px;border-radius:10px;">OPTIONAL</span>
                    </div>
                </td>
                <td style="text-align:center;font-family:monospace;font-size:11px;color:#64748b;">${escapeHtml(s.subject_code || '—')}</td>
                <td style="text-align:center;">${scoreBar(s.total, grade)}</td>
                <td style="text-align:center;"><strong class="${gradeColorClass(grade)}" style="font-size:17px;">${grade}</strong></td>
                <td style="text-align:center;"><span style="color:#94a3b8;font-size:11px;font-style:italic;">No min grade</span></td>
                <td>${buildEval(s)}</td>
            </tr>`;
        });
    }

    html += `</tbody></table></div>`;
    return html;
}

/* ── Open promotion modal ── */
async function openPromotionModal(studentId, admissionNo, firstName, lastName, otherName, picture, gender, schoolclass, schoolarm, session, termid) {
    currentStudentId     = studentId;
    currentSchoolclassId = document.getElementById('idclass').value;
    currentSessionId     = document.getElementById('idsession').value;
    // Always use filter term (not per-row termid) so modal matches table evaluation
    currentTermId = document.getElementById('idterm').value;

    document.getElementById('modalStudentName').innerHTML =
        `<i class="ri-id-card-line me-2"></i>${escapeHtml(admissionNo)} — ${escapeHtml(firstName)} ${escapeHtml(lastName)}${otherName ? ' ' + escapeHtml(otherName) : ''}`;
    document.getElementById('modalStudentGender').innerHTML =
        `<i class="ri-gender-${gender === 'Male' ? 'male' : 'female'}-line me-1"></i>${escapeHtml(gender || 'N/A')}`;
    document.getElementById('modalCurrentClass').innerText   = schoolclass;
    document.getElementById('modalCurrentArm').innerText     = schoolarm || 'N/A';
    document.getElementById('modalCurrentSession').innerText = session;

    const imgEl = document.getElementById('modalStudentImage');
    setStudentImage(imgEl, normalizeImagePath(picture, gender), gender);

    document.getElementById('promotionForm').reset();
    ['newClassSelect','newSessionSelect','newTermSelect'].forEach(id => {
        document.getElementById(id).value = '';
    });
    ['promotionCheckbox','trialCheckbox','seePrincipalCheckbox','repeatCheckbox'].forEach(id => {
        document.getElementById(id).checked = false;
    });
    document.getElementById('recommendationCard').style.display  = 'none';
    document.getElementById('compulsoryCard').style.display      = 'none';
    document.getElementById('allSubjectsCard').style.display     = 'none';
    document.getElementById('allSubjectsContent').innerHTML      = '';
    document.getElementById('compulsoryContent').innerHTML       = '';
    document.getElementById('recommendationContent').innerHTML   = '';
    document.getElementById('modalOverallAverage').innerHTML     = '<span class="text-muted">Loading…</span>';

    showLoading('Loading student data...');

    try {
        const basis = getAverageBasis();
        const response = await axios.get(
            `/promotions/student-details/${studentId}/${currentSchoolclassId}/${currentSessionId}/${currentTermId}`,
            { params: { average_basis: basis } }
        );
        hideLoading();

        if (!response.data.success) {
            showToast(response.data.message || 'Failed to load student details', 'danger');
            return;
        }

        currentStudentData = response.data;

        const serverPic = response.data.student?.picture || picture;
        setStudentImage(imgEl, normalizeImagePath(serverPic, gender), gender);

        const result      = response.data.promotion_result;
        const avg         = response.data.overall_average;
        const allSubjects = response.data.all_subjects        || [];
        const compData    = response.data.compulsory_subjects || [];

        const avgEl    = document.getElementById('modalOverallAverage');
        const avgValue = avg !== null && avg !== undefined ? `${avg}%` : 'N/A';
        const avgCls   = avg !== null
            ? (avg >= 50 ? 'text-success' : avg >= 40 ? 'text-warning' : 'text-danger')
            : 'text-muted';
        const basisLbl = basis === 'cum' ? 'Cum' : 'Term Total';
        avgEl.innerHTML = `<span class="${avgCls} fs-5 fw-bold">${avgValue}</span> <small class="text-muted">(${basisLbl})</small>`;

        if (result && result.status) {
            const recCard = document.getElementById('recommendationCard');
            recCard.style.display = 'block';
            const statusColors = {
                promoted:      { bg: '#10b981', icon: 'ri-checkbox-circle-line' },
                trial:         { bg: '#f59e0b', icon: 'ri-time-line'            },
                see_principal: { bg: '#3b82f6', icon: 'ri-eye-line'             },
                repeated:      { bg: '#ef4444', icon: 'ri-repeat-line'          },
            };
            const sc = statusColors[result.status] || { bg: '#6b7280', icon: 'ri-question-line' };
            let html = `<div class="recommendation-card ${result.status}">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <div class="label text-muted mb-2">System Recommendation</div>
                        <span class="status-badge-lg text-white" style="background:${sc.bg};">
                            <i class="${sc.icon} me-1"></i>${escapeHtml(result.status_label || result.status)}
                        </span>
                    </div>`;
            if (result.required_average !== null && result.required_average !== undefined) {
                const metAvg = result.actual_average >= result.required_average;
                html += `<div class="text-end">
                    <div class="rule-badge"><i class="ri-percent-line me-1"></i>Required: ${result.required_average}%</div>
                    <div class="small mt-1 ${metAvg ? 'text-success' : 'text-danger'}">
                        ${metAvg ? '✓' : '✗'} Actual: ${result.actual_average ?? avg ?? 'N/A'}%
                    </div>
                </div>`;
            }
            html += `</div>`;
            if (result.applied_rule) {
                html += `<div class="mt-3 pt-3 border-top d-flex align-items-center flex-wrap gap-2">
                    <i class="ri-price-tag-3-line text-primary"></i>
                    <strong>Matched rule:</strong>
                    <span class="badge bg-primary">${escapeHtml(result.applied_rule.name)}</span>
                    ${result.applied_rule.description ? `<span class="small text-muted">— ${escapeHtml(formatRuleDescription(result.applied_rule.description))}</span>` : ''}
                </div>`;
            } else if (result.rule_logic === 'average_only') {
                html += `<div class="mt-3 pt-3 border-top d-flex align-items-center flex-wrap gap-2">
                    <i class="ri-percent-line text-info"></i>
                    <span class="small text-muted">Determined by Global Minimum Average only.</span>
                </div>`;
            }
            if (result.compulsory_count > 0) {
                const allPassed = result.passed_compulsory === result.compulsory_count;
                html += `<div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between">
                        <span><i class="ri-book-open-line me-1"></i>Compulsory subjects:</span>
                        <span class="${allPassed ? 'text-success' : 'text-danger'} fw-bold">
                            ${result.passed_compulsory}/${result.compulsory_count} passed
                        </span>
                    </div>`;
                if (result.failed_compulsory?.length) {
                    html += `<div class="mt-2 small text-danger"><i class="ri-close-circle-line me-1"></i>Failed: `;
                    result.failed_compulsory.forEach(f => {
                        html += `<span class="badge bg-danger me-1">${escapeHtml(f.subject || `Subject #${f.subject_id}`)}</span>`;
                    });
                    html += `</div>`;
                }
                html += `</div>`;
            }
            html += `</div>`;
            document.getElementById('recommendationContent').innerHTML = html;
        }

        if (allSubjects.length) {
            document.getElementById('allSubjectsCard').style.display = 'block';
            document.getElementById('allSubjectsContent').innerHTML = buildSubjectsTable(allSubjects, result);
        }

        if (compData.length) {
            const passCount  = compData.filter(s => s.pass_status === 'pass').length;
            const failCount  = compData.filter(s => s.pass_status === 'fail').length;
            const nsCount    = compData.filter(s => s.pass_status === 'not_sat').length;
            function gc2(g) {
                if (!g) return '#6b7280'; const u = g.toUpperCase();
                if (['A1','A'].includes(u)) return '#15803d';
                if (['B2','B3','B'].includes(u)) return '#1d4ed8';
                if (['C4','C5','C6','C'].includes(u)) return '#0369a1';
                if (['D7','D'].includes(u)) return '#d97706';
                return '#b91c1c';
            }
            let chtml = `<div class="d-flex gap-2 mb-3 flex-wrap">
                <span class="badge bg-success" style="font-size:13px;padding:6px 12px;"><i class="ri-checkbox-circle-line me-1"></i>${passCount} Passed</span>
                <span class="badge bg-danger"  style="font-size:13px;padding:6px 12px;"><i class="ri-close-circle-line me-1"></i>${failCount} Failed</span>
                ${nsCount ? `<span class="badge bg-secondary" style="font-size:13px;padding:6px 12px;"><i class="ri-minus-line me-1"></i>${nsCount} Not Sat</span>` : ''}
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Subject</th><th>Grade</th><th>Required</th><th>Rule Requirement</th><th>Status</th></tr></thead>
                    <tbody>`;
            compData.forEach(cs => {
                const sc2 = cs.pass_status === 'pass' ? 'success' : (cs.pass_status === 'fail' ? 'danger' : 'secondary');
                const ico = cs.pass_status === 'pass' ? '✓' : (cs.pass_status === 'fail' ? '✗' : '○');
                chtml += `<tr>
                    <td><strong>${escapeHtml(cs.subject)}</strong><br><small class="text-muted">${escapeHtml(cs.subject_code || '')}</small></td>
                    <td><strong style="color:${gc2(cs.student_grade || '')}">${cs.student_grade || 'Not Sat'}</strong></td>
                    <td>${cs.required_min_grade || '—'}</td>
                    <td><small class="text-muted">${cs.rule_requirement || '—'}</small></td>
                    <td><span class="badge bg-${sc2}">${ico} ${cs.pass_status_label || cs.pass_status}</span></td>
                </tr>`;
            });
            chtml += `</tbody></table></div>`;
            document.getElementById('compulsoryContent').innerHTML = chtml;
            document.getElementById('compulsoryCard').style.display = 'block';
        }

    } catch (error) {
        hideLoading();
        console.error('Error fetching student details:', error);
        showToast('Failed to load student details: ' + (error.response?.data?.message || error.message), 'danger');
    }

    new bootstrap.Modal(document.getElementById('promotionModal')).show();
}

function removeStudent(studentId, schoolclassId, sessionId, termId, admissionNo, firstName, lastName) {
    Swal.fire({
        title: 'Confirm Removal',
        text: `Remove ${admissionNo} - ${firstName} ${lastName} from this class?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Remove',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (!result.isConfirmed) return;
        showLoading('Removing student...');
        const fd = new FormData();
        fd.append('_method', 'DELETE');
        fd.append('schoolclassid', schoolclassId);
        fd.append('sessionid', sessionId);
        fd.append('termid', termId);
        axios.post(`/promotions/${studentId}`, fd, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'multipart/form-data' }
        }).then(response => {
            hideLoading();
            showToast(response.data.success ? response.data.message : (response.data.message || 'Failed to remove'), response.data.success ? 'success' : 'danger');
            if (response.data.success) filterData();
        }).catch(error => {
            hideLoading();
            showToast(error.response?.data?.message || 'Failed to remove student', 'danger');
        });
    });
}

function submitPromotion() {
    if (!currentStudentId) { showToast('Student ID not found', 'danger'); return; }

    const nc = document.getElementById('newClassSelect');
    const ns = document.getElementById('newSessionSelect');
    const nt = document.getElementById('newTermSelect');
    if (!nc.value)  { showToast('Please select a new class',   'warning'); return; }
    if (!ns.value)  { showToast('Please select a new session', 'warning'); return; }
    if (!nt.value)  { showToast('Please select a new term',    'warning'); return; }

    const cbs = ['promotionCheckbox','trialCheckbox','seePrincipalCheckbox','repeatCheckbox']
        .map(id => document.getElementById(id));
    if (cbs.filter(cb => cb.checked).length !== 1) {
        showToast('Please select exactly one promotion decision', 'warning'); return;
    }

    const fd = new FormData();
    fd.append('_method',           'PUT');
    fd.append('new_schoolclassid', nc.value);
    fd.append('new_sessionid',     ns.value);
    fd.append('new_termid',        nt.value);
    fd.append('promotion',         document.getElementById('promotionCheckbox').checked    ? '1' : '0');
    fd.append('trial',             document.getElementById('trialCheckbox').checked        ? '1' : '0');
    fd.append('see_principal',     document.getElementById('seePrincipalCheckbox').checked ? '1' : '0');
    fd.append('repeat',            document.getElementById('repeatCheckbox').checked       ? '1' : '0');

    Swal.fire({
        title: 'Confirm Update',
        text: "Update this student's promotion?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Update',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (!result.isConfirmed) return;
        showLoading('Updating promotion...');
        bootstrap.Modal.getInstance(document.getElementById('promotionModal'))?.hide();
        axios.post(`/promotions/${currentStudentId}`, fd, {
            headers: { 'X-CSRF-TOKEN': CSRF }
        }).then(response => {
            hideLoading();
            showToast(response.data.success ? response.data.message : (response.data.message || 'Failed to update'), response.data.success ? 'success' : 'danger');
            if (response.data.success) filterData();
        }).catch(error => {
            hideLoading();
            showToast(error.response?.data?.message || 'Failed to update promotion', 'danger');
        });
    });
}

function openBulkPromoteModal() {
    const n = document.querySelectorAll('.row-checkbox:checked').length;
    if (!n) { showToast('Select at least one student', 'warning'); return; }
    new bootstrap.Modal(document.getElementById('bulkPromoteModal')).show();
}

function submitBulkPromote() {
    const ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) { showToast('No students selected', 'warning'); return; }

    const newClass   = document.getElementById('bulkNewClass').value;
    const newSession = document.getElementById('bulkNewSession').value;
    const newTerm    = document.getElementById('bulkNewTerm').value;
    const decision   = document.getElementById('bulkDecision').value;

    if (!newClass || !newSession || !newTerm) {
        showToast('Select new class, session and term', 'warning');
        return;
    }

    const fd = new FormData();
    ids.forEach((id, i) => fd.append(`student_ids[${i}]`, id));
    fd.append('new_schoolclassid', newClass);
    fd.append('new_sessionid', newSession);
    fd.append('new_termid', newTerm);
    fd.append('decision', decision);
    fd.append('schoolclassid', document.getElementById('idclass').value);
    fd.append('sessionid', document.getElementById('idsession').value);
    fd.append('termid', document.getElementById('idterm').value);

    Swal.fire({
        title: 'Confirm Bulk Update',
        text: `Update ${ids.length} student(s)?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Update',
    }).then(result => {
        if (!result.isConfirmed) return;
        showLoading('Bulk updating…');
        bootstrap.Modal.getInstance(document.getElementById('bulkPromoteModal'))?.hide();
        axios.post('{{ route("promotions.bulk") }}', fd, {
            headers: { 'X-CSRF-TOKEN': CSRF }
        }).then(response => {
            hideLoading();
            showToast(response.data.message || (response.data.success ? 'Updated' : 'Failed'), response.data.success ? 'success' : 'danger');
            if (response.data.success) filterData();
        }).catch(error => {
            hideLoading();
            showToast(error.response?.data?.message || 'Bulk update failed', 'danger');
        });
    });
}

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput')?.focus();
        showToast('Search focused — type to filter students', 'info');
    }
    if (e.key === 'Escape') {
        const si = document.getElementById('searchInput');
        if (si && document.activeElement === si) {
            si.value = ''; filterData();
            showToast('Search cleared', 'info');
        }
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
        const ae = document.activeElement;
        if (ae && !['INPUT','TEXTAREA'].includes(ae.tagName)) {
            e.preventDefault();
            const sa = document.getElementById('selectAll');
            if (sa) {
                sa.checked = true;
                document.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = true;
                    cb.closest('tr')?.classList.add('selected');
                });
                updateBulkBar();
                showToast('All students selected', 'info');
            }
        }
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('idclass').addEventListener('change', filterData);
    document.getElementById('idsession').addEventListener('change', filterData);
    document.getElementById('idterm').addEventListener('change', filterData);
    document.getElementById('average_basis').addEventListener('change', filterData);

    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterData, 500);
    });

    setupCheckboxHandlers();
    updateStats();
    triggerRowEntrance();
    popPromotionBadges();
    setupRowSelection();

    const bulkBtn = document.getElementById('bulkPromoteActionBtn');
    const bulkBar = document.getElementById('bulkActionBar');
    if (bulkBtn && bulkBar) {
        new MutationObserver(() => {
            bulkBtn.classList.toggle('btn-pulse', bulkBar.classList.contains('visible'));
        }).observe(bulkBar, { attributes: true });
    }

    // Initial load if filters already selected
    if (document.getElementById('idclass').value &&
        document.getElementById('idsession').value &&
        document.getElementById('idterm').value) {
        filterData();
    }
});
</script>
@endsection