@php
    use App\Enums\LoanPurpose;
    use App\Enums\ModeOfPayment;

    $chosen = fn (bool $active): string => $active ? 'chosen' : '';
    $a = $applicant;
@endphp
<article class="page quick-page">
    <header class="letterhead">
        <img class="letterhead-logo" src="{{ asset('images/DICNHSLOGO1.png') }}" alt="{{ __('DICNHS TEMPUCO logo') }}">
        <div class="letterhead-text">
            <p>Republic of the Philippines</p>
            <p>Province of Davao Del Sur</p>
            <p class="org-name">Digos City National High School Teachers And Employees Multi-Purpose Cooperative</p>
            <p class="org-short">DICNHS TEMPUCO</p>
            <p>CDA Reg. No.9520 - 11013080</p>
            <p>Rizal Ave., Zone II, Digos City</p>
        </div>
        <div class="letterhead-spacer" aria-hidden="true"></div>
    </header>

    <h1 class="form-title">QUICK LOAN APPLICATION FORM</h1>

    <section class="app-section">
        <h2 class="app-h">I. PERSONAL INFORMATION</h2>
        <div class="field-row">
            <span class="field-label">Full Name:</span>
            <span class="field-line">{{ $a['fullName'] ?? '' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Date of Birth:</span>
            <span class="field-line mid">{{ $a['dateOfBirth'] ?? '' }}</span>
            <span class="field-label">Age:</span>
            <span class="field-line short">{{ $a['age'] ?? '' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Sex:</span>
            <span class="{{ $chosen(($a['sex'] ?? '') === 'male') }}">Male</span>
            /
            <span class="{{ $chosen(($a['sex'] ?? '') === 'female') }}">Female</span>
        </div>
        <div class="field-row">
            <span class="field-label">Civil Status:</span>
            <span class="{{ $chosen(($a['civilStatus'] ?? '') === 'single') }}">Single</span>
            /
            <span class="{{ $chosen(($a['civilStatus'] ?? '') === 'married') }}">Married</span>
            /
            <span class="{{ $chosen(($a['civilStatus'] ?? '') === 'widowed') }}">Widowed</span>
            /
            <span class="{{ $chosen(($a['civilStatus'] ?? '') === 'separated') }}">Separated</span>
        </div>
        <div class="field-row">
            <span class="field-label">Address:</span>
            <span class="field-line">{{ $a['address'] ?? '' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Contact Number:</span>
            <span class="field-line mid">{{ $a['contactNumber'] ?? '' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Email:</span>
            <span class="field-line mid">{{ $a['email'] ?? '' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Occupation/Position:</span>
            <span class="field-line">{{ $a['occupation'] ?? '' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Employer/Department:</span>
            <span class="field-line">{{ $a['employer'] ?? '' }}</span>
        </div>
    </section>

    <section class="app-section">
        <h2 class="app-h">II. LOAN DETAILS</h2>
        <div class="field-row">
            <span class="field-label">Type of Loan:</span>
            <span class="bold">QUICK LOAN</span>
        </div>
        <div class="field-row">
            <span class="field-label">Amount to Borrow (Maximum ₱2,000):</span>
            <span>₱</span>
            <span class="field-line short">{{ $a['loanAmount'] ?? '' }}</span>
        </div>
        <div class="field-row">
            <span class="field-label">Term:</span>
            <span>1 Month to Pay</span>
        </div>
        <div class="field-row">
            <span class="field-label">Interest Rate:</span>
            <span>1% only</span>
        </div>
        <p class="breakdown-h">Breakdown:</p>
        <div class="breakdown">
            <div class="field-row">
                <span class="field-label">Loan Amount:</span>
                <span>₱</span>
                <span class="field-line short">{{ $a['loanAmount'] ?? '' }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">Interest (1%):</span>
                <span>₱</span>
                <span class="field-line short">{{ $a['interest'] ?? '' }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">Total Amount to be Paid:</span>
                <span>₱</span>
                <span class="field-line short">{{ $a['totalPayable'] ?? '' }}</span>
            </div>
        </div>
        <div class="field-row">
            <span class="field-label">Due Date:</span>
            <span class="field-line mid">{{ $a['dueDate'] ?? '' }}</span>
        </div>
    </section>
</article>

<article class="page quick-page">
    <section class="app-section">
        <h2 class="app-h">III. PURPOSE OF LOAN</h2>
        <p class="app-body">
            <span class="{{ $chosen($purpose === LoanPurpose::Personal) }}">Personal</span>
            /
            <span class="{{ $chosen($purpose === LoanPurpose::Emergency) }}">Emergency</span>
            /
            <span class="{{ $chosen($purpose === LoanPurpose::SchoolRelated) }}">School-related</span>
            /
            <span class="{{ $chosen($purpose === LoanPurpose::Medical) }}">Medical</span>
            /
            <span class="{{ $chosen($purpose === LoanPurpose::Others) }}">Others</span>:
            <span class="u-short">{{ $purpose === LoanPurpose::Others ? $purposeOther : '' }}</span>
        </p>
    </section>

    <section class="app-section">
        <h2 class="app-h">IV. MODE OF PAYMENT</h2>
        <p class="app-body pad">
            •
            <span class="{{ $chosen($modeOfPayment === ModeOfPayment::CashPayment) }}">Cash Payment</span>
            /
            <span class="{{ $chosen($modeOfPayment === ModeOfPayment::OverTheCounter) }}">Over the counter</span>
        </p>
    </section>

    <section class="app-section">
        <h2 class="app-h">V. MEMBER'S DECLARATION</h2>
        <p class="app-body">
            I hereby certify that the above information is true and correct. I agree to pay the loan based on the terms and conditions stated.
        </p>
        <div class="office-row tight">
            <div class="office-col">
                <span class="office-label">Signature of Applicant:</span>
                <span class="office-line">{{ $borrowerName }}</span>
            </div>
            <div class="office-col">
                <span class="office-label">Date:</span>
                <span class="office-line">{{ optional($loan->applicant_signed_at ?? $loan->loan_date)->format('F j, Y') }}</span>
            </div>
        </div>
    </section>

    <section class="app-section">
        <h2 class="app-h">TERMS &amp; CONDITIONS:</h2>
        <ol class="terms">
            <li>The Quick Loan is payable within 1 month from date of release.</li>
            <li>1% interest applies to the total loan amount.</li>
            <li>Delay in payment may result in penalties as per Coop policy.</li>
            <li>The Coop may deduct outstanding obligations from any benefits if needed.</li>
        </ol>
    </section>

    <section class="office">
        <div class="office-row">
            <div class="office-col">
                <span class="office-label">Received by:</span>
                <span class="office-line"></span>
                <span class="office-sub">(Signature over printed Name)</span>
            </div>
            <div class="office-col">
                <span class="office-label">Date Received:</span>
                <span class="office-line">{{ optional($loan->loan_date)->format('F j, Y') }}</span>
            </div>
        </div>

        <div class="office-row">
            <div class="office-col">
                <span class="office-label">Reviewed by:</span>
                <span class="officer-name">LORNA N. BRUTAS</span>
                <span class="officer-title">Bookkeeper</span>
            </div>
            <div class="office-col">
                <span class="office-label">Noted by:</span>
                <span class="office-line"></span>
                <span class="office-sub">Credit Committee</span>
            </div>
        </div>

        <div class="office-row">
            <div class="office-col">
                <span class="office-label">Approved by:</span>
                <span class="officer-name">JESSAMAE B. DEL ROSARIO</span>
                <span class="officer-title">General Manager</span>
            </div>
            <div class="office-col"></div>
        </div>

        <div class="office-row">
            <div class="office-col">
                <span class="office-label">Date Approved:</span>
                <span class="office-line">{{ optional($loan->approved_at)->format('F j, Y') }}</span>
            </div>
            <div class="office-col"></div>
        </div>

        <div class="office-row">
            <div class="office-col" style="flex: 1 1 100%;">
                <span class="office-label">Remarks:</span>
                <span class="remarks-line">{{ $loan->committeeDecision?->conditions_notes }}</span>
            </div>
        </div>
    </section>
</article>
