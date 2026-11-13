<?php
include 'includes/functions.php';
require_role(array('admin', 'manager'));

$departments = db_fetch_all("SELECT d.*, SUM(e.status = 'active') AS active_count, COUNT(e.id) AS total_count
                               FROM departments d LEFT JOIN employees e ON e.department_id = d.id
                              GROUP BY d.id ORDER BY d.name");

$page_title = 'Departments';
include 'header.php';
?>
<h1>Departments</h1>
<?php if (is_admin()) { ?>
<p><a href="department_edit.php" class="button" id="add-department">+ Add Department</a></p>
<?php } ?>
<table class="grid" width="100%" id="department-table">
  <tr><th>Name</th><th>Code</th><th>Description</th><th class="num">Active</th><th class="num">Total</th><?php if (is_admin()) { ?><th>Actions</th><?php } ?></tr>
  <?php foreach ($departments as $i => $d) { ?>
  <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
    <td><?php echo h($d['name']); ?></td>
    <td><?php echo h($d['code']); ?></td>
    <td class="small"><?php echo h($d['description']); ?></td>
    <td class="num"><?php echo (int)$d['active_count']; ?></td>
    <td class="num"><?php echo (int)$d['total_count']; ?></td>
    <?php if (is_admin()) { ?>
    <td nowrap="nowrap">
      <a href="department_edit.php?id=<?php echo $d['id']; ?>">edit</a> |
      <form method="post" action="department_delete.php" class="inline">
        <input type="hidden" name="id" value="<?php echo $d['id']; ?>" />
        <input type="submit" value="delete" class="link-button" data-confirm="Delete department <?php echo h($d['name']); ?>?" />
      </form>
    </td>
    <?php } ?>
  </tr>
  <?php } ?>
</table>
<?php include 'footer.php'; ?>
