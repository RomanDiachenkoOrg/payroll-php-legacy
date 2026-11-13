<?php
include 'includes/functions.php';
require_role(array('admin'));

$id = (int)get('id');
$run = db_fetch_one('SELECT r.*, u.full_name AS approver FROM payruns r LEFT JOIN users u ON u.id = r.approved_by WHERE r.id = ' . $id);
if (!$run) {
    set_flash('Pay run not found.', 'error');
    redirect('payruns.php');
}
$slips = db_fetch_all('SELECT p.*, e.first_name, e.last_name, e.emp_no FROM payslips p JOIN employees e ON e.id = p.employee_id
                        WHERE p.payrun_id = ' . $id . ' ORDER BY e.last_name, e.first_name');
$t = array('gross' => 0, 'income_tax' => 0, 'social' => 0, 'pension' => 0, 'other_deductions' => 0, 'net' => 0);
foreach ($slips as $s) {
    foreach ($t as $k => $v) {
        $t[$k] += $s[$k];
    }
}

$page_title = 'Pay Run';
include 'header.php';
?>
<p class="breadcrumb"><a href="payruns.php">Pay Runs</a> &raquo; <?php echo date('F Y', strtotime($run['period_start'])); ?></p>
<h1>Pay Run <?php echo date('F Y', strtotime($run['period_start'])); ?> <?php echo status_label($run['status']); ?></h1>
<p>Period <?php echo fmt_date($run['period_start']); ?> - <?php echo fmt_date($run['period_end']); ?> &middot; pay date <?php echo fmt_date($run['pay_date']); ?>
<?php if ($run['approver']) { ?> &middot; approved by <?php echo h($run['approver']); ?><?php } ?></p>

<div class="actions">
<?php if ($run['status'] == 'draft' || $run['status'] == 'calculated') { ?>
  <form method="post" action="payrun_action.php" class="inline">
    <input type="hidden" name="id" value="<?php echo $id; ?>" /><input type="hidden" name="action" value="calculate" />
    <input type="submit" value="<?php echo $run['status'] == 'draft' ? 'Calculate' : 'Recalculate'; ?>" class="button" id="calculate-payrun" />
  </form>
<?php } ?>
<?php if ($run['status'] == 'calculated' && count($slips) > 0) { ?>
  <form method="post" action="payrun_action.php" class="inline">
    <input type="hidden" name="id" value="<?php echo $id; ?>" /><input type="hidden" name="action" value="approve" />
    <input type="submit" value="Approve" class="button button-ok" id="approve-payrun" data-confirm="Approve this pay run? Payslips will become visible to employees." />
  </form>
<?php } ?>
<?php if ($run['status'] == 'approved') { ?>
  <form method="post" action="payrun_action.php" class="inline">
    <input type="hidden" name="id" value="<?php echo $id; ?>" /><input type="hidden" name="action" value="pay" />
    <input type="submit" value="Mark as Paid" class="button" id="pay-payrun" data-confirm="Mark this pay run as paid? It will be locked." />
  </form>
<?php } ?>
<?php if ($run['status'] == 'paid') { ?><span class="locked">Locked - paid on <?php echo fmt_date($run['paid_at']); ?></span><?php } ?>
</div>

<table class="summary">
  <tr>
    <td>Payslips<br /><b><?php echo count($slips); ?></b></td>
    <td>Gross<br /><b><?php echo format_money($t['gross']); ?></b></td>
    <td>Income tax<br /><b><?php echo format_money($t['income_tax']); ?></b></td>
    <td>Social<br /><b><?php echo format_money($t['social']); ?></b></td>
    <td>Pension<br /><b><?php echo format_money($t['pension']); ?></b></td>
    <td>Net<br /><b><?php echo format_money($t['net']); ?></b></td>
  </tr>
</table>

<table class="grid" width="100%" id="payslip-lines">
  <tr><th>No.</th><th>Employee</th><th>Department</th><th>Type</th><th class="num">Hours (OT)</th><th class="num">Gross</th><th class="num">Tax</th><th class="num">Social</th><th class="num">Pension</th><th class="num">Other</th><th class="num">Net</th><th></th></tr>
  <?php foreach ($slips as $i => $s) { ?>
  <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
    <td><?php echo h($s['emp_no']); ?></td>
    <td><?php echo h($s['last_name'] . ', ' . $s['first_name']); ?></td>
    <td><?php echo h($s['department_name']); ?></td>
    <td><?php echo h(ucfirst($s['pay_type'])); ?></td>
    <td class="num"><?php echo $s['pay_type'] == 'hourly' ? number_format($s['regular_hours'], 2) . ' (' . number_format($s['overtime_hours'], 2) . ')' : '-'; ?></td>
    <td class="num"><?php echo format_money($s['gross']); ?></td>
    <td class="num"><?php echo format_money($s['income_tax']); ?></td>
    <td class="num"><?php echo format_money($s['social']); ?></td>
    <td class="num"><?php echo format_money($s['pension']); ?></td>
    <td class="num"><?php echo format_money($s['other_deductions']); ?></td>
    <td class="num"><b><?php echo format_money($s['net']); ?></b></td>
    <td><a href="payslip.php?id=<?php echo $s['id']; ?>">view</a></td>
  </tr>
  <?php } ?>
  <?php if (!$slips) { ?><tr><td colspan="12" class="empty">Not calculated yet.</td></tr><?php } ?>
</table>
<?php include 'footer.php'; ?>
