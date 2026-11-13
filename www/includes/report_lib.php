<?php
/* payroll cost by department, for approved and paid runs starting in the range */
function department_cost_report($from, $to)
{
    return db_fetch_all("SELECT COALESCE(NULLIF(p.department_name, ''), 'Unassigned') AS department,
                                COUNT(DISTINCT p.employee_id) AS employees,
                                SUM(p.gross) AS gross, SUM(p.income_tax) AS income_tax, SUM(p.social) AS social,
                                SUM(p.pension) AS pension, SUM(p.other_deductions) AS other_deductions, SUM(p.net) AS net
                           FROM payslips p JOIN payruns r ON r.id = p.payrun_id
                          WHERE r.status IN ('approved', 'paid')
                            AND r.period_start BETWEEN '" . esc($from) . "' AND '" . esc($to) . "'
                          GROUP BY department ORDER BY department");
}

function report_range()
{
    $from = get('from', date('Y-01-01'));
    $to = get('to', date('Y-m-d'));
    if (!valid_date($from)) $from = date('Y-01-01');
    if (!valid_date($to)) $to = date('Y-m-d');
    return array($from, $to);
}
