<?php
include 'includes/functions.php';
require_login();

$id = (int)get('id');
$p = db_fetch_one('SELECT p.*, r.period_start, r.period_end, r.pay_date, r.status AS run_status, e.first_name, e.last_name, e.emp_no
                     FROM payslips p JOIN payruns r ON r.id = p.payrun_id JOIN employees e ON e.id = p.employee_id
                    WHERE p.id = ' . $id);
if (!$p || !(is_admin() || ($p['employee_id'] == my_employee_id() && in_array($p['run_status'], array('approved', 'paid'))))) {
    header('HTTP/1.1 404 Not Found');
    die('Payslip not found');
}

audit_log('export', 'payslip', $id, 'Exported payslip CSV');

$filename = 'payslip_' . $p['emp_no'] . '_' . date('Y_m', strtotime($p['period_start'])) . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, array('Field', 'Value'));
fputcsv($out, array('Employee', $p['first_name'] . ' ' . $p['last_name']));
fputcsv($out, array('Employee no.', $p['emp_no']));
fputcsv($out, array('Department', $p['department_name']));
fputcsv($out, array('Period', $p['period_start'] . ' - ' . $p['period_end']));
fputcsv($out, array('Pay date', $p['pay_date']));
if ($p['pay_type'] == 'hourly') {
    fputcsv($out, array('Hourly rate', $p['hourly_rate']));
    fputcsv($out, array('Regular hours', $p['regular_hours']));
    fputcsv($out, array('Overtime hours', $p['overtime_hours']));
}
fputcsv($out, array('Gross pay', $p['gross']));
fputcsv($out, array('Income tax', $p['income_tax']));
fputcsv($out, array('Social contribution', $p['social']));
fputcsv($out, array('Pension', $p['pension']));
fputcsv($out, array('Other deductions', $p['other_deductions']));
fputcsv($out, array('Net pay', $p['net']));
fclose($out);
