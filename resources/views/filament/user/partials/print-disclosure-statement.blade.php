@php
    use App\Enums\LoanPurpose;
    use App\Enums\ModeOfPayment;

    $box = fn (bool $checked): string => $checked ? '✓' : '';
    $d = $disclosure;
@endphp
<article class="page disclosure-page">
    <header class="center">
        <p class="creditor">DICNHS TEMPUCO</p>
        <p class="creditor-note">(Business Name of Creditor)</p>
        <h1 class="doc-title">Disclosure Statement on Loan/Credit Transaction</h1>
        <p class="doc-sub">(As Required under R.A. 3765, Truth in Lending Act)</p>
    </header>

    <div class="fill-row">
        <span class="fill-label">NAME OF BORROWER</span>
        <span class="fill-line">{{ $borrowerName }}</span>
    </div>
    <div class="fill-row">
        <span class="fill-label">ADDRESS</span>
        <span class="fill-line">{{ $borrowerAddress }}</span>
    </div>

    <table class="charges">
        <thead>
            <tr>
                <th class="desc"></th>
                <th class="col">Not Deducted From<br>Proceeds of Loan</th>
                <th class="col">Deducted From<br>Proceeds of Loan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="item" colspan="2">1. LOAN GRANTED (Amount to be financed)</td>
                <td class="amt">P {{ $d['loanGranted'] }}</td>
            </tr>
            <tr>
                <td class="item" colspan="3">2. FINANCE CHARGES</td>
            </tr>
            <tr>
                <td class="sub">
                    a. Interest
                    <span class="inline-u">{{ $d['interestRate'] }}</span> % p.a. from
                    <span class="inline-u date">{{ $d['interestFrom'] }}</span> to
                    <span class="inline-u date">{{ $d['interestTo'] }}</span>
                    <div class="checks">
                        ( <span class="box">{{ $box($d['interestSimple']) }}</span> ) Simple
                        &nbsp;&nbsp;
                        ( <span class="box">{{ $box($d['interestMonthly']) }}</span> ) Monthly
                        &nbsp;&nbsp;
                        ( <span class="box">{{ $box($d['interestSemiAnnual']) }}</span> ) Semi-annual
                    </div>
                </td>
                <td class="amt">{{ $d['interestNotDeducted'] }}</td>
                <td class="amt">{{ $d['interestDeducted'] }}</td>
            </tr>
            <tr>
                <td class="sub">b. Non-Interest Charges <span class="leaders"></span></td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="sub">c. Commitment fee <span class="leaders"></span></td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="sub">d. Guarantee fee <span class="leaders"></span></td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="sub">e. Other charges incidental to the extension of credit (Specify) <span class="leaders"></span></td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="sub-deep">Total Finance Charges</td>
                <td class="amt">{{ $d['totalFinanceNotDeducted'] }}</td>
                <td class="amt">{{ $d['totalFinanceDeducted'] }}</td>
            </tr>
            <tr>
                <td class="item" colspan="3">3. NON-FINANCE CHARGES</td>
            </tr>
            <tr>
                <td class="sub">a. CAPITAL BUILD-UP <span class="leaders"></span></td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="sub">b. Insurance Premium <span class="leaders"></span></td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="sub">c. Service Charge 5.25% (School fee &amp; Processing fee for APDS) <span class="leaders"></span></td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="sub">d. Others (Specify) BALANCE of PREVIOUS LOAN</td>
                <td class="amt empty"></td>
                <td class="amt">{{ $d['previousLoanBalance'] }}</td>
            </tr>
            <tr>
                <td class="sub">e. SURCHARGE <span class="leaders"></span></td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="sub">
                    f. Emergency ( <span class="box">{{ $box(false) }}</span> ) / Short Term ( <span class="box">{{ $box(false) }}</span> ) loan due
                </td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="sub">g. Others: <span class="leaders"></span></td>
                <td class="amt empty"></td>
                <td class="amt empty"></td>
            </tr>
            <tr>
                <td class="summary-label">4. TOTAL DEDUCTIONS FROM PROCEEDS OF LOAN</td>
                <td></td>
                <td class="summary-amt">{{ $d['totalDeductions'] }}</td>
            </tr>
            <tr>
                <td class="summary-label">5. NET PROCEEDS OF LOAN (A less D)</td>
                <td></td>
                <td class="summary-amt">{{ $d['netProceeds'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="pct-row">
        <span class="bold">6. PERCENTAGE OF FINANCE CHARGES TO TOTAL AMOUNT FINANCED</span>
        <span class="fill-line">{{ $d['financeChargePercent'] }}</span>
        <span>%</span>
    </div>
    <div class="pct-row">
        <span class="bold">7. EFFECTIVE INTEREST RATE PER MONTH</span>
        <span class="fill-line">{{ $d['effectiveMonthlyRate'] }}</span>
        <span>%</span>
    </div>

    <p class="bold mt">8. SCHEDULE OF PAYMENT</p>
    <div class="sched">
        <div class="sched-line">
            <span>a. Single payment due one (Date)</span>
            <span class="fill-line">{{ $d['singlePaymentDate'] }}</span>
        </div>
        <div class="sched-line" style="flex-wrap: wrap;">
            <span>b. Total installment payments Payable in</span>
            <span class="inline-u">{{ $d['installmentCount'] }}</span>
            <span>monthly/year at P</span>
            <span class="inline-u date">{{ $d['installmentAmount'] }}</span>
            <span class="fill-line" style="min-width: 5rem;">{{ $d['totalInstallments'] }}</span>
        </div>
    </div>

    <div class="collateral">
        <p class="bold" style="margin: 0;">9. COLLATERAL</p>
        <p class="small" style="margin: 0.15rem 0 0;">This loan is wholly/partly secured by (please check)</p>
        <div class="collateral-opts">
            ( <span class="box">{{ $box(false) }}</span> ) real estate
            &nbsp;&nbsp;
            ( <span class="box">{{ $box(false) }}</span> ) chattel
            &nbsp;&nbsp;
            ( <span class="box">{{ $box(false) }}</span> ) government securities
            &nbsp;&nbsp;
            ( <span class="box">{{ $box($d['unsecured'] ?? false) }}</span> ) UNSECURED
        </div>
    </div>

    <p class="bold mt">10. ADDITIONAL CHARGES IN CASE CERTAIN STIPULATIONS ARE NOT MET BY THE BORROWER</p>
    <table class="extra-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr><td></td><td></td></tr>
            <tr><td></td><td></td></tr>
        </tbody>
    </table>

    <div class="sig-pair">
        <div>
            <div class="sig-line"></div>
            <div class="sig-caption">Name &amp; Signature SAIC MEMBER</div>
        </div>
        <div>
            <div class="sig-line"></div>
            <div class="sig-caption">Position</div>
        </div>
    </div>

    <p class="ack">
        I ACKNOWLEDGE RECEIPT OF A COPY OF THIS STATEMENT PRIOR TO THE CONSUMMATION OF THE CREDIT TRANSACTION AND THAT I UNDERSTAND AND FULLY AGREE TO THE TERMS AND CONDITIONS THEREOF.
    </p>

    <div class="borrower-sig">
        <div class="date">
            <div class="sig-line">{{ optional($loan->applicant_signed_at)->format('n/j/y') }}</div>
            <div class="sig-caption">Date</div>
        </div>
        <div class="sign">
            <div class="sig-line center bold">{{ $borrowerName }}</div>
            <div class="sig-caption">Signature of Borrower Over Printed Name</div>
        </div>
    </div>
</article>
