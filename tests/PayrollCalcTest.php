<?php
use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../www/includes/payroll_calc.php';

class PayrollCalcTest extends TestCase
{
    public function testSalariedGrossIsAnnualOverTwelve()
    {
        $this->assertEquals(5000.00, calc_salaried_gross(60000));
        $this->assertEquals(4166.67, calc_salaried_gross(50000));
    }

    public function testWeekWithoutOvertime()
    {
        $this->assertEquals(array(40, 0), split_week_hours(40));
        $this->assertEquals(array(32.5, 0), split_week_hours(32.5));
    }

    public function testWeekWithOvertime()
    {
        $this->assertEquals(array(40, 6), split_week_hours(46));
    }

    public function testHourlyGrossCountsOvertimePerWeek()
    {
        // 44h -> 40 + 4 OT, 30h -> 30 regular
        $this->assertEquals(20 * 70 + 20 * 1.5 * 4, calc_hourly_gross(20, array(44, 30)));
    }

    public function testIncomeTaxBrackets()
    {
        $this->assertEquals(0.00, calc_income_tax(900));
        $this->assertEquals(0.00, calc_income_tax(1000));
        $this->assertEquals(300.00, calc_income_tax(3000));
        $this->assertEquals(700.00, calc_income_tax(5000));
    }

    public function testFullPayslip()
    {
        $p = calc_payslip(5000, true, 120);
        $this->assertEquals(700.00, $p['income_tax']);
        $this->assertEquals(250.00, $p['social']);
        $this->assertEquals(150.00, $p['pension']);
        $this->assertEquals(120.00, $p['other_deductions']);
        $this->assertEquals(3780.00, $p['net']);
    }

    public function testNoPensionWhenNotOptedIn()
    {
        $p = calc_payslip(2000, false);
        $this->assertEquals(0.00, $p['pension']);
        $this->assertEquals(1750.00, $p['net']);
    }

    public function testRoundingHalfUp()
    {
        $this->assertEquals(2.35, money_round(2.345));
        $this->assertEquals(1.01, money_round(1.005));
    }

    public function testFormatMoney()
    {
        $this->assertEquals('$1,234.50', format_money(1234.5));
        $this->assertEquals('-$10.00', format_money(-10));
    }
}
