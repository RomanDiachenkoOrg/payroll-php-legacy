<?php
include 'includes/functions.php';
require_role(array('admin'));

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('departments.php');
}

$id = (int)post('id');
$dept = db_fetch_one('SELECT * FROM departments WHERE id = ' . $id);
if (!$dept) {
    set_flash('Department not found.', 'error');
    redirect('departments.php');
}

$active = db_value("SELECT COUNT(*) FROM employees WHERE department_id = $id AND status = 'active'");
if ($active > 0) {
    set_flash('Cannot delete ' . $dept['name'] . ': it still has ' . $active . ' active employee(s). Move or deactivate them first.', 'error');
    redirect('departments.php');
}

// inactive employees keep their history but lose the department link
db_query('UPDATE employees SET department_id = NULL WHERE department_id = ' . $id);
db_query('DELETE FROM departments WHERE id = ' . $id);
audit_log('delete', 'department', $id, 'Deleted department ' . $dept['name']);
set_flash('Department ' . $dept['name'] . ' deleted.', 'success');
redirect('departments.php');
