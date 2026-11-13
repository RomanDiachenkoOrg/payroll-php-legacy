<?php
include 'includes/functions.php';
require_role(array('admin'));

$id = (int)get('id');
$emp = db_fetch_one('SELECT * FROM employees WHERE id = ' . $id);
if (!$emp) {
    set_flash('Employee not found.', 'error');
    redirect('employees.php');
}
$hourly = $emp['pay_type'] == 'hourly';
$errors = array();
$effective = date('Y-m-d');
$amount = $hourly ? $emp['hourly_rate'] : $emp['annual_salary'];
$reason = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $effective = post('effective_date');
    $amount = post('amount');
    $reason = post('reason');

    if (!valid_date($effective)) {
        $errors[] = 'Please enter a valid effective date.';
    }
    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = 'The new amount must be a number greater than 0.';
    }
    if ($reason == '') {
        $errors[] = 'Please give a reason.';
    }

    if (!$errors) {
        $col = $hourly ? 'hourly_rate' : 'annual_salary';
        $stmt = db_prepared(
            "INSERT INTO salary_history (employee_id, effective_date, $col, reason, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            'isdsi',
            array($id, $effective, $amount, $reason, $_SESSION['user_id'])
        );
        mysqli_stmt_close($stmt);
        if ($effective <= date('Y-m-d')) {
            db_query("UPDATE employees SET $col = " . (float)$amount . ', updated_at = NOW() WHERE id = ' . $id);
        }
        audit_log('create', 'salary_history', $id, 'Pay change for ' . $emp['first_name'] . ' ' . $emp['last_name'] . ': ' . $amount . ' from ' . $effective);
        set_flash('Pay change saved.', 'success');
        redirect('employee_view.php?id=' . $id);
    }
}

$page_title = 'Add Raise';
include 'header.php';
?>
<p class="breadcrumb"><a href="employees.php">Employees</a> &raquo; <a href="employee_view.php?id=<?php echo $id; ?>"><?php echo h($emp['first_name'] . ' ' . $emp['last_name']); ?></a> &raquo; Add Raise</p>
<h1>Pay change: <?php echo h($emp['first_name'] . ' ' . $emp['last_name']); ?></h1>
<?php if ($errors) { ?>
<div class="msg msg-error"><?php echo implode('<br />', array_map('h', $errors)); ?></div>
<?php } ?>
<form method="post" action="salary_raise.php?id=<?php echo $id; ?>">
<table class="form">
  <tr><th>Current pay</th><td><?php echo $hourly ? format_money($emp['hourly_rate']) . ' / hour' : format_money($emp['annual_salary']) . ' / year'; ?></td></tr>
  <tr><th><label for="effective_date">Effective date</label></th><td><input type="text" name="effective_date" id="effective_date" value="<?php echo h($effective); ?>" size="12" /> <span class="hint">YYYY-MM-DD</span></td></tr>
  <tr><th><label for="amount"><?php echo $hourly ? 'New hourly rate' : 'New annual salary'; ?></label></th><td><input type="text" name="amount" id="amount" value="<?php echo h($amount); ?>" /></td></tr>
  <tr><th><label for="reason">Reason</label></th><td><input type="text" name="reason" id="reason" size="40" value="<?php echo h($reason); ?>" /></td></tr>
  <tr><td></td><td><input type="submit" value="Save" class="button" /> <a href="employee_view.php?id=<?php echo $id; ?>">Cancel</a></td></tr>
</table>
</form>
<?php include 'footer.php'; ?>
