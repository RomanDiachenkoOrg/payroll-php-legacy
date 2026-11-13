<?php
include 'includes/functions.php';
require_role(array('admin', 'manager'));

$q = get('q');
$dept = get('department');
$status = get('status');
$sort = get('sort', 'name');
$page = max(1, (int)get('page', 1));

$sort_columns = array(
    'name' => 'e.last_name ASC, e.first_name ASC',
    'name_desc' => 'e.last_name DESC, e.first_name DESC',
    'dept' => 'd.name ASC, e.last_name ASC',
    'hired' => 'e.hire_date ASC',
    'hired_desc' => 'e.hire_date DESC',
    'salary' => 'COALESCE(e.annual_salary, e.hourly_rate * 2080) DESC',
);
if (!isset($sort_columns[$sort])) {
    $sort = 'name';
}

$where = array('1=1');
if ($q != '') {
    $like = esc($q);
    $where[] = "(e.first_name LIKE '%$like%' OR e.last_name LIKE '%$like%' OR e.email LIKE '%$like%' OR e.emp_no = '$like' OR e.job_title LIKE '%$like%')";
}
if ($dept != '') {
    $where[] = 'e.department_id = ' . (int)$dept;
}
if ($status == 'active' || $status == 'inactive') {
    $where[] = "e.status = '" . $status . "'";
}
if (is_manager()) {
    $where[] = 'e.department_id = ' . my_department_id();
}
$where_sql = implode(' AND ', $where);

$total = db_value("SELECT COUNT(*) FROM employees e WHERE $where_sql");
$pages = max(1, floor($total / EMPLOYEES_PER_PAGE));
if ($page > $pages) {
    $page = $pages;
}
$offset = ($page - 1) * EMPLOYEES_PER_PAGE;

$employees = db_fetch_all("SELECT e.*, d.name AS department_name
                             FROM employees e LEFT JOIN departments d ON d.id = e.department_id
                            WHERE $where_sql
                            ORDER BY " . $sort_columns[$sort] . "
                            LIMIT $offset, " . EMPLOYEES_PER_PAGE);

function sort_link($key, $label, $sort)
{
    $new = $key;
    $arrow = '';
    if ($sort == $key) {
        $new = $key . '_desc';
        $arrow = ' &#9650;';
    } elseif ($sort == $key . '_desc') {
        $arrow = ' &#9660;';
    }
    return '<a href="' . h(qs(array('sort' => $new, 'page' => ''))) . '">' . $label . $arrow . '</a>';
}

$page_title = 'Employees';
include 'header.php';
?>
<h1>Employees</h1>

<?php if (is_admin()) { ?>
<p><a href="employee_edit.php" class="button" id="add-employee">+ Add Employee</a></p>
<?php } ?>

<form method="get" action="employees.php" class="filter" id="employee-filter">
  <label for="q">Search:</label>
  <input type="text" name="q" id="q" value="<?php echo h($q); ?>" size="25" />
  <label for="department">Department:</label>
  <select name="department" id="department"><?php echo department_options($dept, 'All departments'); ?></select>
  <label for="status">Status:</label>
  <select name="status" id="status">
    <option value="">Any</option>
    <option value="active"<?php if ($status == 'active') echo ' selected="selected"'; ?>>Active</option>
    <option value="inactive"<?php if ($status == 'inactive') echo ' selected="selected"'; ?>>Inactive</option>
  </select>
  <input type="hidden" name="sort" value="<?php echo h($sort); ?>" />
  <input type="submit" value="Filter" class="button" />
  <a href="employees.php">Reset</a>
</form>

<table class="grid" width="100%" id="employee-table">
  <tr>
    <th>No.</th>
    <th><?php echo sort_link('name', 'Name', $sort); ?></th>
    <th>Email</th>
    <th><?php echo sort_link('dept', 'Department', $sort); ?></th>
    <th>Job Title</th>
    <th>Pay</th>
    <th><?php echo sort_link('hired', 'Hired', $sort); ?></th>
    <th>Status</th>
  </tr>
  <?php foreach ($employees as $i => $e) { ?>
  <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
    <td><?php echo h($e['emp_no']); ?></td>
    <td><a href="employee_view.php?id=<?php echo $e['id']; ?>"><?php echo h($e['last_name'] . ', ' . $e['first_name']); ?></a></td>
    <td><?php echo h($e['email']); ?></td>
    <td><?php echo h($e['department_name']); ?></td>
    <td><?php echo h($e['job_title']); ?></td>
    <td class="num"><?php echo $e['pay_type'] == 'hourly' ? format_money($e['hourly_rate']) . '/hr' : format_money($e['annual_salary']) . '/yr'; ?></td>
    <td><?php echo fmt_date($e['hire_date']); ?></td>
    <td><?php echo status_label($e['status']); ?></td>
  </tr>
  <?php } ?>
  <?php if (!$employees) { ?>
  <tr><td colspan="8" class="empty">No employees found.</td></tr>
  <?php } ?>
</table>

<div class="pager" id="pager">
  <?php echo (int)$total; ?> employee(s) &nbsp;|&nbsp; Page <?php echo $page; ?> of <?php echo $pages; ?> &nbsp;
  <?php if ($page > 1) { ?><a href="<?php echo h(qs(array('page' => $page - 1))); ?>">&laquo; Prev</a><?php } ?>
  <?php for ($p = 1; $p <= $pages; $p++) { ?>
    <?php if ($p == $page) { ?><b><?php echo $p; ?></b><?php } else { ?><a href="<?php echo h(qs(array('page' => $p))); ?>"><?php echo $p; ?></a><?php } ?>
  <?php } ?>
  <?php if ($page < $pages) { ?><a href="<?php echo h(qs(array('page' => $page + 1))); ?>">Next &raquo;</a><?php } ?>
</div>
<?php include 'footer.php'; ?>
