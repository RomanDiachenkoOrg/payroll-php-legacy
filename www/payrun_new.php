<?php
include 'includes/functions.php';
require_role(array('admin'));

$errors = array();
$start = date('Y-m-01');
$end = date('Y-m-t');
$pay_date = date('Y-m-') . sprintf('%02d', (int)get_setting('pay_day', 28));
$notes = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start = post('period_start');
    $end = post('period_end');
    $pay_date = post('pay_date');
    $notes = post('notes');

    if (!valid_date($start) || !valid_date($end) || !valid_date($pay_date)) {
        $errors[] = 'Please enter valid dates (YYYY-MM-DD).';
    } elseif ($end < $start) {
        $errors[] = 'Period end must be on or after the period start.';
    } elseif ($pay_date < $start) {
        $errors[] = 'Pay date cannot be before the period start.';
    } else {
        $overlap = db_value("SELECT COUNT(*) FROM payruns WHERE period_start <= '" . esc($end) . "' AND period_end >= '" . esc($start) . "'");
        if ($overlap) {
            $errors[] = 'Another pay run already covers part of this period.';
        }
    }

    if (!$errors) {
        $stmt = db_prepared("INSERT INTO payruns (period_start, period_end, pay_date, status, notes, created_by, created_at) VALUES (?, ?, ?, 'draft', ?, ?, NOW())",
            'ssssi', array($start, $end, $pay_date, $notes, $_SESSION['user_id']));
        $id = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);
        audit_log('create', 'payrun', $id, 'Created pay run ' . $start . ' - ' . $end);
        set_flash('Pay run created as draft.', 'success');
        redirect('payrun_view.php?id=' . $id);
    }
}

$page_title = 'New Pay Run';
include 'header.php';
?>
<p class="breadcrumb"><a href="payruns.php">Pay Runs</a> &raquo; New</p>
<h1>New Pay Run</h1>
<?php if ($errors) { ?><div class="msg msg-error"><?php echo implode('<br />', array_map('h', $errors)); ?></div><?php } ?>
<form method="post" action="payrun_new.php">
<table class="form">
  <tr><th><label for="period_start">Period start</label></th><td><input type="text" name="period_start" id="period_start" value="<?php echo h($start); ?>" size="12" /></td></tr>
  <tr><th><label for="period_end">Period end</label></th><td><input type="text" name="period_end" id="period_end" value="<?php echo h($end); ?>" size="12" /></td></tr>
  <tr><th><label for="pay_date">Pay date</label></th><td><input type="text" name="pay_date" id="pay_date" value="<?php echo h($pay_date); ?>" size="12" /></td></tr>
  <tr><th><label for="notes">Notes</label></th><td><input type="text" name="notes" id="notes" value="<?php echo h($notes); ?>" size="40" /></td></tr>
  <tr><td></td><td><input type="submit" value="Create Draft" class="button" /> <a href="payruns.php">Cancel</a></td></tr>
</table>
</form>
<?php include 'footer.php'; ?>
