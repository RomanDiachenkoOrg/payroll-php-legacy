<?php
if (!isset($page_title)) {
    $page_title = APP_NAME;
}
$current = basename($_SERVER['PHP_SELF']);
function nav_link($file, $label, $current, $also = array())
{
    $active = ($file == $current || in_array($current, $also)) ? ' class="active"' : '';
    return '<li' . $active . '><a href="' . $file . '">' . $label . '</a></li>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo h($page_title); ?> - <?php echo APP_NAME; ?></title>
<link rel="stylesheet" type="text/css" href="css/style.css" />
<link rel="stylesheet" type="text/css" href="css/print.css" media="print" />
<script type="text/javascript" src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script type="text/javascript" src="js/common.js"></script>
</head>
<body>
<table id="layout" width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td colspan="2" id="top">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td><a href="dashboard.php" id="logo"><?php echo APP_NAME; ?></a></td>
          <td align="right" class="userbox">
          <?php if (is_logged_in()) { ?>
            Logged in as <b><?php echo h($_SESSION['full_name']); ?></b>
            (<span id="user-role"><?php echo h(ucfirst(user_role())); ?></span>)
            &nbsp;|&nbsp; <a href="logout.php" id="logout-link">Log out</a>
          <?php } ?>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <?php if (is_logged_in()) { ?>
    <td id="sidebar" valign="top" width="190">
      <ul class="menu">
        <?php echo nav_link('dashboard.php', 'Dashboard', $current); ?>
        <?php if (is_admin() || is_manager()) { ?>
          <?php echo nav_link('employees.php', 'Employees', $current, array('employee_edit.php', 'employee_view.php', 'salary_raise.php')); ?>
          <?php echo nav_link('departments.php', 'Departments', $current, array('department_edit.php')); ?>
        <?php } ?>
        <?php if (my_employee_id()) { ?>
          <?php echo nav_link('profile.php', 'My Profile', $current); ?>
        <?php } ?>
        <?php echo nav_link('timesheets.php', 'Timesheets', $current, array('timesheet_edit.php', 'timesheet_view.php')); ?>
        <?php if (is_admin()) { ?>
          <?php echo nav_link('payruns.php', 'Pay Runs', $current, array('payrun_new.php', 'payrun_view.php', 'payslip.php')); ?>
        <?php } ?>
        <?php if (is_admin() || is_manager()) { ?>
          <?php echo nav_link('reports.php', 'Reports', $current); ?>
        <?php } ?>
        <?php if (is_admin()) { ?>
          <li class="menu-header">Administration</li>
          <?php echo nav_link('settings.php', 'Tax &amp; Rates', $current); ?>
          <?php echo nav_link('users.php', 'Users', $current); ?>
          <?php echo nav_link('audit.php', 'Audit Log', $current); ?>
        <?php } ?>
      </ul>
    </td>
    <?php } ?>
    <td id="content" valign="top">
<?php show_flash(); ?>
