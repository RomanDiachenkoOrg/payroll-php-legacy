<?php
include 'includes/functions.php';
require_login();

$status = get('status');
$statuses = array('draft', 'submitted', 'approved', 'rejected');

$sql = "SELECT t.id, t.week_start, t.status, e.first_name, e.last_name, d.name AS department_name, u.full_name AS reviewer,
               (SELECT COALESCE(SUM(hours), 0) FROM timesheet_entries x WHERE x.timesheet_id = t.id) AS total_hours
          FROM timesheets t
          JOIN employees e ON e.id = t.employee_id
          LEFT JOIN departments d ON d.id = e.department_id
          LEFT JOIN users u ON u.id = t.reviewed_by
         WHERE 1=1";
if (is_manager()) {
    $sql .= ' AND (e.department_id = ' . my_department_id() . ' OR e.id = ' . my_employee_id() . ')';
} elseif (!is_admin()) {
    $sql .= ' AND t.employee_id = ' . my_employee_id();
}
if (in_array($status, $statuses)) {
    $sql .= " AND t.status = '$status'";
}
$sql .= ' ORDER BY t.week_start DESC, e.last_name LIMIT 200';
$rows = db_fetch_all($sql);

$page_title = 'Timesheets';
include 'header.php';
?>
<h1>Timesheets</h1>
<?php if (my_employee_id() || is_admin()) { ?>
<p><a href="timesheet_edit.php" class="button" id="new-timesheet">+ New Timesheet</a></p>
<?php } ?>

<form method="get" action="timesheets.php" class="filter">
  <label for="status">Status:</label>
  <select name="status" id="status" onchange="this.form.submit();">
    <option value="">All</option>
    <?php foreach ($statuses as $s) { ?>
      <option value="<?php echo $s; ?>"<?php if ($status == $s) echo ' selected="selected"'; ?>><?php echo ucfirst($s); ?></option>
    <?php } ?>
  </select>
  <noscript><input type="submit" value="Filter" /></noscript>
</form>

<table class="grid" width="100%" id="timesheet-table">
  <tr><th>Employee</th><th>Department</th><th>Week</th><th class="num">Hours</th><th class="num">Overtime</th><th>Status</th><th>Reviewed by</th></tr>
  <?php foreach ($rows as $i => $r) { list($reg, $ot) = split_week_hours($r['total_hours']); ?>
  <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
    <td><?php echo h($r['first_name'] . ' ' . $r['last_name']); ?></td>
    <td><?php echo h($r['department_name']); ?></td>
    <td><a href="timesheet_view.php?id=<?php echo $r['id']; ?>"><?php echo fmt_date($r['week_start']); ?> - <?php echo fmt_date(date('Y-m-d', strtotime($r['week_start'] . ' +6 days'))); ?></a></td>
    <td class="num"><?php echo number_format($r['total_hours'], 2); ?></td>
    <td class="num"><?php echo number_format($ot, 2); ?></td>
    <td><?php echo status_label($r['status']); ?></td>
    <td class="small"><?php echo h($r['reviewer']); ?></td>
  </tr>
  <?php } ?>
  <?php if (!$rows) { ?><tr><td colspan="7" class="empty">No timesheets found.</td></tr><?php } ?>
</table>
<?php include 'footer.php'; ?>
