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
}
