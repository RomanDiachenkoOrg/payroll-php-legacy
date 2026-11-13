<?php
include 'includes/functions.php';
require_login();

$id = (int)get('id');
$p = db_fetch_one('SELECT p.*, r.period_start, r.period_end, r.pay_date, r.status AS run_status,
                          e.first_name, e.last_name, e.emp_no, e.job_title
                     FROM payslips p JOIN payruns r ON r.id = p.payrun_id JOIN employees e ON e.id = p.employee_id
                    WHERE p.id = ' . $id);

$ok = $p && (is_admin() || ($p['employee_id'] == my_employee_id() && in_array($p['run_status'], array('approved', 'paid'))));
if (!$ok) {
    set_flash('Payslip not found.', 'error');
    redirect('dashboard.php');
}
$print = get('print') == '1';
$deductions_total = $p['income_tax'] + $p['social'] + $p['pension'] + $p['other_deductions'];

if ($print) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Payslip <?php echo h($p['first_name'] . ' ' . $p['last_name']); ?></title>
<link rel="stylesheet" type="text/css" href="css/print.css" />
</head>
<body class="print" onload="window.print();">
<?php } else {
    $page_title = 'Payslip';
    include 'header.php';
?>
<p class="actions noprint">
  <a href="payslip.php?id=<?php echo $id; ?>&amp;print=1" target="_blank" class="button" id="print-payslip">Print</a>
  <a href="payslip_csv.php?id=<?php echo $id; ?>" class="button" id="export-csv">Export CSV</a>
  <?php if (is_admin()) { ?><a href="payrun_view.php?id=<?php echo $p['payrun_id']; ?>">Back to pay run</a><?php } else { ?><a href="profile.php">Back</a><?php } ?>
</p>
<?php } ?>

<div class="payslip" id="payslip">
  <table width="100%">
    <tr>
      <td><h2><?php echo h(get_setting('company_name', 'Acme Corp.')); ?></h2>Payslip for <?php echo date('F Y', strtotime($p['period_start'])); ?></td>
      <td align="right">Pay date: <?php echo fmt_date($p['pay_date']); ?><br />Period: <?php echo fmt_date($p['period_start']); ?> - <?php echo fmt_date($p['period_end']); ?></td>
    </tr>
  </table>
  <table class="details" width="100%">
    <tr><th>Employee</th><td><?php echo h($p['first_name'] . ' ' . $p['last_name']); ?> (<?php echo h($p['emp_no']); ?>)</td></tr>
    <tr><th>Department</th><td><?php echo h($p['department_name']); ?></td></tr>
    <tr><th>Job title</th><td><?php echo h($p['job_title']); ?></td></tr>
    <?php if ($p['pay_type'] == 'hourly') { ?>
      <tr><th>Hourly rate</th><td><?php echo format_money($p['hourly_rate']); ?></td></tr>
      <tr><th>Regular hours</th><td><?php echo number_format($p['regular_hours'], 2); ?></td></tr>
      <tr><th>Overtime hours (x1.5)</th><td><?php echo number_format($p['overtime_hours'], 2); ?></td></tr>
    <?php } else { ?>
      <tr><th>Annual salary</th><td><?php echo format_money($p['annual_salary']); ?></td></tr>
    <?php } ?>
  </table>
  <table class="breakdown" width="100%" id="payslip-breakdown">
    <tr class="total"><th>Gross pay</th><td class="num"><?php echo format_money($p['gross']); ?></td></tr>
    <tr><td>Income tax</td><td class="num">-<?php echo format_money($p['income_tax']); ?></td></tr>
    <tr><td>Social contribution</td><td class="num">-<?php echo format_money($p['social']); ?></td></tr>
    <tr><td>Pension</td><td class="num">-<?php echo format_money($p['pension']); ?></td></tr>
    <tr><td>Other deductions</td><td class="num">-<?php echo format_money($p['other_deductions']); ?></td></tr>
    <tr class="total"><th>Total deductions</th><td class="num">-<?php echo format_money($deductions_total); ?></td></tr>
    <tr class="net"><th>Net pay</th><td class="num" id="payslip-net"><?php echo format_money($p['net']); ?></td></tr>
  </table>
</div>

<?php if ($print) { ?>
<p class="small">Generated <?php echo date('m/d/Y H:i'); ?>. Confidential.</p>
</body>
</html>
<?php } else {
    include 'footer.php';
} ?>
