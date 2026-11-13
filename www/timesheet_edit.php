<?php
include 'includes/functions.php';
include 'includes/timesheet_lib.php';
require_login();

$id = (int)get('id');
$errors = array();
$days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');

if ($id) {
    $ts = load_timesheet($id);
    if (!$ts || !can_view_timesheet($ts)) {
        set_flash('Timesheet not found.', 'error');
        redirect('timesheets.php');
    }
    if (!can_edit_timesheet($ts)) {
        set_flash('This timesheet can no longer be edited.', 'error');
        redirect('timesheet_view.php?id=' . $id);
    }
    $employee_id = $ts['employee_id'];
    $week_start = $ts['week_start'];
    $hours = array_values($ts['hours']);
    $notes = $ts['notes'];
} else {
    if (!is_admin() && !my_employee_id()) {
        require_role(array('admin'));
    }
    $employee_id = is_admin() ? (int)get('employee_id', my_employee_id()) : my_employee_id();
    $week_start = monday_of(date('Y-m-d'));
    $hours = array(0, 0, 0, 0, 0, 0, 0);
    $notes = '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$id) {
        $week_start = post('week_start');
        if (is_admin()) {
            $employee_id = (int)post('employee_id');
        }
    }
    $notes = post('notes');
    for ($i = 0; $i < 7; $i++) {
        $hours[$i] = isset($_POST['hours'][$i]) ? trim($_POST['hours'][$i]) : 0;
        if ($hours[$i] === '') {
            $hours[$i] = 0;
        }
        if (!is_numeric($hours[$i]) || $hours[$i] < 0 || $hours[$i] > 24) {
            $errors[] = $days[$i] . ': hours must be between 0 and 24.';
        }
    }
    if (!$employee_id) {
        $errors[] = 'Please select an employee.';
    }
    if (!$id) {
        if (!valid_date($week_start)) {
            $errors[] = 'Please enter a valid week start date.';
        } elseif (date('N', strtotime($week_start)) != 1) {
            $errors[] = 'The week must start on a Monday.';
        } elseif (db_value('SELECT COUNT(*) FROM timesheets WHERE employee_id = ' . (int)$employee_id . " AND week_start = '" . esc($week_start) . "'")) {
            $errors[] = 'A timesheet for this week already exists.';
        }
    }

    if (!$errors) {
        if ($id) {
            $stmt = db_prepared('UPDATE timesheets SET notes = ? WHERE id = ?', 'si', array($notes, $id));
            mysqli_stmt_close($stmt);
            db_query('DELETE FROM timesheet_entries WHERE timesheet_id = ' . $id);
            audit_log('update', 'timesheet', $id, 'Updated timesheet');
        } else {
            $stmt = db_prepared("INSERT INTO timesheets (employee_id, week_start, status, notes, created_at) VALUES (?, ?, 'draft', ?, NOW())", 'iss', array($employee_id, $week_start, $notes));
            $id = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);
            audit_log('create', 'timesheet', $id, 'Created timesheet for week ' . $week_start);
        }
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime($week_start . " +$i days"));
            db_query("INSERT INTO timesheet_entries (timesheet_id, work_date, hours) VALUES ($id, '$d', " . (float)$hours[$i] . ')');
        }

        if (isset($_POST['submit_for_approval'])) {
            db_query("UPDATE timesheets SET status = 'submitted', submitted_at = NOW(), reject_reason = NULL WHERE id = $id");
            audit_log('submit', 'timesheet', $id, 'Submitted timesheet');
            set_flash('Timesheet submitted for approval.', 'success');
        } else {
            set_flash('Timesheet saved as draft.', 'success');
        }
        redirect('timesheet_view.php?id=' . $id);
    }
}

$employees = is_admin() ? db_fetch_all("SELECT id, first_name, last_name FROM employees WHERE status = 'active' ORDER BY last_name, first_name") : array();

$page_title = $id ? 'Edit Timesheet' : 'New Timesheet';
include 'header.php';
?>
<p class="breadcrumb"><a href="timesheets.php">Timesheets</a> &raquo; <?php echo $id ? 'Edit' : 'New'; ?></p>
<h1><?php echo $page_title; ?></h1>
<?php if ($errors) { ?><div class="msg msg-error" id="form-errors"><?php echo implode('<br />', array_map('h', $errors)); ?></div><?php } ?>

<form method="post" action="timesheet_edit.php<?php echo $id ? '?id=' . $id : ''; ?>" id="timesheet-form">
<table class="form">
  <?php if (!$id && is_admin()) { ?>
  <tr><th><label for="employee_id">Employee</label></th><td>
    <select name="employee_id" id="employee_id">
      <option value="">-- select --</option>
      <?php foreach ($employees as $e) { ?>
        <option value="<?php echo $e['id']; ?>"<?php if ($e['id'] == $employee_id) echo ' selected="selected"'; ?>><?php echo h($e['last_name'] . ', ' . $e['first_name']); ?></option>
      <?php } ?>
    </select></td></tr>
  <?php } ?>
  <tr><th><label for="week_start">Week starting (Monday)</label></th><td>
    <?php if ($id) { echo fmt_date($week_start); } else { ?>
      <input type="text" name="week_start" id="week_start" value="<?php echo h($week_start); ?>" size="12" /> <span class="hint">YYYY-MM-DD</span>
    <?php } ?>
  </td></tr>
</table>

<table class="grid hours-grid" id="hours-grid">
  <tr><?php foreach ($days as $i => $dname) { ?><th><label for="hours_<?php echo $i; ?>"><?php echo $dname; ?></label></th><?php } ?><th>Total</th></tr>
  <tr>
    <?php foreach ($days as $i => $dname) { ?>
      <td><input type="text" name="hours[<?php echo $i; ?>]" id="hours_<?php echo $i; ?>" class="hours" size="4" value="<?php echo h($hours[$i]); ?>" /></td>
    <?php } ?>
    <td class="num"><b id="total-hours">0.00</b></td>
  </tr>
</table>
<p class="hint">Hours above 40 in a week are paid as overtime (1.5x).</p>

<table class="form">
  <tr><th><label for="notes">Notes</label></th><td><textarea name="notes" id="notes" rows="2" cols="50"><?php echo h($notes); ?></textarea></td></tr>
  <tr><td></td><td>
    <input type="submit" name="save" value="Save Draft" class="button" id="save-draft" />
    <input type="submit" name="submit_for_approval" value="Save &amp; Submit" class="button" id="save-submit" />
    <a href="timesheets.php">Cancel</a>
  </td></tr>
</table>
</form>

<script type="text/javascript">
function updateTotal() {
    var total = 0;
    $('input.hours').each(function () {
        var v = parseFloat($(this).val());
        if (!isNaN(v)) total += v;
    });
    $('#total-hours').text(total.toFixed(2));
    $('#total-hours').toggleClass('overtime', total > 40);
}
$(document).ready(function () {
    updateTotal();
    $('input.hours').keyup(updateTotal).change(updateTotal);
});
</script>
<?php include 'footer.php'; ?>
