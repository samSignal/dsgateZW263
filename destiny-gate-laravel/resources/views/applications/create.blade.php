<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Application — Willowcrest College</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f3f7f5; color: #0f172a; }
        .container { max-width: 860px; margin: 44px auto; padding: 0 20px; }
        .shell { display: grid; grid-template-columns: 1fr; gap: 18px; }
        .card { background: #fff; border-radius: 18px; padding: 30px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); border: 1px solid rgba(27, 42, 74, 0.12); }
        .header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand-badge { width: 46px; height: 46px; border-radius: 12px; background: #eef1f8; border: 1px solid #d1fae5; display: flex; align-items: center; justify-content: center; }
        .brand-title { font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1.1; }
        .brand-sub { font-size: 12px; color: #64748b; margin-top: 2px; }
        .step { font-size: 12px; font-weight: 700; color: #8a6b34; background: rgba(27, 42, 74, 0.08); border: 1px solid rgba(27, 42, 74, 0.18); padding: 6px 10px; border-radius: 999px; white-space: nowrap; }
        .title { font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -0.3px; margin: 10px 0 6px; }
        .subtitle { font-size: 13px; color: #64748b; line-height: 1.6; margin: 0 0 6px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px; letter-spacing: 0.2px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 11px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13px; background: #fff; box-sizing: border-box; transition: border-color .15s, box-shadow .15s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #b6924c; box-shadow: 0 0 0 4px rgba(27, 42, 74, 0.12); }
        .btn { background: #b6924c; color: #fff; border: none; padding: 12px 18px; border-radius: 12px; font-size: 14px; font-weight: 800; cursor: pointer; width: 100%; letter-spacing: 0.2px; }
        .btn:hover { background: #0f1a2e; }
        .btn-secondary { background: #fff; color: #8a6b34; border: 1.5px solid rgba(27, 42, 74, 0.35); }
        .btn-secondary:hover { background: #eef1f8; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: 12px 14px; border-radius: 12px; border: 1px solid #fecaca; margin-bottom: 14px; font-size: 13px; }
        .alert-success { background: #ecfdf5; color: #065f46; padding: 12px 14px; border-radius: 12px; border: 1px solid #a7f3d0; margin-bottom: 14px; font-size: 13px; }
        .summary { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 16px; margin-bottom: 16px; }
        .summary h3 { margin: 0 0 10px; font-size: 12px; letter-spacing: 0.4px; color: #475569; text-transform: uppercase; }
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; }
        .pill { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; padding: 8px 10px; border-radius: 999px; background: rgba(27, 42, 74, 0.06); border: 1px solid rgba(27, 42, 74, 0.14); color: #0f1a2e; }
        .footer { text-align: center; padding: 6px 0; }
        .footer a { color: #64748b; font-size: 12px; text-decoration: none; }
        .footer a:hover { color: #0f172a; text-decoration: underline; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
        @media (max-width: 820px) { .grid-3 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
@php
    $mode = $mode ?? 'apply';
    $resumeToken = trim((string) ($resumeToken ?? ''));
    $draft = $draft ?? null;
    $prefill = $draft ? $draft->toArray() : [];

    $value = function (string $key, $default = null) use ($prefill) {
        return old($key, $prefill[$key] ?? $default);
    };

    $defaultYearId = collect($academicYears ?? [])->firstWhere('is_active', true)->id ?? (collect($academicYears ?? [])->first()->id ?? null);
    $defaultTermId = collect($terms ?? [])->firstWhere('is_current', true)->id ?? (collect($terms ?? [])->first()->id ?? null);

    $selectedYearId = (int) $value('academic_year_id', $defaultYearId);
    $selectedTermId = (int) $value('term_id', $defaultTermId);
    $selectedFormId = (int) $value('form_id');
    $selectedCategoryId = (int) $value('category_id');

    $filled = function (string $key) use ($value) {
        $v = $value($key);
        return !is_null($v) && $v !== '';
    };

    $showGuardian2 = $filled('guardian2_name') || $filled('guardian2_email') || $filled('guardian2_phone');
    $showGuardian3 = $filled('guardian3_name') || $filled('guardian3_email') || $filled('guardian3_phone');

    $step1Fields = ['academic_year_id', 'term_id', 'form_id', 'category_id'];
    $step2Fields = ['first_name', 'middle_name', 'last_name', 'date_of_birth', 'id_number', 'student_address', 'email', 'phone'];
    $step3Fields = ['guardian_name', 'guardian_email', 'guardian_phone', 'guardian2_name', 'guardian2_email', 'guardian2_phone', 'guardian3_name', 'guardian3_email', 'guardian3_phone'];
    $step4Fields = ['previous_school', 'former_grade', 'reason_for_joining'];
    $step5Fields = ['student_document', 'results_document', 'parent_document', 'transfer_letter'];

    $initialStep = 1;
    if (collect($step2Fields)->some(fn ($k) => $filled($k))) $initialStep = 2;
    if (collect($step3Fields)->some(fn ($k) => $filled($k))) $initialStep = 3;
    if (collect($step4Fields)->some(fn ($k) => $filled($k))) $initialStep = 4;

    if ($draft && $draft->last_saved_step && !$errors->any()) {
        $initialStep = (int) $draft->last_saved_step;
    }

    if ($errors->any()) {
        if (collect($step5Fields)->some(fn ($k) => $errors->has($k))) $initialStep = 5;
        elseif (collect($step4Fields)->some(fn ($k) => $errors->has($k))) $initialStep = 4;
        elseif (collect($step3Fields)->some(fn ($k) => $errors->has($k))) $initialStep = 3;
        elseif (collect($step2Fields)->some(fn ($k) => $errors->has($k))) $initialStep = 2;
        else $initialStep = 1;
    }
@endphp

<div class="container">
    <div class="shell">
        <div class="card">
            <div class="header">
                <div class="brand">
                    <div class="brand-badge">
                        <img src="/logo-mark.png" alt="Willowcrest College" style="width:30px; height:30px; object-fit:contain;" />
                    </div>
                    <div>
                        <div class="brand-title">Willowcrest College</div>
                        <div class="brand-sub">Online Admission Application</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                    @if(($draft?->application_number))
                        <div class="pill">App #: {{ $draft->application_number }}</div>
                    @endif
                    <div class="step" id="stepIndicator"></div>
                    @if($mode === 'apply')
                        <button
                            type="submit"
                            form="applicationForm"
                            formaction="{{ route('applications.draft') }}"
                            formnovalidate
                            class="btn btn-secondary"
                            style="width:auto; padding:8px 12px; font-size:12px; border-radius:999px;"
                        >
                            Save &amp; Continue Later
                        </button>
                    @endif
                </div>
            </div>

            @if(session('draft_saved') && $resumeToken)
                <div class="summary" style="margin-top: 0;">
                    <h3>Saved</h3>
                    <div class="summary-grid">
                        <div class="pill">Resume Token: {{ $resumeToken }}</div>
                        @if(($draft?->application_number))
                            <div class="pill">Application #: {{ $draft->application_number }}</div>
                        @endif
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="alert-error">
                    <ul style="margin:0; padding-left:16px;">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @if(session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif
            @if($mode === 'resume')
                <div class="title">Resume Application</div>
                <p class="subtitle">Enter your resume token to continue where you left off.</p>

                @if($resumeToken !== '' && !$draft)
                    <div class="alert-error">Invalid token. Please check and try again.</div>
                @endif

                <form method="POST" action="{{ route('applications.resume.submit') }}">
                    @csrf
                    <div class="form-group" style="margin-top: 14px;">
                        <label>Resume Token *</label>
                        <input type="text" name="token" value="{{ $resumeToken }}" required>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 18px;">
                        <a href="/" class="btn btn-secondary" style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none;">Back</a>
                        <button type="submit" class="btn">Resume</button>
                    </div>
                </form>
            @elseif($mode === 'track')
                <div class="title">Track Application</div>
                <p class="subtitle">Enter your application number and email address to check the status.</p>

                <form method="POST" action="{{ route('applications.track.submit') }}">
                    @csrf
                    <div class="grid-2" style="margin-top: 14px;">
                        <div class="form-group">
                            <label>Application Number *</label>
                            <input type="text" name="application_number" value="{{ old('application_number', $tracked?->application_number ?? '') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" value="{{ old('email', $tracked?->email ?? '') }}" required>
                        </div>
                    </div>
                    <button type="submit" class="btn">Check Status</button>
                </form>

                @php($tracked = $trackedApplication ?? null)
                @php($requests = $trackedRequests ?? collect())
                @if(isset($trackedApplication) && !$tracked)
                    <div class="alert-error" style="margin-top:14px;">No application found for those details.</div>
                @endif
                @if($tracked)
                    <div class="summary" style="margin-top: 14px;">
                        <h3>Result</h3>
                        <div class="summary-grid">
                            <div class="pill">Application #: {{ $tracked->application_number }}</div>
                            <div class="pill">Status: {{ ucfirst($tracked->status) }}</div>
                            <div class="pill">Applicant: {{ $tracked->full_name }}</div>
                            <div class="pill">Year: {{ $tracked->academic_year }}</div>
                            @if(!empty($tracked->offer_accepted_at))
                                <div class="pill">Offer Accepted: {{ $tracked->offer_accepted_at->format('Y-m-d H:i') }}</div>
                            @endif
                        </div>
                    </div>

                    @if($tracked->status === 'offered' && !empty($tracked->offer_letter_token))
                        <div class="summary" style="margin-top: 14px;">
                            <h3>Offer Letter</h3>
                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; align-items:center;">
                                <div style="font-size:13px; color:#0f172a; font-weight:700;">
                                    {{ !empty($tracked->offer_accepted_at) ? 'Offer letter accepted. Admissions can now prepare for enrolment.' : 'A provisional place has been offered.' }}
                                </div>
                                <a href="{{ route('applications.offer-letter', $tracked->offer_letter_token) }}" target="_blank" class="btn" style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none;">
                                    {{ !empty($tracked->offer_accepted_at) ? 'View Accepted Offer' : 'View Offer Letter' }}
                                </a>
                                <form method="POST" action="{{ route('applications.offer-letter.send', $tracked->offer_letter_token) }}" style="margin:0;">
                                    @csrf
                                    <input type="hidden" name="application_number" value="{{ old('application_number', $tracked?->application_number ?? '') }}">
                                    <input type="hidden" name="email" value="{{ old('email', $tracked?->email ?? '') }}">
                                    <button type="submit" class="btn btn-secondary" style="width:100%; height:42px;">Send to Email</button>
                                </form>
                            </div>
                        </div>
                    @endif

                    @php($pendingRequests = $pendingRequests ?? collect())
                    @php($documentLabels = $documentLabels ?? [])
                    @if($pendingRequests->count() > 0)
                        <div class="summary" style="margin-top: 14px;">
                            <h3>Document Resubmission Required</h3>
                            <p style="font-size:12px; color:#64748b; margin:0 0 10px;">Please upload the requested document(s) below.</p>

                            @foreach($pendingRequests as $r)
                                <div style="border:1px solid #e2e8f0; border-radius:12px; padding:12px; background:#fff; margin-bottom:10px;">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                                        <div>
                                            <div style="font-size:12px; font-weight:800; color:#0f172a;">{{ $documentLabels[$r->document_key] ?? 'Document' }}</div>
                                            @if(!empty($r->instructions))
                                                <div style="font-size:12px; color:#64748b; margin-top:6px; white-space:pre-wrap;">{{ $r->instructions }}</div>
                                            @endif
                                        </div>
                                        <div class="pill">Requested</div>
                                    </div>

                                    <form method="POST" action="{{ route('applications.document-requests.upload', $r->id) }}" enctype="multipart/form-data" style="margin-top:10px;">
                                        @csrf
                                        <input type="hidden" name="application_number" value="{{ old('application_number', $tracked?->application_number ?? '') }}">
                                        <input type="hidden" name="email" value="{{ old('email', $tracked?->email ?? '') }}">
                                        <div class="grid-2" style="align-items:end;">
                                            <div class="form-group" style="margin-bottom:0;">
                                                <label>Upload (PDF or image, max 5MB) *</label>
                                                <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*">
                                            </div>
                                            <button type="submit" class="btn" style="height:42px;">Upload</button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            @else
            <form method="POST" action="{{ route('applications.store') }}" id="applicationForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="resume_token" id="resume_token" value="{{ $resumeToken }}">
                <input type="hidden" name="current_step" id="current_step" value="{{ $initialStep }}">

                <div class="summary" style="margin-top: 10px;">
                    <h3>Application Summary</h3>
                    <div class="summary-grid">
                        <div class="pill" id="summaryYear">Academic Year: —</div>
                        <div class="pill" id="summaryTerm">Term: —</div>
                        <div class="pill" id="summaryForm">Form: —</div>
                        <div class="pill" id="summaryCategory">Category: —</div>
                    </div>
                </div>

                <div data-step="1" id="step1">
                    <div class="title">Select Year, Term, Form & Category</div>
                    <p class="subtitle">All fields are required before you can continue.</p>

                    <div class="grid-2" style="margin-top: 14px;">
                        <div class="form-group">
                            <label>Academic Year *</label>
                            <select name="academic_year_id" id="academic_year_id" required>
                                <option value="" disabled {{ $selectedYearId ? '' : 'selected' }}>Select academic year</option>
                                @foreach(($academicYears ?? []) as $y)
                                    <option value="{{ $y->id }}" {{ (int)$y->id === (int)$selectedYearId ? 'selected' : '' }}>{{ $y->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Term *</label>
                            <select name="term_id" id="term_id" required>
                                <option value="" disabled {{ $selectedTermId ? '' : 'selected' }}>Select term</option>
                                @foreach(($terms ?? []) as $t)
                                    <option value="{{ $t->id }}" data-year-id="{{ $t->academic_year_id }}" {{ (int)$t->id === (int)$selectedTermId ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Form *</label>
                            <select name="form_id" id="form_id" required>
                                <option value="" disabled {{ $selectedFormId ? '' : 'selected' }}>Select form</option>
                                @foreach(($forms ?? []) as $f)
                                    <option value="{{ $f->id }}" {{ (int)$f->id === (int)$selectedFormId ? 'selected' : '' }}>{{ $f->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category_id" id="category_id" required>
                                <option value="" disabled {{ $selectedCategoryId ? '' : 'selected' }}>Select category</option>
                                @foreach(($categories ?? []) as $c)
                                    <option value="{{ $c->id }}" {{ (int)$c->id === (int)$selectedCategoryId ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 18px;">
                        <a href="/" class="btn btn-secondary" style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none;">Back</a>
                        <button type="button" class="btn" id="nextToStep2">Next</button>
                    </div>
                </div>

                <div data-step="2" id="step2" style="display:none;">
                    <div class="title">Student Basic Information</div>
                    <p class="subtitle">All fields are required before you can continue.</p>

                    <div class="grid-3" style="margin-top: 14px;">
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="first_name" value="{{ $value('first_name') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" value="{{ $value('middle_name') }}">
                        </div>
                        <div class="form-group">
                            <label>Surname *</label>
                            <input type="text" name="last_name" value="{{ $value('last_name') }}" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Date of Birth *</label>
                            <input type="date" name="date_of_birth" value="{{ $value('date_of_birth') }}" required>
                        </div>
                        <div class="form-group">
                            <label>ID Number *</label>
                            <input type="text" name="id_number" value="{{ $value('id_number') }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Student Address *</label>
                        <textarea name="student_address" rows="3" required>{{ $value('student_address') }}</textarea>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" value="{{ $value('email') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Phone *</label>
                            <input type="text" name="phone" value="{{ $value('phone') }}" required>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 18px;">
                        <button type="button" class="btn btn-secondary" id="backToStep1">Back</button>
                        <button type="button" class="btn" id="nextToStep3">Next</button>
                    </div>
                </div>

                <div data-step="3" id="step3" style="display:none;">
                    <div class="title">Guardian / Parent Details</div>
                    <p class="subtitle">Minimum 1 guardian, maximum 3. Each guardian must have name, email, and phone.</p>

                    <div style="margin-top: 14px;">
                        <div class="summary" style="margin-bottom: 14px;">
                            <h3>Guardian 1 (Required)</h3>
                            <div class="grid-2">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Full Name *</label>
                                    <input type="text" name="guardian_name" value="{{ $value('guardian_name') }}" required>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Phone *</label>
                                    <input type="text" name="guardian_phone" value="{{ $value('guardian_phone') }}" required>
                                </div>
                            </div>
                            <div class="form-group" style="margin-top:14px; margin-bottom:0;">
                                <label>Email *</label>
                                <input type="email" name="guardian_email" value="{{ $value('guardian_email') }}" required>
                            </div>
                        </div>

                        <div class="summary" id="guardian2Card" style="{{ $showGuardian2 ? '' : 'display:none;' }} margin-bottom: 14px;">
                            <h3>Guardian 2</h3>
                            <div class="grid-2">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Full Name *</label>
                                    <input type="text" name="guardian2_name" value="{{ $value('guardian2_name') }}" {{ $showGuardian2 ? 'required' : '' }} {{ $showGuardian2 ? '' : 'disabled' }}>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Phone *</label>
                                    <input type="text" name="guardian2_phone" value="{{ $value('guardian2_phone') }}" {{ $showGuardian2 ? 'required' : '' }} {{ $showGuardian2 ? '' : 'disabled' }}>
                                </div>
                            </div>
                            <div class="grid-2" style="margin-top:14px;">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Email *</label>
                                    <input type="email" name="guardian2_email" value="{{ $value('guardian2_email') }}" {{ $showGuardian2 ? 'required' : '' }} {{ $showGuardian2 ? '' : 'disabled' }}>
                                </div>
                                <div style="display:flex; align-items:flex-end;">
                                    <button type="button" class="btn btn-secondary" id="removeGuardian2">Remove</button>
                                </div>
                            </div>
                        </div>

                        <div class="summary" id="guardian3Card" style="{{ $showGuardian3 ? '' : 'display:none;' }} margin-bottom: 14px;">
                            <h3>Guardian 3</h3>
                            <div class="grid-2">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Full Name *</label>
                                    <input type="text" name="guardian3_name" value="{{ $value('guardian3_name') }}" {{ $showGuardian3 ? 'required' : '' }} {{ $showGuardian3 ? '' : 'disabled' }}>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Phone *</label>
                                    <input type="text" name="guardian3_phone" value="{{ $value('guardian3_phone') }}" {{ $showGuardian3 ? 'required' : '' }} {{ $showGuardian3 ? '' : 'disabled' }}>
                                </div>
                            </div>
                            <div class="grid-2" style="margin-top:14px;">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Email *</label>
                                    <input type="email" name="guardian3_email" value="{{ $value('guardian3_email') }}" {{ $showGuardian3 ? 'required' : '' }} {{ $showGuardian3 ? '' : 'disabled' }}>
                                </div>
                                <div style="display:flex; align-items:flex-end;">
                                    <button type="button" class="btn btn-secondary" id="removeGuardian3">Remove</button>
                                </div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                            <button type="button" class="btn btn-secondary" id="addGuardian">Add another guardian</button>
                            <div></div>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 18px;">
                        <button type="button" class="btn btn-secondary" id="backToStep2">Back</button>
                        <button type="button" class="btn" id="nextToStep4">Next</button>
                    </div>
                </div>

                <div data-step="4" id="step4" style="display:none;">
                    <div class="title">Academic History</div>
                    <p class="subtitle">All fields are required before you can continue.</p>

                    <div class="grid-2" style="margin-top: 14px;">
                        <div class="form-group">
                            <label>Previous School *</label>
                            <input type="text" name="previous_school" value="{{ $value('previous_school') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Former Form / Grade *</label>
                            <input type="text" name="former_grade" value="{{ $value('former_grade') }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Why do you want to join us? *</label>
                        <textarea name="reason_for_joining" rows="4" required>{{ $value('reason_for_joining') }}</textarea>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 18px;">
                        <button type="button" class="btn btn-secondary" id="backToStep3">Back</button>
                        <button type="button" class="btn" id="nextToStep5">Next</button>
                    </div>
                </div>

                <div data-step="5" id="step5" style="display:none;">
                    <div class="title">Upload Documents</div>
                    <p class="subtitle">Upload the required documents to complete your application. Transfer letter can be uploaded later.</p>

                    <div class="grid-2" style="margin-top: 14px;">
                        <div class="form-group">
                            <label>Student Birth Certificate / National ID *</label>
                            <input type="file" name="student_document" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*">
                        </div>
                        <div class="form-group">
                            <label>Previous / Current Results *</label>
                            <input type="file" name="results_document" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Parent ID / Birth Certificate *</label>
                            <input type="file" name="parent_document" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*">
                        </div>
                        <div class="form-group">
                            <label>Transfer Letter</label>
                            <input type="file" name="transfer_letter" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 18px;">
                        <button type="button" class="btn btn-secondary" id="backToStep4">Back</button>
                        <button type="submit" class="btn">Submit Application</button>
                    </div>
                </div>
            </form>
            @endif
        </div>

        <div class="footer">
            <a href="/">← Back to Portal</a>
        </div>
    </div>
</div>

<script>
    (function () {
        var initialStep = Number({{ (int) $initialStep }}) || 1;

        var stepIndicator = document.getElementById('stepIndicator');
        var step1 = document.getElementById('step1');
        var step2 = document.getElementById('step2');
        var step3 = document.getElementById('step3');
        var step4 = document.getElementById('step4');
        var step5 = document.getElementById('step5');

        var nextToStep2 = document.getElementById('nextToStep2');
        var nextToStep3 = document.getElementById('nextToStep3');
        var nextToStep4 = document.getElementById('nextToStep4');
        var nextToStep5 = document.getElementById('nextToStep5');
        var backToStep1 = document.getElementById('backToStep1');
        var backToStep2 = document.getElementById('backToStep2');
        var backToStep3 = document.getElementById('backToStep3');
        var backToStep4 = document.getElementById('backToStep4');

        var yearSelect = document.getElementById('academic_year_id');
        var termSelect = document.getElementById('term_id');
        var formSelect = document.getElementById('form_id');
        var categorySelect = document.getElementById('category_id');

        var summaryYear = document.getElementById('summaryYear');
        var summaryTerm = document.getElementById('summaryTerm');
        var summaryForm = document.getElementById('summaryForm');
        var summaryCategory = document.getElementById('summaryCategory');

        var addGuardianBtn = document.getElementById('addGuardian');
        var removeGuardian2Btn = document.getElementById('removeGuardian2');
        var removeGuardian3Btn = document.getElementById('removeGuardian3');
        var guardian2Card = document.getElementById('guardian2Card');
        var guardian3Card = document.getElementById('guardian3Card');
        var currentStepInput = document.getElementById('current_step');

        if (!step1 || !step2 || !step3 || !step4 || !step5) return;

        function setStep(step) {
            step1.style.display = step === 1 ? '' : 'none';
            step2.style.display = step === 2 ? '' : 'none';
            step3.style.display = step === 3 ? '' : 'none';
            step4.style.display = step === 4 ? '' : 'none';
            step5.style.display = step === 5 ? '' : 'none';
            if (stepIndicator) stepIndicator.textContent = 'Step ' + step + ' of 5';
            if (currentStepInput) currentStepInput.value = String(step);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(container) {
            var fields = Array.prototype.slice.call(container.querySelectorAll('input, select, textarea'));
            for (var i = 0; i < fields.length; i++) {
                var el = fields[i];
                if (el.disabled) continue;
                if (el.offsetParent === null) continue;
                if (!el.checkValidity()) {
                    el.reportValidity();
                    return false;
                }
            }
            return true;
        }

        function filterTerms() {
            if (!yearSelect || !termSelect) return;
            var yearId = yearSelect.value;
            var hasVisible = false;
            var options = Array.prototype.slice.call(termSelect.options);

            options.forEach(function (opt) {
                if (!opt.value) return;
                var optYear = opt.getAttribute('data-year-id');
                var visible = !yearId || optYear === yearId;
                opt.hidden = !visible;
                if (visible) hasVisible = true;
            });

            if (termSelect.value) {
                var selected = termSelect.options[termSelect.selectedIndex];
                if (selected && selected.hidden) termSelect.value = '';
            }

            if (!termSelect.value && hasVisible) {
                var firstVisible = options.find(function (opt) { return opt.value && !opt.hidden; });
                if (firstVisible) termSelect.value = firstVisible.value;
            }

            updateSummary();
        }

        function updateSummary() {
            var yText = yearSelect && yearSelect.selectedOptions && yearSelect.selectedOptions[0] ? yearSelect.selectedOptions[0].text : '—';
            var tText = termSelect && termSelect.selectedOptions && termSelect.selectedOptions[0] ? termSelect.selectedOptions[0].text : '—';
            var fText = formSelect && formSelect.selectedOptions && formSelect.selectedOptions[0] ? formSelect.selectedOptions[0].text : '—';
            var cText = categorySelect && categorySelect.selectedOptions && categorySelect.selectedOptions[0] ? categorySelect.selectedOptions[0].text : '—';

            if (summaryYear) summaryYear.textContent = 'Academic Year: ' + (yearSelect && yearSelect.value ? yText : '—');
            if (summaryTerm) summaryTerm.textContent = 'Term: ' + (termSelect && termSelect.value ? tText : '—');
            if (summaryForm) summaryForm.textContent = 'Form: ' + (formSelect && formSelect.value ? fText : '—');
            if (summaryCategory) summaryCategory.textContent = 'Category: ' + (categorySelect && categorySelect.value ? cText : '—');
        }

        function setGuardianCardEnabled(card, enabled) {
            if (!card) return;
            card.style.display = enabled ? '' : 'none';
            var inputs = Array.prototype.slice.call(card.querySelectorAll('input'));
            inputs.forEach(function (i) {
                i.disabled = !enabled;
                i.required = enabled;
                if (!enabled) i.value = '';
            });
        }

        function guardiansCount() {
            var count = 1;
            if (guardian2Card && guardian2Card.style.display !== 'none') count += 1;
            if (guardian3Card && guardian3Card.style.display !== 'none') count += 1;
            return count;
        }

        function syncGuardianButtons() {
            var count = guardiansCount();
            if (addGuardianBtn) addGuardianBtn.disabled = count >= 3;
        }

        if (yearSelect) yearSelect.addEventListener('change', filterTerms);
        if (termSelect) termSelect.addEventListener('change', updateSummary);
        if (formSelect) formSelect.addEventListener('change', updateSummary);
        if (categorySelect) categorySelect.addEventListener('change', updateSummary);

        if (nextToStep2) nextToStep2.addEventListener('click', function () {
            if (validateStep(step1)) setStep(2);
        });
        if (nextToStep3) nextToStep3.addEventListener('click', function () {
            if (validateStep(step2)) setStep(3);
        });
        if (nextToStep4) nextToStep4.addEventListener('click', function () {
            if (validateStep(step3)) setStep(4);
        });
        if (nextToStep5) nextToStep5.addEventListener('click', function () {
            if (validateStep(step4)) setStep(5);
        });
        if (backToStep1) backToStep1.addEventListener('click', function () { setStep(1); });
        if (backToStep2) backToStep2.addEventListener('click', function () { setStep(2); });
        if (backToStep3) backToStep3.addEventListener('click', function () { setStep(3); });
        if (backToStep4) backToStep4.addEventListener('click', function () { setStep(4); });

        if (addGuardianBtn) addGuardianBtn.addEventListener('click', function () {
            if (guardian2Card && guardian2Card.style.display === 'none') {
                setGuardianCardEnabled(guardian2Card, true);
                syncGuardianButtons();
                return;
            }
            if (guardian3Card && guardian3Card.style.display === 'none') {
                setGuardianCardEnabled(guardian3Card, true);
                syncGuardianButtons();
                return;
            }
            syncGuardianButtons();
        });

        if (removeGuardian2Btn) removeGuardian2Btn.addEventListener('click', function () {
            setGuardianCardEnabled(guardian2Card, false);
            if (guardian3Card && guardian3Card.style.display !== 'none') {
                var g3Name = document.querySelector('input[name="guardian3_name"]');
                var g3Email = document.querySelector('input[name="guardian3_email"]');
                var g3Phone = document.querySelector('input[name="guardian3_phone"]');
                var g2Name = document.querySelector('input[name="guardian2_name"]');
                var g2Email = document.querySelector('input[name="guardian2_email"]');
                var g2Phone = document.querySelector('input[name="guardian2_phone"]');

                if (g2Name) g2Name.value = g3Name ? g3Name.value : '';
                if (g2Email) g2Email.value = g3Email ? g3Email.value : '';
                if (g2Phone) g2Phone.value = g3Phone ? g3Phone.value : '';

                setGuardianCardEnabled(guardian3Card, false);
                setGuardianCardEnabled(guardian2Card, true);
            }
            syncGuardianButtons();
        });

        if (removeGuardian3Btn) removeGuardian3Btn.addEventListener('click', function () {
            setGuardianCardEnabled(guardian3Card, false);
            syncGuardianButtons();
        });

        filterTerms();
        updateSummary();

        if (guardian2Card && guardian2Card.style.display === 'none') setGuardianCardEnabled(guardian2Card, false);
        if (guardian3Card && guardian3Card.style.display === 'none') setGuardianCardEnabled(guardian3Card, false);
        syncGuardianButtons();
        setStep(initialStep);
    })();
</script>
</body>
</html>
