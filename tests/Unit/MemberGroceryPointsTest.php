<?php

namespace Tests\Unit;

use App\Support\MemberGroceryPoints;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MemberGroceryPointsTest extends TestCase
{
    #[DataProvider('amounts')]
    public function test_points_for_amount(float $amount, int $expected): void
    {
        $this->assertSame($expected, MemberGroceryPoints::pointsForAmount($amount));
    }

    /**
     * @return array<string, array{0: float, 1: int}>
     */
    public static function amounts(): array
    {
        return [
            'zero' => [0.0, 0],
            'under_200' => [199.99, 0],
            'exact_200' => [200.0, 1],
            'exact_400' => [400.0, 2],
            'between' => [450.0, 2],
            'exact_600' => [600.0, 3],
        ];
    }
}
