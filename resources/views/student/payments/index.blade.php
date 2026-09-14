{{-- resources/views/student/payments/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

<style>
:root {
    --navy:      #0f1c35;
    --navy-mid:  #1a2f55;
    --gold:      #c9a84c;
    --gold-lt:   #f5e6c0;
    --cream:     #f9f7f2;
    --paper:     #ffffff;
    --border:    #e3e7f0;
    --success:   #16a34a;
    --danger:    #dc2626;
    --info:      #2563eb;
    --purple:    #7c3aed;
    --muted:     #6b7280;
    --radius:    12px;
    --radius-sm: 8px;
    --shadow:    0 2px 8px rgba(0,0,0,.07);
}

/* ── Layout ──────────────────────────────────── */
.pay-portal { font-family: 'Segoe UI', Roboto, sans-serif; background: var(--cream); border-radius: var(--radius); overflow: hidden; }

/* ── Hero ─────────────────────────────────────── */
.pp-hero { background: linear-gradient(135deg, var(--navy) 0%, #1a3a6e 60%, #1e4da0 100%); padding: 36px 32px 28px; position: relative; overflow: hidden; }
.pp-hero::before { content:''; position:absolute; top:-60px; right:-40px; width:220px; height:220px; background:rgba(255,255,255,.05); border-radius:50%; }
.pp-hero-title { font-size:26px; font-weight:700; color:#fff; margin:0; }
.pp-hero-sub   { color:rgba(255,255,255,.6); font-size:13px; margin-top:4px; }
.pp-hero-badge { color:var(--gold); font-size:12px; margin-top:6px; }

/* ── Filter bar ───────────────────────────────── */
.pp-filter-bar { background:var(--paper); padding:14px 32px; display:flex; gap:14px; flex-wrap:wrap; align-items:center; border-bottom:1px solid var(--border); }
.pp-filter-select { padding:8px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); min-width:160px; font-size:13px; }
.pp-filter-btn { padding:8px 20px; background:var(--gold); color:var(--navy); font-weight:600; border:none; border-radius:var(--radius-sm); cursor:pointer; font-size:13px; }
.pp-receipt-btn { padding:8px 20px; background:var(--navy-mid); color:#fff; border:none; border-radius:var(--radius-sm); cursor:pointer; font-size:13px; display:inline-flex; align-items:center; gap:6px; text-decoration:none; margin-left:auto; }

/* ── Body ─────────────────────────────────────── */
.pp-body { padding:28px 24px; }

/* ── Identity card ───────────────────────────── */
.pp-identity { background:var(--paper); border-radius:var(--radius); padding:22px 26px; display:flex; align-items:center; gap:20px; margin-bottom:22px; border:1px solid var(--border); box-shadow:var(--shadow); }
.pp-avatar { width:60px; height:60px; border-radius:50%; background:var(--navy); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:22px; font-weight:700; overflow:hidden; flex-shrink:0; }
.pp-avatar img { width:100%; height:100%; object-fit:cover; }
.pp-identity-name { font-size:17px; font-weight:700; color:var(--navy); margin:0; }
.pp-identity-meta { display:flex; gap:14px; flex-wrap:wrap; margin-top:4px; font-size:12px; color:var(--muted); }

/* ── Summary strip ────────────────────────────── */
.pp-summary-strip { display:grid; grid-template-columns:repeat(5, 1fr); gap:12px; margin-bottom:22px; }
@media(max-width:900px){ .pp-summary-strip{ grid-template-columns:repeat(3,1fr); } }
@media(max-width:560px){ .pp-summary-strip{ grid-template-columns:repeat(2,1fr); } }
.pp-sum-card { background:var(--paper); border:1px solid var(--border); border-radius:var(--radius); padding:16px 14px; text-align:center; box-shadow:var(--shadow); }
.pp-sum-value { font-size:20px; font-weight:700; color:var(--navy); line-height:1.2; }
.pp-sum-label { font-size:10px; text-transform:uppercase; color:var(--muted); margin-top:3px; letter-spacing:.04em; }
.pp-sum-card.outstanding .pp-sum-value { color:var(--danger); }
.pp-sum-card.paid-card   .pp-sum-value { color:var(--success); }
.pp-sum-card.savings-card .pp-sum-value { color:var(--purple); }

/* ── Benefit banners ─────────────────────────── */
.pp-benefit { border-radius:10px; padding:12px 16px; margin-bottom:14px; display:flex; align-items:flex-start; gap:12px; font-size:13px; }
.pp-benefit.schol { background:#fef9c3; border:1px solid #fde68a; color:#92400e; }
.pp-benefit.disc  { background:#ede9fe; border:1px solid #ddd6fe; color:#6d28d9; }
.pp-benefit .icon { font-size:18px; flex-shrink:0; }

/* ── Bill cards ───────────────────────────────── */
.pp-bills-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:16px; margin-bottom:28px; }
.pp-bill-card { background:var(--paper); border:1px solid var(--border); border-radius:var(--radius); padding:20px; position:relative; overflow:hidden; box-shadow:var(--shadow); transition:transform .15s, box-shadow .15s; }
.pp-bill-card:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(0,0,0,.1); }
.pp-bill-stripe { position:absolute; top:0; left:0; right:0; height:3px; }
.pp-bill-card.paid-bill   .pp-bill-stripe { background:linear-gradient(90deg, #16a34a, #15803d); }
.pp-bill-card.partial-bill .pp-bill-stripe { background:linear-gradient(90deg, #2563eb, #1d4ed8); }
.pp-bill-card.unpaid-bill  .pp-bill-stripe { background:linear-gradient(90deg, #d97706, #b45309); }
.pp-bill-card.savings-bill .pp-bill-stripe { background:linear-gradient(90deg, #7c3aed, #6d28d9); }

.pp-bill-title { font-size:14px; font-weight:700; color:var(--navy); margin-bottom:2px; }
.pp-bill-desc  { font-size:11px; color:var(--muted); }
.pp-bill-amount { font-size:24px; font-weight:700; color:var(--navy); text-align:center; margin:12px 0 4px; }
.pp-bill-amount-note { font-size:10px; color:var(--muted); text-align:center; }
.pp-bill-orig   { font-size:11px; color:var(--muted); text-align:center; text-decoration:line-through; }

.pp-bill-row { display:flex; justify-content:space-between; align-items:center; margin:10px 0 6px; }
.pp-bill-mini-label { font-size:10px; text-transform:uppercase; color:var(--muted); }
.pp-bill-mini-value { font-size:13px; font-weight:700; }

.pp-progress-track { height:7px; background:#e2e8f0; border-radius:4px; overflow:hidden; margin:10px 0 14px; }
.pp-progress-fill  { height:100%; border-radius:4px; }
.fill-paid    { background:linear-gradient(90deg,#16a34a,#15803d); }
.fill-partial { background:linear-gradient(90deg,#2563eb,#1d4ed8); }
.fill-unpaid  { background:linear-gradient(90deg,#d97706,#b45309); }

.pp-status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-paid    { background:#d1fae5; color:#065f46; }
.badge-partial { background:#dbeafe; color:#1e40af; }
.badge-unpaid  { background:#fef3c7; color:#92400e; }

.pp-schol-pill { display:inline-flex; align-items:center; gap:4px; background:#fef9c3; border:1px solid #fde68a; color:#92400e; border-radius:20px; padding:2px 9px; font-size:10px; font-weight:600; }
.pp-disc-pill  { display:inline-flex; align-items:center; gap:4px; background:#ede9fe; border:1px solid #ddd6fe; color:#6d28d9; border-radius:20px; padding:2px 9px; font-size:10px; font-weight:600; }

/* ── Trend card ───────────────────────────────── */
.pp-trend-card { background:var(--paper); border:1px solid var(--border); border-radius:var(--radius); padding:22px; margin-bottom:22px; box-shadow:var(--shadow); }
.pp-section-title { font-size:13px; font-weight:700; color:var(--navy); text-transform:uppercase; letter-spacing:.05em; margin-bottom:14px; }

/* ── History table ────────────────────────────── */
.pp-history-card { background:var(--paper); border:1px solid var(--border); border-radius:var(--radius); padding:22px; box-shadow:var(--shadow); }
.pp-table th { background:var(--navy); color:#fff; padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border:none; }
.pp-table th:first-child { border-radius:8px 0 0 8px; }
.pp-table th:last-child  { border-radius:0 8px 8px 0; }
.pp-table td { padding:11px 14px; font-size:13px; border-bottom:1px solid var(--border); vertical-align:middle; }
.pp-table tr:hover td { background:#f0f9ff; }
.pp-table tr:last-child td { border-bottom:none; }

/* ── Empty state ──────────────────────────────── */
.pp-empty { text-align:center; padding:60px; background:var(--paper); border-radius:var(--radius); }
.pp-empty h3 { color:var(--navy); margin-bottom:6px; }
.pp-empty p  { color:var(--muted); font-size:13px; }
</style>

<div class="pay-portal">

    {{-- HERO --}}
    <div class="pp-hero">
        <h1 class="pp-hero-title">My Payment Portal</h1>
        <p class="pp-hero-sub">View your school fee bills, payment history and download receipts</p>
        @if(isset($term) && isset($session))
            <div class="pp-hero-badge">{{ $term->term }} &middot; {{ $session->session }}</div>
        @endif
    </div>

    {{-- FILTER BAR --}}
    <form method="GET" action="{{ route('student.payments') }}">
        <div class="pp-filter-bar">
            <select name="term_id" class="pp-filter-select">
                <option value="">All Terms</option>
                @foreach($terms as $t)
                    <option value="{{ $t->id }}" {{ $selectedTermId == $t->id ? 'selected' : '' }}>{{ $t->term }}</option>
                @endforeach
            </select>

            <select name="session_id" class="pp-filter-select">
                <option value="">All Sessions</option>
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ $selectedSessionId == $s->id ? 'selected' : '' }}>{{ $s->session }}</option>
                @endforeach
            </select>

            <button type="submit" class="pp-filter-btn">Apply Filter</button>

            @if(isset($bills) && $bills->isNotEmpty())
                <a href="{{ route('student.payments.receipt') }}?term_id={{ $selectedTermId }}&session_id={{ $selectedSessionId }}"
                   class="pp-receipt-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download Receipt
                </a>
            @endif
        </div>
    </form>

    <div class="pp-body">

        @if(session('error'))
            <div class="alert alert-warning mb-3">{{ session('error') }}</div>
        @endif

        @if(!isset($bills) || $bills->isEmpty())
            <div class="pp-empty">
                <h3>No Bills Found</h3>
                <p>No fee bills have been assigned for the selected term and session.</p>
            </div>
        @else

        {{-- IDENTITY CARD --}}
        <div class="pp-identity">
            <div class="pp-avatar">
                @if(!empty($studentPicture))
                    <img src="{{ asset('storage/student_avatars/' . $studentPicture) }}" alt="Photo">
                @else
                    {{ strtoupper(substr($student->firstname ?? '', 0, 1)) }}{{ strtoupper(substr($student->lastname ?? '', 0, 1)) }}
                @endif
            </div>
            <div>
                <p class="pp-identity-name">{{ $student->firstname }} {{ $student->lastname }}</p>
                <div class="pp-identity-meta">
                    <span>{{ $student->admissionNo ?? 'N/A' }}</span>
                    @isset($class)<span>Class: {{ $class->schoolclass }}</span>@endisset
                    @isset($term)<span>Term: {{ $term->term }}</span>@endisset
                    @isset($session)<span>Session: {{ $session->session }}</span>@endisset
                </div>
            </div>
        </div>

        {{-- SUMMARY STRIP --}}
        <div class="pp-summary-strip">
            <div class="pp-sum-card">
                <div class="pp-sum-value">{{ $bills->count() }}</div>
                <div class="pp-sum-label">Total Bills</div>
            </div>
            <div class="pp-sum-card">
                <div class="pp-sum-value">₦{{ number_format($totals['adjusted'], 0) }}</div>
                <div class="pp-sum-label">Total Payable</div>
            </div>
            <div class="pp-sum-card paid-card">
                <div class="pp-sum-value">₦{{ number_format($totals['paid'], 0) }}</div>
                <div class="pp-sum-label">Total Paid</div>
            </div>
            <div class="pp-sum-card outstanding">
                <div class="pp-sum-value">₦{{ number_format($totals['outstanding'], 0) }}</div>
                <div class="pp-sum-label">Outstanding</div>
            </div>
            <div class="pp-sum-card savings-card">
                <div class="pp-sum-value">₦{{ number_format($totals['savings'], 0) }}</div>
                <div class="pp-sum-label">Total Savings</div>
            </div>
        </div>

        {{-- SCHOLARSHIP & DISCOUNT BANNERS --}}
        @if(isset($scholarshipAssignment) && $scholarshipAssignment)
        <div class="pp-benefit schol">
            <span class="icon">🏆</span>
            <div>
                <strong>Scholarship Active: {{ $scholarshipAssignment->scholarship->title ?? 'Scholarship' }}</strong><br>
                <span style="font-size:12px;">
                    Total Savings: ₦{{ number_format($totals['savings'], 0) }}
                </span>
            </div>
        </div>
        @endif

        @if(isset($discountAssignments) && $discountAssignments->isNotEmpty())
        <div class="pp-benefit disc">
            <span class="icon">🏷️</span>
            <div>
                <strong>Discount(s) Applied</strong>
            </div>
        </div>
        @endif

        {{-- BILLS GRID --}}
        <div class="pp-bills-grid">
            @foreach($bills as $bill)
            @php
                $cardClass = $bill['is_paid'] ? 'paid-bill' : ($bill['is_partial'] ? 'partial-bill' : ($bill['total_savings'] > 0 ? 'savings-bill' : 'unpaid-bill'));
                $fillClass = $bill['is_paid'] ? 'fill-paid' : ($bill['is_partial'] ? 'fill-partial' : 'fill-unpaid');
            @endphp
            <div class="pp-bill-card {{ $cardClass }}">
                <div class="pp-bill-stripe"></div>

                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-top:6px;">
                    <div>
                        <div class="pp-bill-title">{{ $bill['title'] }}</div>
                        @if($bill['description'])
                            <div class="pp-bill-desc">{{ $bill['description'] }}</div>
                        @endif
                    </div>
                    <span class="pp-status-badge {{ $bill['is_paid'] ? 'badge-paid' : ($bill['is_partial'] ? 'badge-partial' : 'badge-unpaid') }}">
                        {{ $bill['is_paid'] ? '✓ Paid' : ($bill['is_partial'] ? '⬤ Partial' : '○ Unpaid') }}
                    </span>
                </div>

                @if($bill['total_savings'] > 0)
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin:10px 0;">
                    @if($bill['scholarship_deduction'] > 0)
                        <span class="pp-schol-pill">🏆 -₦{{ number_format($bill['scholarship_deduction'], 0) }}</span>
                    @endif
                    @if($bill['discount_deduction'] > 0)
                        <span class="pp-disc-pill">🏷️ -₦{{ number_format($bill['discount_deduction'], 0) }}</span>
                    @endif
                </div>
                @endif

                <div>
                    @if($bill['total_savings'] > 0)
                        <div class="pp-bill-orig">₦{{ number_format($bill['original_amount'], 0) }}</div>
                    @endif
                    <div class="pp-bill-amount">₦{{ number_format($bill['adjusted_amount'], 0) }}</div>
                    <div class="pp-bill-amount-note">{{ $bill['total_savings'] > 0 ? 'After savings' : 'Payable amount' }}</div>
                </div>

                <div class="pp-bill-row">
                    <div>
                        <div class="pp-bill-mini-label">Paid</div>
                        <div class="pp-bill-mini-value" style="color:var(--success)">₦{{ number_format($bill['amount_paid'], 0) }}</div>
                    </div>
                    <div>
                        <div class="pp-bill-mini-label">Balance</div>
                        <div class="pp-bill-mini-value" style="color:{{ $bill['balance'] > 0 ? 'var(--danger)' : 'var(--success)' }}">
                            ₦{{ number_format($bill['balance'], 0) }}
                        </div>
                    </div>
                    <div>
                        <div class="pp-bill-mini-label">Progress</div>
                        <div class="pp-bill-mini-value">{{ $bill['progress'] }}%</div>
                    </div>
                </div>

                <div class="pp-progress-track">
                    <div class="pp-progress-fill {{ $fillClass }}" style="width:{{ $bill['progress'] }}%"></div>
                </div>

                @if($bill['due_date'])
                    <div style="font-size:11px; color:var(--muted); text-align:center;">
                        Due: {{ $bill['due_date'] }}
                    </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- PAYMENT TREND --}}
        @if(isset($paymentTrend) && count($paymentTrend) > 0)
        <div class="pp-trend-card">
            <div class="pp-section-title">Payment Trend</div>
            <div style="height:200px;">
                <canvas id="paymentTrendChart"></canvas>
            </div>
        </div>
        @endif

        {{-- PAYMENT HISTORY --}}
        @if(isset($paymentHistory) && $paymentHistory->isNotEmpty())
        <div class="pp-history-card">
            <div class="pp-section-title">Payment History</div>
            <div class="table-responsive">
                <table class="table pp-table w-100 mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Bill</th>
                            <th>Amount Paid</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentHistory as $idx => $payment)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $payment->schoolBill?->title ?? '—' }}</strong>
                            </td>
                            <td style="color:var(--success); font-weight:700;">
                                ₦{{ number_format($payment->total_paid ?? 0, 2) }}
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    {{ $payment->payment_method ?? '—' }}
                                </span>
                            </td>
                            <td>{{ $payment->created_at?->format('d M Y') }}</td>
                            <td>
                                <span class="badge {{ $payment->payment_status === 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($payment->payment_status ?? 'pending') }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @endif {{-- end bills check --}}
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    @if(isset($paymentTrend) && count($paymentTrend) > 0)
    const ctx = document.getElementById('paymentTrendChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(array_keys($paymentTrend)),
                datasets: [{
                    label: 'Amount Paid (₦)',
                    data: @json(array_values($paymentTrend)),
                    backgroundColor: 'rgba(201,168,76,0.8)',
                    borderColor: '#c9a84c',
                    borderWidth: 2,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: val => '₦' + Number(val).toLocaleString('en-NG') }
                    }
                }
            }
        });
    }
    @endif
</script>

        </div>
    </div>
</div>
@endsection
