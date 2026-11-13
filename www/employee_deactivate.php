<?php
include 'includes/functions.php';
require_role(array('admin'));

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('employees.php');
}

$id = (int)post('id');
$emp = db_fetch_one('SELECT id, first_name, last_name, status FROM employees WHERE id = ' . $id);
if (!$emp) {
    set_flash('Employee not found.', 'error');
    redirect('employees.php');
}
$name = $emp['first_name'] . ' ' . $emp['last_name'];

if (post('reactivate')) {
    db_query("UPDATE employees SET status = 'active', termination_date = NULL, updated_at = NOW() WHERE id = " . $id);
    db_query('UPDATE users SET active = 1 WHERE employee_id = ' . $id);
    audit_log('update', 'employee', $id, 'Reactivated ' . $name);
    set_flash($name . ' has been reactivated.', 'success');
} else {
    db_query("UPDATE employees SET status = 'inactive', termination_date = CURDATE(), updated_at = NOW() WHERE id = " . $id);
    db_query('UPDATE users SET active = 0 WHERE employee_id = ' . $id);
    audit_log('update', 'employee', $id, 'Deactivated ' . $name);
    set_flash($name . ' has been deactivated.', 'success');
}
redirect('employee_view.php?id=' . $id);
