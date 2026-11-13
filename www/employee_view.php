<?php
include 'includes/functions.php';
require_login();

$id = (int)get('id');
$emp = db_fetch_one('SELECT e.*, d.name AS department_name FROM employees e LEFT JOIN departments d ON d.id = e.department_id WHERE e.id = ' . $id);
if (!$emp) {
    set_flash('Employee not found.', 'error');
    redirect('dashboard.php');
}

// who can see this record
$allowed = is_admin()
    || (is_manager() && $emp['department_id'] == my_department_id())
    || ($emp['id'] == my_employee_id());
if (!$allowed) {
    require_role(array('admin'));
}

$history = db_fetch_all('SELECT h.*, u.full_name AS created_by_name FROM salary_history h LEFT JOIN users u ON u.id = h.created_by
                          WHERE h.employee_id = ' . $id . ' ORDER BY h.effective_date DESC, h.id DESC');

$payslip_sql = 'SELECT p.*, r.period_start, r.period_end, r.status AS run_status FROM payslips p JOIN payruns r ON r.id = p.payrun_id WHERE p.employee_id = ' . $id;
if (!is_admin()) {
    $payslip_sql .= " AND r.status IN ('approved', 'paid')";
}
$payslips = db_fetch_all($payslip_sql . ' ORDER BY r.period_start DESC');

$deductions = db_fetch_all('SELECT t.name, ed.amount FROM employee_deductions ed JOIN deduction_types t ON t.id = ed.deduction_type_id WHERE ed.employee_id = ' . $id);

$page_title = $emp['first_name'] . ' ' . $emp['last_name'];
include 'header.php';
?>
<?php if (is_admin() || is_manager()) { ?>
<p class="breadcrumb"><a href="employees.php">Employees</a> &raquo; <?php echo h($page_title); ?></p>
<?php } ?>
<h1><?php echo h($page_title); ?> <?php echo status_label($emp['status']); ?></h1>

<?php if (is_admin()) { ?>
<p class="actions">
  <a href="employee_edit.php?id=<?php echo $id; ?>" class="button" id="edit-employee">Edit</a>
  <?php if ($emp['status'] == 'active') { ?>
    <a href="salary_raise.php?id=<?php echo $id; ?>" class="button" id="add-raise">Add Raise</a>
    <form method="post" action="employee_deactivate.php" class="inline" id="deactivate-form">
      <input type="hidden" name="id" value="<?php echo $id; ?>" />
      <input type="submit" value="Deactivate" class="button button-danger" id="deactivate-employee"
             data-confirm="Deactivate <?php echo h($page_title); ?>? The employee will no longer be paid and cannot log in." />
    </form>
  <?php } else { ?>
    <form method="post" action="employee_deactivate.php" class="inline">
      <input type="hidden" name="id" value="<?php echo $id; ?>" />
      <input type="hidden" name="reactivate" value="1" />
      <input type="submit" value="Reactivate" class="button" />
    </form>
  <?php } ?>
</p>
<?php } ?>

<table width="100%" cellspacing="0" cellpadding="0">
<tr>
<td valign="top" width="45%">
  <h2>Profile</h2>
  <table class="details">
    <tr><th>Employee no.</th><td><?php echo h($emp['emp_no']); ?></td></tr>
    <tr><th>Email</th><td><?php echo h($emp['email']); ?></td></tr>
    <tr><th>Phone</th><td><?php echo h($emp['phone']); ?></td></tr>
    <tr><th>Department</th><td><?php echo h($emp['department_name']); ?></td></tr>
    <tr><th>Job title</th><td><?php echo h($emp['job_title']); ?></td></tr>
    <tr><th>Hire date</th><td><?php echo fmt_date($emp['hire_date']); ?></td></tr>
    <?php if ($emp['termination_date']) { ?><tr><th>Left on</th><td><?php echo fmt_date($emp['termination_date']); ?></td></tr><?php } ?>
    <tr><th>Pay type</th><td><?php echo $emp['pay_type'] == 'hourly' ? 'Hourly' : 'Salaried (monthly)'; ?></td></tr>
    <tr><th>Current pay</th><td><?php echo $emp['pay_type'] == 'hourly' ? format_money($emp['hourly_rate']) . ' / hour' : format_money($emp['annual_salary']) . ' / year'; ?></td></tr>
    <tr><th>Pension</th><td><?php echo $emp['pension_opt_in'] ? 'Opted in' : 'Not enrolled'; ?></td></tr>
    <tr><th>Other deductions</th><td>
      <?php foreach ($deductions as $d) { echo h($d['name']) . ': ' . format_money($d['amount']) . '<br />'; } ?>
      <?php if (!$deductions) echo '-'; ?>
    </td></tr>
  </table>
</td>
<td valign="top" style="padding-left: 20px;">
  <h2>Salary history</h2>
  <table class="grid" width="100%" id="salary-history">
    <tr><th>Effective</th><th>Amount</th><th>Reason</th><th>By</th></tr>
    <?php foreach ($history as $i => $hrow) { ?>
    <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
      <td><?php echo fmt_date($hrow['effective_date']); ?></td>
      <td class="num"><?php echo $hrow['hourly_rate'] !== null ? format_money($hrow['hourly_rate']) . '/hr' : format_money($hrow['annual_salary']) . '/yr'; ?></td>
      <td><?php echo h($hrow['reason']); ?></td>
      <td class="small"><?php echo h($hrow['created_by_name']); ?></td>
    </tr>
    <?php } ?>
  </table>
</td>
</tr>
</table>

<h2>Payslips</h2>
<table class="grid" width="100%" id="payslip-list">
  <tr><th>Period</th><th>Status</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th></th></tr>
  <?php foreach ($payslips as $i => $p) { ?>
  <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
    <td><?php echo date('F Y', strtotime($p['period_start'])); ?></td>
    <td><?php echo status_label($p['run_status']); ?></td>
    <td class="num"><?php echo format_money($p['gross']); ?></td>
    <td class="num"><?php echo format_money($p['income_tax'] + $p['social'] + $p['pension'] + $p['other_deductions']); ?></td>
    <td class="num"><b><?php echo format_money($p['net']); ?></b></td>
    <td><a href="payslip.php?id=<?php echo $p['id']; ?>">view</a></td>
  </tr>
  <?php } ?>
  <?php if (!$payslips) { ?><tr><td colspan="6" class="empty">No payslips yet.</td></tr><?php } ?>
</table>
<?php include 'footer.php'; ?>
