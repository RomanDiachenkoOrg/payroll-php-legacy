<?php
include 'includes/functions.php';
require_role(array('admin'));

$users = db_fetch_all('SELECT u.*, e.emp_no, e.id AS emp_id, d.name AS department_name
                         FROM users u LEFT JOIN employees e ON e.id = u.employee_id LEFT JOIN departments d ON d.id = e.department_id
                        ORDER BY u.email');

$page_title = 'Users';
include 'header.php';
?>
<h1>Users</h1>
<table class="grid" width="100%" id="user-table">
  <tr><th>Email</th><th>Name</th><th>Role</th><th>Employee</th><th>Department</th><th>Active</th><th>Last login</th></tr>
  <?php foreach ($users as $i => $u) { ?>
  <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
    <td><?php echo h($u['email']); ?></td>
    <td><?php echo h($u['full_name']); ?></td>
    <td><?php echo h(ucfirst($u['role'])); ?></td>
    <td><?php if ($u['emp_id']) { ?><a href="employee_view.php?id=<?php echo $u['emp_id']; ?>"><?php echo h($u['emp_no']); ?></a><?php } ?></td>
    <td><?php echo h($u['department_name']); ?></td>
    <td><?php echo $u['active'] ? 'Yes' : 'No'; ?></td>
    <td class="small"><?php echo $u['last_login'] ? date('m/d/Y H:i', strtotime($u['last_login'])) : 'never'; ?></td>
  </tr>
  <?php } ?>
</table>
<p class="hint">New logins are created by the system administrator in the database.</p>
<?php include 'footer.php'; ?>
