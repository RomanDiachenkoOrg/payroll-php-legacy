<?php
/*
 * Payroll calculation functions.
 *
 * No database access in here - these are unit tested (see tests/).
 *
 *  salaried: gross = annual / 12
 *  hourly:   gross = rate * regular + rate * 1.5 * overtime
 *            (overtime = hours above 40 in one week)
 *  income tax progressive on monthly gross:
 *            0 - 1000: 0%, 1000.01 - 4000: 15%, above 4000: 25%
 *  social 5% of gross, pension 3% of gross if opted in
 *  net = gross - tax - social - pension - other
 */

define('OVERTIME_THRESHOLD', 40);
define('OVERTIME_RATE', 1.5);

function payroll_default_brackets()
{
    return array(
        array('lower' => 0, 'upper' => 1000, 'rate' => 0),
        array('lower' => 1000, 'upper' => 4000, 'rate' => 0.15),
        array('lower' => 4000, 'upper' => null, 'rate' => 0.25),
    );
}

/* round to cents, half up */
function money_round($amount)
{
    return round((float)$amount + 0.0000001 * ($amount >= 0 ? 1 : -1), 2);
}

/* returns array(regular, overtime) for one week */
function split_week_hours($total)
{
    $total = (float)$total;
    if ($total > OVERTIME_THRESHOLD) {
        return array(OVERTIME_THRESHOLD, $total - OVERTIME_THRESHOLD);
    }
    return array($total, 0);
}

function calc_salaried_gross($annual_salary)
{
    return money_round($annual_salary / 12);
}

/* $weeks = array of weekly hour totals */
function calc_hours($weeks)
{
    $regular = 0;
    $overtime = 0;
    foreach ($weeks as $w) {
        list($r, $o) = split_week_hours($w);
        $regular += $r;
        $overtime += $o;
    }
    return array($regular, $overtime);
}

function calc_hourly_gross($rate, $weeks)
{
    list($regular, $overtime) = calc_hours($weeks);
    return money_round($rate * $regular + $rate * OVERTIME_RATE * $overtime);
}

function calc_income_tax($gross, $brackets = null)
{
    if ($brackets === null || count($brackets) == 0) {
        $brackets = payroll_default_brackets();
    }
    usort($brackets, function ($a, $b) {
        return $a['lower'] < $b['lower'] ? -1 : 1;
    });
    $tax = 0;
    foreach ($brackets as $b) {
        if ($gross <= $b['lower']) {
            break;
        }
        $top = ($b['upper'] === null || $b['upper'] === '') ? $gross : min($gross, $b['upper']);
        $tax += ($top - $b['lower']) * $b['rate'];
    }
    return money_round($tax);
}

/*
 * Full payslip. Returns assoc array with gross, income_tax, social,
 * pension, other_deductions, net
 */
function calc_payslip($gross, $pension_opt_in, $other_deductions = 0, $brackets = null, $social_rate = 0.05, $pension_rate = 0.03)
{
    $gross = money_round($gross);
    $tax = calc_income_tax($gross, $brackets);
    $social = money_round($gross * $social_rate);
    $pension = $pension_opt_in ? money_round($gross * $pension_rate) : 0.00;
    $other = money_round($other_deductions);
    $net = money_round($gross - $tax - $social - $pension - $other);

    return array(
        'gross' => $gross,
        'income_tax' => $tax,
        'social' => $social,
        'pension' => $pension,
        'other_deductions' => $other,
        'net' => $net,
    );
}

function format_money($amount)
{
    if ($amount === null || $amount === '') {
        return '-';
    }
    $neg = $amount < 0;
    return ($neg ? '-' : '') . '$' . number_format(abs($amount), 2);
}
