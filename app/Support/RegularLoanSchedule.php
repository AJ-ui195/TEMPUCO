<?php

namespace App\Support;

use App\Models\Loan;

/**
 * Declining-balance APDS schedule for a regular loan. All figures are derived
 * from principal and term; nothing is stored.
 */
final class RegularLoanSchedule
{
    public const ANNUAL_INTEREST_RATE = 0.075;

    public const MONTHLY_INTEREST_RATE = 0.00625;

    public const CREDIT_LOAN_INSURANCE_RATE = 0.01;

    public const PROCESSING_FEE_RATE = 0.01;

    public const SERVICE_FEE_RATE = 0.0125;

    public const NOTARIAL_FEE_RATE = 0.01;

    public const VERIFICATION_FEE_RATE = 0.01;

    public const OTHER_CHARGES_RATE = 0.0525;

    /**
     * @param  list<array{
     *     period: int,
     *     gross_loan: ?float,
     *     principal: ?float,
     *     interest: ?float,
     *     other_charges: ?float,
     *     net_proceeds: ?float,
     *     cash_flow: ?float,
     *     outstanding: float
     * }>  $rows
     * @param  list<array{key: string, rate: float}>  $otherChargeLines
     */
    public function __construct(
        public float $principal,
        public int $termMonths,
        public int $termYears,
        public int $installments,
        public int $gracePeriodMonths,
        public int $periods,
        public float $otherCharges,
        public float $netProceeds,
        public float $monthlyInstallment,
        public float $monthlyEir,
        public float $annualEir,
        public array $otherChargeLines,
        public array $rows,
        public float $totalPrincipal,
        public float $totalInterest,
    ) {}

    public static function fromLoan(Loan $loan): self
    {
        $principal = round((float) $loan->loan_amount, 2);
        $termMonths = max(1, (int) $loan->loan_period_months);

        return self::calculate($principal, $termMonths);
    }

    /** Empty APDS form so the layout still shows when no loan is selected. */
    public static function blank(): self
    {
        return new self(
            principal: 0,
            termMonths: 12,
            termYears: 1,
            installments: 12,
            gracePeriodMonths: 0,
            periods: 12,
            otherCharges: 0,
            netProceeds: 0,
            monthlyInstallment: 0,
            monthlyEir: 0,
            annualEir: 0,
            otherChargeLines: self::otherChargeLines(),
            rows: [[
                'period' => 0,
                'gross_loan' => null,
                'principal' => null,
                'interest' => null,
                'other_charges' => null,
                'net_proceeds' => null,
                'cash_flow' => null,
                'outstanding' => 0,
            ]],
            totalPrincipal: 0,
            totalInterest: 0,
        );
    }

    /**
     * @return list<array{key: string, rate: float}>
     */
    private static function otherChargeLines(): array
    {
        return [
            ['key' => 'credit_loan_insurance', 'rate' => self::CREDIT_LOAN_INSURANCE_RATE],
            ['key' => 'processing_fee', 'rate' => self::PROCESSING_FEE_RATE],
            ['key' => 'service_fee', 'rate' => self::SERVICE_FEE_RATE],
            ['key' => 'notarial_fee', 'rate' => self::NOTARIAL_FEE_RATE],
            ['key' => 'verification_fee', 'rate' => self::VERIFICATION_FEE_RATE],
        ];
    }

