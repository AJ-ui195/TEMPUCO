<?php

namespace Tests\Unit;

use App\Support\RegularLoanPaymentAllocation;
use App\Support\RegularLoanSchedule;
use Tests\TestCase;

class RegularLoanPaymentAllocationTest extends TestCase
{
    public function test_on_time_installment_pays_period_one_in_full(): void
    {
        $schedule = RegularLoanSchedule::calculate(300000, 60, false);
        $allocated = RegularLoanPaymentAllocation::allocate($schedule, $schedule->monthlyInstallment);

        $this->assertSame(RegularLoanPaymentAllocation::STATUS_PAID, $allocated['periods'][1]['interest_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_PAID, $allocated['periods'][1]['principal_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_PAID, $allocated['periods'][1]['cash_flow_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_UNPAID, $allocated['periods'][2]['interest_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_UNPAID, $allocated['periods'][2]['principal_status']);
    }

    public function test_nine_thousand_covers_three_months_interest_then_oldest_principal(): void
    {
        $schedule = RegularLoanSchedule::calculate(300000, 60, false);
        $allocated = RegularLoanPaymentAllocation::allocate($schedule, 9000.0, 3);

        $this->assertSame(RegularLoanPaymentAllocation::STATUS_PAID, $allocated['periods'][1]['interest_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_PAID, $allocated['periods'][2]['interest_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_PAID, $allocated['periods'][3]['interest_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_UNPAID, $allocated['periods'][4]['interest_status']);

        $this->assertSame(RegularLoanPaymentAllocation::STATUS_PARTIAL, $allocated['periods'][1]['principal_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_UNPAID, $allocated['periods'][2]['principal_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_UNPAID, $allocated['periods'][3]['principal_status']);

        $interest = round(
            $allocated['periods'][1]['interest_due']
            + $allocated['periods'][2]['interest_due']
            + $allocated['periods'][3]['interest_due'],
            2
        );
        $leftover = round(9000 - $interest, 2);

        $this->assertEqualsWithDelta($leftover, $allocated['periods'][1]['principal_allocated'], 0.01);
        $this->assertEqualsWithDelta(
            round($allocated['periods'][1]['principal_due'] - $leftover, 2),
            $allocated['periods'][1]['principal_remaining'],
            0.01
        );
    }

    public function test_extra_above_one_installment_prepay_principal_not_next_interest(): void
    {
        $schedule = RegularLoanSchedule::calculate(300000, 60, false);
        $cash = round($schedule->monthlyInstallment + 2000, 2);
        $allocated = RegularLoanPaymentAllocation::allocate($schedule, $cash, 1);

        $this->assertSame(RegularLoanPaymentAllocation::STATUS_PAID, $allocated['periods'][1]['interest_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_PAID, $allocated['periods'][1]['principal_status']);
        $this->assertSame(RegularLoanPaymentAllocation::STATUS_UNPAID, $allocated['periods'][2]['interest_status']);
        $this->assertEqualsWithDelta(2000.0, $allocated['periods'][2]['principal_allocated'], 0.01);
    }

    public function test_advance_recast_lowers_interest_and_can_go_negative_at_term(): void
    {
        $base = RegularLoanSchedule::calculate(300000, 60, false);
        $recast = $base->recastAfterAdvance(2000);

        $basePeriodOne = collect($base->rows)->firstWhere('period', 1);
        $recastPeriodOne = collect($recast->rows)->firstWhere('period', 1);
        $last = collect($recast->rows)->firstWhere('period', 60);

        $this->assertNotNull($basePeriodOne);
        $this->assertNotNull($recastPeriodOne);
        $this->assertNotNull($last);
        $this->assertEqualsWithDelta(round(298000 * $base->monthlyInterestRate, 2), $recastPeriodOne['interest'], 0.01);
        $this->assertTrue($recastPeriodOne['interest'] < $basePeriodOne['interest']);
        $this->assertTrue($last['outstanding'] < 0);
    }

    public function test_remaining_rows_recast_from_current_outstanding_minus_installment(): void
    {
        $base = RegularLoanSchedule::calculate(300000, 60, false);
        $recast = $base->recastFromPeriod(2, 287811.74);
        $periodTwo = collect($recast->rows)->firstWhere('period', 2);
        $periodOne = collect($recast->rows)->firstWhere('period', 1);
        $basePeriodOne = collect($base->rows)->firstWhere('period', 1);

        $this->assertNotNull($periodTwo);
        $this->assertNotNull($periodOne);
        $this->assertNotNull($basePeriodOne);
        $this->assertEqualsWithDelta((float) $basePeriodOne['principal'], (float) $periodOne['principal'], 0.01);
        $this->assertEqualsWithDelta(287811.74, (float) $periodOne['outstanding'], 0.01);
        $this->assertEqualsWithDelta(281800.35, (float) $periodTwo['outstanding'], 0.01);
        $this->assertEqualsWithDelta(round(287811.74 * $base->monthlyInterestRate, 2), (float) $periodTwo['interest'], 0.01);
    }
}
