<?php
include 'includes/functions.php';

if (is_logged_in()) {
    audit_log('logout', 'user', $_SESSION['user_id'], 'Logged out');
}
$_SESSION = array();
session_destroy();
session_start();
set_flash('You have been logged out.', 'info');
redirect('login.php');
