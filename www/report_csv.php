<?php
include 'includes/functions.php';
include 'includes/report_lib.php';
require_role(array('admin', 'manager'));

list($from, $to) = report_range();
$rows = department_cost_report($from, $to);
audit_log('export', 'report', '', "Department cost report $from - $to");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="payroll_by_department_' . str_replace('-', '', $from) . '_' . str_replace('-', '', $to) . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, array('Department', 'Employees', 'Gross', 'Income tax', 'Social', 'Pension', 'Other deductions', 'Net'));
foreach ($rows as $r) {
    fputcsv($out, array($r['department'], $r['employees'], $r['gross'], $r['income_tax'], $r['social'], $r['pension'], $r['other_deductions'], $r['net']));
}
fclose($out);
