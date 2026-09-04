<?php

namespace App\Support;

use App\Enums\LoanPurpose;
use App\Enums\ModeOfPayment;
use App\Models\Loan;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class PrintMemberLoan
{
    public static function printUrl(Loan $loan, bool $autoPrint = false): string
    {
        return route('members.loans.print', [
            'loan' => $loan,
            'auto' => $autoPrint ? 1 : 0,
        ]);
    }

    /**
     * @return array{
     *     loan: Loan,
     *     autoPrint: bool,
     *     documentTitle: string,
     *     borrowerName: string,
     *     borrowerAddress: string,
     *     isQuickLoan: bool,
     *     purpose: ?LoanPurpose,
     *     purposeOther: ?string,
     *     modeOfPayment: ?ModeOfPayment,
     *     applicant: array<string, mixed>,
     *     disclosure: array<string, mixed>
     * }
     */
    public static function viewData(Loan $loan, bool $autoPrint = false): array
    {
        $loan->loadMissing(['user', 'certification', 'committeeDecision', 'payments']);

        $member = $loan->user;
        $certification = $loan->certification;
        $purpose = self::resolvePurpose($loan);
        $isQuickLoan = self::isQuickLoan($loan);
        $borrowerName = filled($certification?->borrower_name)
            ? (string) $certification->borrower_name
            : (string) ($member?->name ?? '');

        return [
            'loan' => $loan,
            'autoPrint' => $autoPrint,
            'documentTitle' => $isQuickLoan
                ? 'Quick Loan Application Form'
                : 'Disclosure Statement on Loan/Credit Transaction',
            'borrowerName' => $borrowerName,
            'borrowerAddress' => filled($certification?->home_address)
                ? (string) $certification->home_address
                : (string) ($member?->address ?? ''),
            'isQuickLoan' => $isQuickLoan,
            'purpose' => $purpose['purpose'],
            'purposeOther' => $purpose['other'],
            'modeOfPayment' => $loan->mode_of_payment instanceof ModeOfPayment
                ? $loan->mode_of_payment
                : self::parseModeOfPayment($loan),
            'applicant' => $isQuickLoan ? self::quickLoanApplicant($loan, $borrowerName) : [],
            'disclosure' => $isQuickLoan ? [] : self::disclosureData($loan),
        ];
    }

    public static function isQuickLoan(Loan $loan): bool
    {
        return strcasecmp(trim((string) $loan->loan_type), 'QUICK LOAN') === 0;
    }

    /**
     * @return array{purpose: ?LoanPurpose, other: ?string}
     */
    private static function resolvePurpose(Loan $loan): array
    {
        if ($loan->purpose_of_loan instanceof LoanPurpose) {
            return [
                'purpose' => $loan->purpose_of_loan,
                'other' => filled($loan->purpose_of_loan_other) ? (string) $loan->purpose_of_loan_other : null,
            ];
        }

        $notes = (string) $loan->application_notes;
        if (preg_match('/^Purpose of loan:\s*(.+)$/mi', $notes, $matches) !== 1) {
            return ['purpose' => null, 'other' => null];
        }

        $text = trim($matches[1]);

        foreach (LoanPurpose::cases() as $case) {
            $label = $case->getLabel();

            if (strcasecmp($text, $label) === 0) {
                return ['purpose' => $case, 'other' => null];
            }

            if ($case === LoanPurpose::Others && preg_match('/^'.preg_quote($label, '/').'\s*:?\s*(.*)$/i', $text, $otherMatch) === 1) {
                $other = trim((string) ($otherMatch[1] ?? ''));

                return [
                    'purpose' => $case,
                    'other' => $other !== '' ? $other : null,
                ];
            }
        }

        return ['purpose' => LoanPurpose::Others, 'other' => $text !== '' ? $text : null];
    }

    private static function parseModeOfPayment(Loan $loan): ?ModeOfPayment
    {
        $notes = (string) $loan->application_notes;
        if (preg_match('/^Mode of payment:\s*(.+)$/mi', $notes, $matches) !== 1) {
            return null;
        }

        $text = trim($matches[1]);

        foreach (ModeOfPayment::cases() as $case) {
            if (strcasecmp($text, $case->getLabel()) === 0) {
                return $case;
            }
        }

        return ModeOfPayment::tryFrom(strtolower(str_replace(' ', '_', $text)));
    }

    /**
     * @return array<string, mixed>
     */
    private static function quickLoanApplicant(Loan $loan, string $borrowerName): array
    {
        $notes = (string) $loan->application_notes;
        $member = $loan->user;
        $loanAmount = (float) $loan->loan_amount;
        $installment = (float) $loan->installment_amount;
        $interest = round($loanAmount * 0.01, 2);

        if ($installment > $loanAmount) {
            $interest = round($installment - $loanAmount, 2);
            $totalPayable = $installment;
        } else {
            $totalPayable = round($loanAmount + $interest, 2);
        }

        $dateOfBirth = self::parseNoteLine($notes, 'Date of birth');
        $sex = strtolower((string) (self::parseNoteLine($notes, 'Sex') ?? ''));
        $civilStatus = strtolower((string) (self::parseNoteLine($notes, 'Civil status') ?? ''));

        return [
            'fullName' => $borrowerName,
            'dateOfBirth' => self::prettyDate($dateOfBirth),
            'age' => self::parseNoteLine($notes, 'Age') ?? '',
            'sex' => $sex,
            'civilStatus' => $civilStatus,
            'address' => self::parseNoteLine($notes, 'Address')
                ?: (string) ($member?->address ?? ''),
            'contactNumber' => self::parseNoteLine($notes, 'Contact number')
                ?: (string) ($member?->cellphone ?? ''),
            'email' => self::parseNoteLine($notes, 'Email')
                ?: (string) ($member?->email ?? ''),
            'occupation' => self::parseNoteLine($notes, 'Occupation / Position')
                ?? self::parseNoteLine($notes, 'Occupation/Position')
                ?? '',
            'employer' => self::parseNoteLine($notes, 'Employer / Department')
                ?? self::parseNoteLine($notes, 'Employer/Department')
                ?? '',
            'loanAmount' => self::money($loanAmount),
            'interest' => self::money($interest),
            'totalPayable' => self::money($totalPayable),
            'dueDate' => $loan->first_payment_due_date instanceof CarbonInterface
                ? $loan->first_payment_due_date->format('F j, Y')
                : '',
        ];
    }

    private static function parseNoteLine(string $notes, string $label): ?string
    {
        if (preg_match('/^'.preg_quote($label, '/').':\s*(.*)$/mi', $notes, $matches) !== 1) {
            return null;
        }

        $value = trim((string) $matches[1]);

        if ($value === '' || strcasecmp($value, 'N/A') === 0) {
            return null;
        }

        return $value;
    }

    private static function prettyDate(mixed $value): string
    {
        if (! filled($value)) {
            return '';
        }

        try {
            return Carbon::parse((string) $value)->format('F j, Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function disclosureData(Loan $loan): array
    {
        $loanAmount = (float) $loan->loan_amount;
        $installment = (float) $loan->installment_amount;
        $months = max(1, (int) $loan->loan_period_months);
        $isQuickLoan = self::isQuickLoan($loan);

        $interestAmount = self::interestAmount($loanAmount, $installment, $months, $isQuickLoan);
        $interestRate = self::interestRatePercent($loanAmount, $interestAmount, $months, $isQuickLoan);

        $previousLoanBalance = (float) ($loan->certification?->standing_loan ?? 0);
        $previousLoanBalance = $previousLoanBalance > 0 ? $previousLoanBalance : null;

        $interestNotDeducted = $interestAmount > 0 ? $interestAmount : null;
        $interestDeducted = null;

        $totalFinanceNotDeducted = $interestNotDeducted;
        $totalFinanceDeducted = $interestDeducted;
        $totalDeductions = $previousLoanBalance ?? 0.0;
        $netProceeds = $loanAmount - $totalDeductions;

        $financeChargePercent = $loanAmount > 0 && $interestAmount > 0
            ? round(($interestAmount / $loanAmount) * 100, 2)
            : null;

        $effectiveMonthlyRate = $months > 0 && $loanAmount > 0 && $interestAmount > 0
            ? round(($interestAmount / $loanAmount / $months) * 100, 2)
            : ($isQuickLoan ? 1.0 : null);

        $startDate = $loan->approved_at ?? $loan->loan_date;
        $endDate = $loan->first_payment_due_date
            ?? (filled($startDate) ? $startDate->copy()->addMonths($months) : null);

        $isSinglePayment = $months === 1;

        return [
            'loanGranted' => self::money($loanAmount),
            'interestRate' => $interestRate !== null ? self::number($interestRate) : '',
            'interestFrom' => self::slashDate($startDate),
            'interestTo' => self::slashDate($endDate),
            'interestMonthly' => true,
            'interestSimple' => false,
            'interestSemiAnnual' => false,
            'interestNotDeducted' => self::money($interestNotDeducted),
            'interestDeducted' => self::money($interestDeducted),
            'previousLoanBalance' => self::money($previousLoanBalance),
            'totalFinanceNotDeducted' => self::money($totalFinanceNotDeducted),
            'totalFinanceDeducted' => self::money($totalFinanceDeducted),
            'totalDeductions' => $totalDeductions > 0 ? self::money($totalDeductions) : '',
            'netProceeds' => self::money($netProceeds),
            'financeChargePercent' => $financeChargePercent !== null ? self::number($financeChargePercent) : '',
            'effectiveMonthlyRate' => $effectiveMonthlyRate !== null ? self::number($effectiveMonthlyRate) : '',
            'singlePaymentDate' => $isSinglePayment ? self::slashDate($endDate) : '',
            'installmentCount' => $isSinglePayment ? '' : (string) $months,
            'installmentAmount' => $isSinglePayment ? '' : self::money($installment),
            'totalInstallments' => $isSinglePayment ? '' : self::money($installment * $months),
            'unsecured' => $isQuickLoan,
        ];
    }

    private static function interestAmount(
        float $loanAmount,
        float $installment,
        int $months,
        bool $isQuickLoan,
    ): float {
        if ($isQuickLoan) {
            $difference = round($installment - $loanAmount, 2);

            return $difference > 0 ? $difference : round($loanAmount * 0.01, 2);
        }

        $totalPayable = round($installment * $months, 2);
        $difference = round($totalPayable - $loanAmount, 2);

        return $difference > 0 ? $difference : 0.0;
    }

    private static function interestRatePercent(
        float $loanAmount,
        float $interestAmount,
        int $months,
        bool $isQuickLoan,
    ): ?float {
        if ($isQuickLoan) {
            return 1.0;
        }

        if ($loanAmount <= 0 || $interestAmount <= 0 || $months <= 0) {
            return null;
        }

        return round(($interestAmount / $loanAmount / $months) * 100, 2);
    }

    private static function money(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        return number_format((float) $amount, 2);
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private static function slashDate(mixed $date): string
    {
        if (! $date instanceof CarbonInterface) {
            return '';
        }

        return $date->format('n/j/y');
    }
}
