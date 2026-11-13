<?php
include 'includes/functions.php';
include 'includes/report_lib.php';
require_role(array('admin', 'manager'));

list($from, $to) = report_range();
$rows = department_cost_report($from, $to);
$totals = array('employees' => 0, 'gross' => 0, 'income_tax' => 0, 'social' => 0, 'pension' => 0, 'other_deductions' => 0, 'net' => 0);
foreach ($rows as $r) {
    foreach ($totals as $k => $v) {
        $totals[$k] += $r[$k];
    }
}

$page_title = 'Reports';
include 'header.php';
?>
<h1>Payroll Cost by Department</h1>
<form method="get" action="reports.php" class="filter">
  <label for="from">From:</label> <input type="text" name="from" id="from" value="<?php echo h($from); ?>" size="12" />
  <label for="to">To:</label> <input type="text" name="to" id="to" value="<?php echo h($to); ?>" size="12" />
  <input type="submit" value="Run Report" class="button" />
  <a href="report_csv.php?from=<?php echo urlencode($from); ?>&amp;to=<?php echo urlencode($to); ?>" class="button" id="export-report">Export CSV</a>
</form>
<p class="small">Approved and paid pay runs with a period starting between <?php echo fmt_date($from); ?> and <?php echo fmt_date($to); ?>.</p>

<table class="grid" width="100%" id="report-table">
  <tr><th>Department</th><th class="num">Employees</th><th class="num">Gross</th><th class="num">Income tax</th><th class="num">Social</th><th class="num">Pension</th><th class="num">Other</th><th class="num">Net</th></tr>
  <?php foreach ($rows as $i => $r) { ?>
  <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
    <td><?php echo h($r['department']); ?></td>
    <td class="num"><?php echo (int)$r['employees']; ?></td>
    <td class="num"><?php echo format_money($r['gross']); ?></td>
    <td class="num"><?php echo format_money($r['income_tax']); ?></td>
    <td class="num"><?php echo format_money($r['social']); ?></td>
    <td class="num"><?php echo format_money($r['pension']); ?></td>
    <td class="num"><?php echo format_money($r['other_deductions']); ?></td>
    <td class="num"><?php echo format_money($r['net']); ?></td>
  </tr>
  <?php } ?>
  <?php if ($rows) { ?>
  <tr class="total">
    <td><b>Total</b></td><td></td>
    <td class="num"><b><?php echo format_money($totals['gross']); ?></b></td>
    <td class="num"><b><?php echo format_money($totals['income_tax']); ?></b></td>
    <td class="num"><b><?php echo format_money($totals['social']); ?></b></td>
    <td class="num"><b><?php echo format_money($totals['pension']); ?></b></td>
    <td class="num"><b><?php echo format_money($totals['other_deductions']); ?></b></td>
    <td class="num"><b><?php echo format_money($totals['net']); ?></b></td>
  </tr>
  <?php } else { ?>
  <tr><td colspan="8" class="empty">No approved payroll in this period.</td></tr>
  <?php } ?>
</table>
<?php include 'footer.php'; ?>
