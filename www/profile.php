<?php
include 'includes/functions.php';
require_login();

if (!my_employee_id()) {
    set_flash('No employee record is linked to your account.', 'error');
    redirect('dashboard.php');
}
redirect('employee_view.php?id=' . my_employee_id());
