<?php
include 'includes/functions.php';
require_role(array('admin'));

$id = (int)get('id');
$errors = array();

$emp = array(
    'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '',
    'department_id' => '', 'job_title' => '', 'pay_type' => 'salaried',
    'annual_salary' => '', 'hourly_rate' => '', 'hire_date' => date('Y-m-d'),
    'pension_opt_in' => 0,
);

if ($id) {
    $emp = db_fetch_one('SELECT * FROM employees WHERE id = ' . $id);
    if (!$emp) {
        set_flash('Employee not found.', 'error');
        redirect('employees.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp['first_name'] = post('first_name');
    $emp['last_name'] = post('last_name');
    $emp['email'] = strtolower(post('email'));
    $emp['phone'] = post('phone');
    $emp['department_id'] = post('department_id');
    $emp['job_title'] = post('job_title');
    $emp['hire_date'] = post('hire_date');
    $emp['pension_opt_in'] = isset($_POST['pension_opt_in']) ? 1 : 0;
    if (!$id) {
        $emp['pay_type'] = post('pay_type');
        $emp['annual_salary'] = post('annual_salary');
        $emp['hourly_rate'] = post('hourly_rate');
    }

    // validation
    if ($emp['first_name'] == '') $errors['first_name'] = 'First name is required.';
    if ($emp['last_name'] == '') $errors['last_name'] = 'Last name is required.';
    if ($emp['job_title'] == '') $errors['job_title'] = 'Job title is required.';
    if ($emp['department_id'] == '') $errors['department_id'] = 'Please select a department.';

    if ($emp['email'] == '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($emp['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        $dupe = db_value("SELECT COUNT(*) FROM employees WHERE email = '" . esc($emp['email']) . "' AND id <> " . $id);
        if ($dupe > 0) {
            $errors['email'] = 'This email address is already used by another employee.';
        }
    }

    if ($emp['hire_date'] == '' || !valid_date($emp['hire_date'])) {
        $errors['hire_date'] = 'Please enter a valid hire date (YYYY-MM-DD).';
    } elseif ($emp['hire_date'] > date('Y-m-d')) {
        $errors['hire_date'] = 'Hire date cannot be in the future.';
    }

    if (!$id) {
        if ($emp['pay_type'] == 'hourly') {
            if ($emp['hourly_rate'] == '' || !is_numeric($emp['hourly_rate'])) {
                $errors['hourly_rate'] = 'Hourly rate is required.';
            } elseif ($emp['hourly_rate'] <= 0) {
                $errors['hourly_rate'] = 'Hourly rate must be greater than 0.';
            }
            $emp['annual_salary'] = null;
        } elseif ($emp['pay_type'] == 'salaried') {
            if ($emp['annual_salary'] == '' || !is_numeric($emp['annual_salary'])) {
                $errors['annual_salary'] = 'Annual salary is required.';
            } elseif ($emp['annual_salary'] < 0) {
                $errors['annual_salary'] = 'Salary must be greater than 0.';
            }
            $emp['hourly_rate'] = null;
        } else {
            $errors['pay_type'] = 'Please select a pay type.';
        }
    }

    if (!$errors) {
        if ($id) {
            $stmt = db_prepared(
                'UPDATE employees SET first_name=?, last_name=?, email=?, phone=?, department_id=?, job_title=?, hire_date=?, pension_opt_in=?, updated_at=NOW() WHERE id=?',
                'ssssissii',
                array($emp['first_name'], $emp['last_name'], $emp['email'], $emp['phone'], $emp['department_id'], $emp['job_title'], $emp['hire_date'], $emp['pension_opt_in'], $id)
            );
            mysqli_stmt_close($stmt);
            audit_log('update', 'employee', $id, 'Updated employee ' . $emp['first_name'] . ' ' . $emp['last_name']);
            set_flash('Employee saved.', 'success');
        } else {
            $next_no = 'E' . str_pad((int)db_value('SELECT COALESCE(MAX(id), 0) FROM employees') + 1001, 4, '0', STR_PAD_LEFT);
            $stmt = db_prepared(
                "INSERT INTO employees (emp_no, first_name, last_name, email, phone, department_id, job_title, pay_type, annual_salary, hourly_rate, hire_date, status, pension_opt_in, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())",
                'sssssissddsi',
                array($next_no, $emp['first_name'], $emp['last_name'], $emp['email'], $emp['phone'], $emp['department_id'], $emp['job_title'], $emp['pay_type'], $emp['annual_salary'], $emp['hourly_rate'], $emp['hire_date'], $emp['pension_opt_in'])
            );
            $id = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);

            // starting pay goes into salary history
            db_query("INSERT INTO salary_history (employee_id, effective_date, annual_salary, hourly_rate, reason, created_by, created_at)
                      VALUES ($id, '" . esc($emp['hire_date']) . "', " . ($emp['annual_salary'] === null ? 'NULL' : (float)$emp['annual_salary']) . ", "
                      . ($emp['hourly_rate'] === null ? 'NULL' : (float)$emp['hourly_rate']) . ", 'Starting pay', " . (int)$_SESSION['user_id'] . ", NOW())");
            audit_log('create', 'employee', $id, 'Created employee ' . $emp['first_name'] . ' ' . $emp['last_name']);
            set_flash('Employee created.', 'success');
        }
        redirect('employee_view.php?id=' . $id);
    }
}

function field_error($errors, $name)
{
    if (isset($errors[$name])) {
        return '<span class="field-error" id="error-' . $name . '">' . h($errors[$name]) . '</span>';
    }
    return '';
}

$page_title = $id ? 'Edit Employee' : 'Add Employee';
include 'header.php';
?>
<p class="breadcrumb"><a href="employees.php">Employees</a> &raquo; <?php echo $id ? h($emp['first_name'] . ' ' . $emp['last_name']) : 'New'; ?></p>
<h1><?php echo $page_title; ?></h1>

<?php if ($errors) { ?>
<div class="msg msg-error">Please correct the errors below.</div>
<?php } ?>

<form method="post" action="employee_edit.php<?php echo $id ? '?id=' . $id : ''; ?>" id="employee-form">
<table class="form">
  <tr>
    <th><label for="first_name">First name *</label></th>
    <td><input type="text" name="first_name" id="first_name" value="<?php echo h($emp['first_name']); ?>" /> <?php echo field_error($errors, 'first_name'); ?></td>
  </tr>
  <tr>
    <th><label for="last_name">Last name *</label></th>
    <td><input type="text" name="last_name" id="last_name" value="<?php echo h($emp['last_name']); ?>" /> <?php echo field_error($errors, 'last_name'); ?></td>
  </tr>
  <tr>
    <th><label for="email">Email *</label></th>
    <td><input type="text" name="email" id="email" size="35" value="<?php echo h($emp['email']); ?>" /> <?php echo field_error($errors, 'email'); ?></td>
  </tr>
  <tr>
    <th><label for="phone">Phone</label></th>
    <td><input type="text" name="phone" id="phone" value="<?php echo h($emp['phone']); ?>" /></td>
  </tr>
  <tr>
    <th><label for="department_id">Department *</label></th>
    <td><select name="department_id" id="department_id"><?php echo department_options($emp['department_id']); ?></select> <?php echo field_error($errors, 'department_id'); ?></td>
  </tr>
  <tr>
    <th><label for="job_title">Job title *</label></th>
    <td><input type="text" name="job_title" id="job_title" size="35" value="<?php echo h($emp['job_title']); ?>" /> <?php echo field_error($errors, 'job_title'); ?></td>
  </tr>
  <?php if (!$id) { ?>
  <tr>
    <th><label for="pay_type">Pay type *</label></th>
    <td>
      <select name="pay_type" id="pay_type">
        <option value="salaried"<?php if ($emp['pay_type'] == 'salaried') echo ' selected="selected"'; ?>>Salaried (monthly)</option>
        <option value="hourly"<?php if ($emp['pay_type'] == 'hourly') echo ' selected="selected"'; ?>>Hourly</option>
      </select> <?php echo field_error($errors, 'pay_type'); ?>
    </td>
  </tr>
  <tr class="pay-salaried">
    <th><label for="annual_salary">Annual salary (USD) *</label></th>
    <td><input type="text" name="annual_salary" id="annual_salary" value="<?php echo h($emp['annual_salary']); ?>" /> <?php echo field_error($errors, 'annual_salary'); ?></td>
  </tr>
  <tr class="pay-hourly">
    <th><label for="hourly_rate">Hourly rate (USD) *</label></th>
    <td><input type="text" name="hourly_rate" id="hourly_rate" value="<?php echo h($emp['hourly_rate']); ?>" /> <?php echo field_error($errors, 'hourly_rate'); ?></td>
  </tr>
  <?php } else { ?>
  <tr>
    <th>Pay</th>
    <td><?php echo $emp['pay_type'] == 'hourly' ? format_money($emp['hourly_rate']) . ' / hour' : format_money($emp['annual_salary']) . ' / year'; ?>
      &nbsp;<a href="salary_raise.php?id=<?php echo $id; ?>">change pay</a></td>
  </tr>
  <?php } ?>
  <tr>
    <th><label for="hire_date">Hire date *</label></th>
    <td><input type="text" name="hire_date" id="hire_date" class="datepicker" value="<?php echo h($emp['hire_date']); ?>" size="12" /> <span class="hint">YYYY-MM-DD</span> <?php echo field_error($errors, 'hire_date'); ?></td>
  </tr>
  <tr>
    <th><label for="pension_opt_in">Pension scheme</label></th>
    <td><input type="checkbox" name="pension_opt_in" id="pension_opt_in" value="1"<?php if ($emp['pension_opt_in']) echo ' checked="checked"'; ?> /> opted in (3% of gross)</td>
  </tr>
  <tr>
    <td></td>
    <td>
      <input type="submit" value="Save" class="button" id="save-employee" />
      <a href="<?php echo $id ? 'employee_view.php?id=' . $id : 'employees.php'; ?>">Cancel</a>
    </td>
  </tr>
</table>
</form>

<script type="text/javascript">
function togglePayFields() {
    var hourly = $('#pay_type').val() == 'hourly';
    $('.pay-hourly').toggle(hourly);
    $('.pay-salaried').toggle(!hourly);
}
$(document).ready(function () {
    if ($('#pay_type').length) {
        togglePayFields();
        $('#pay_type').change(togglePayFields);
    }
});
</script>
<?php include 'footer.php'; ?>