    public static function calculate(float $principal, int $termMonths): self
    {
        $principal = round($principal, 2);
        $termMonths = max(1, $termMonths);
        $monthlyRate = self::MONTHLY_INTEREST_RATE;

        $otherCharges = round($principal * self::OTHER_CHARGES_RATE, 2);
        $netProceeds = round($principal - $otherCharges, 2);
        $installment = self::monthlyInstallment($principal, $monthlyRate, $termMonths);

        $rows = [[
            'period' => 0,
            'gross_loan' => $principal,
            'principal' => null,
            'interest' => null,
            'other_charges' => $otherCharges,
            'net_proceeds' => $netProceeds,
            'cash_flow' => null,
            'outstanding' => $principal,
        ]];

        $balance = $principal;
        $totalPrincipal = 0.0;
        $totalInterest = 0.0;
        $lastCashFlow = $installment;

        for ($period = 1; $period <= $termMonths; $period++) {
            $interest = round($balance * $monthlyRate, 2);
            $isLast = $period === $termMonths;

            if ($isLast) {
                $principalPortion = round($balance, 2);
                $cashFlow = round($principalPortion + $interest, 2);
            } else {
                $principalPortion = round($installment - $interest, 2);
                if ($principalPortion > $balance) {
                    $principalPortion = round($balance, 2);
                }
                $cashFlow = $installment;
            }

            $balance = round($balance - $principalPortion, 2);
            if ($isLast || $balance < 0) {
                $balance = 0.0;
            }

            $totalPrincipal = round($totalPrincipal + $principalPortion, 2);
            $totalInterest = round($totalInterest + $interest, 2);
            $lastCashFlow = $cashFlow;

            $rows[] = [
                'period' => $period,
                'gross_loan' => null,
                'principal' => $principalPortion,
                'interest' => $interest,
                'other_charges' => null,
                'net_proceeds' => null,
                'cash_flow' => $cashFlow,
                'outstanding' => $balance,
            ];
        }

        $monthlyEir = self::monthlyEffectiveRate($netProceeds, $installment, $lastCashFlow, $termMonths);

        return new self(
            principal: $principal,
            termMonths: $termMonths,
            termYears: intdiv($termMonths, 12),
            installments: $termMonths,
            gracePeriodMonths: 0,
            periods: $termMonths,
            otherCharges: $otherCharges,
            netProceeds: $netProceeds,
            monthlyInstallment: $installment,
            monthlyEir: $monthlyEir,
            annualEir: round((pow(1 + $monthlyEir, 12) - 1), 6),
            otherChargeLines: self::otherChargeLines(),
            rows: $rows,
            totalPrincipal: $totalPrincipal,
            totalInterest: $totalInterest,
        );
    }

    public function termYearsLabel(): string
    {
        if ($this->termMonths % 12 === 0) {
            return (string) intdiv($this->termMonths, 12);
        }

        return rtrim(rtrim(number_format($this->termMonths / 12, 2, '.', ''), '0'), '.');
    }

    private static function monthlyInstallment(float $principal, float $monthlyRate, int $periods): float
    {
        if ($principal <= 0) {
            return 0.0;
        }

        if ($monthlyRate <= 0) {
            return round($principal / $periods, 2);
        }

        $factor = pow(1 + $monthlyRate, $periods);

        return round($principal * $monthlyRate * $factor / ($factor - 1), 2);
    }

    /**
     * Monthly IRR of receiving net proceeds, then paying the installment.
     */
    private static function monthlyEffectiveRate(float $netProceeds, float $installment, float $lastCashFlow, int $periods): float
    {
        if ($netProceeds <= 0 || $periods < 1) {
            return 0.0;
        }

        $rate = 0.008;

        for ($iteration = 0; $iteration < 80; $iteration++) {
            $npv = $netProceeds;
            $derivative = 0.0;

            for ($period = 1; $period <= $periods; $period++) {
                $payment = $period === $periods ? $lastCashFlow : $installment;
                $discount = pow(1 + $rate, $period);
                $npv -= $payment / $discount;
                $derivative += $period * $payment / pow(1 + $rate, $period + 1);
            }

            if (abs($npv) < 0.000001 || abs($derivative) < 1e-12) {
                break;
            }

            $next = $rate - ($npv / $derivative);
            if ($next <= 0) {
                $next = $rate / 2;
            }

            if (abs($next - $rate) < 1e-12) {
                return max(0.0, $next);
            }

            $rate = $next;
        }

        return max(0.0, $rate);
    }
}
