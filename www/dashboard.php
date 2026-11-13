<?php
include 'includes/functions.php';
require_login();

$role = user_role();

if ($role == 'admin' || $role == 'manager') {
    $dept_where = '';
    if ($role == 'manager') {
        $dept_where = ' AND department_id = ' . my_department_id();
    }
    $headcount = db_value("SELECT COUNT(*) FROM employees WHERE status = 'active'" . $dept_where);

    $pending_sql = "SELECT COUNT(*) FROM timesheets t JOIN employees e ON e.id = t.employee_id WHERE t.status = 'submitted'";
    if ($role == 'manager') {
        $pending_sql .= ' AND e.department_id = ' . my_department_id() . ' AND e.id <> ' . my_employee_id();
    }
    $pending = db_value($pending_sql);

    $last_run = db_fetch_one("SELECT r.*, (SELECT SUM(gross) FROM payslips WHERE payrun_id = r.id) AS total_gross,
                                     (SELECT SUM(net) FROM payslips WHERE payrun_id = r.id) AS total_net
                                FROM payruns r WHERE r.status = 'paid' ORDER BY r.period_start DESC LIMIT 1");

    $chart = db_fetch_all("SELECT r.period_start, COALESCE(SUM(p.gross), 0) AS gross
                             FROM payruns r LEFT JOIN payslips p ON p.payrun_id = r.id
                            GROUP BY r.id, r.period_start ORDER BY r.period_start DESC LIMIT 6");
    $chart = array_reverse($chart);
    $max = 1;
    foreach ($chart as $c) {
        if ($c['gross'] > $max) {
            $max = $c['gross'];
        }
    }

    $by_dept = db_fetch_all("SELECT d.name, SUM(e.status = 'active') AS active, COUNT(e.id) AS total
                               FROM departments d LEFT JOIN employees e ON e.department_id = d.id
                              GROUP BY d.id, d.name ORDER BY d.name");
} else {
    $emp_id = my_employee_id();
    $latest = db_fetch_one("SELECT p.*, r.period_start FROM payslips p JOIN payruns r ON r.id = p.payrun_id
                             WHERE p.employee_id = " . $emp_id . " AND r.status IN ('approved','paid')
                             ORDER BY r.period_start DESC LIMIT 1");
    $my_sheets = db_fetch_all('SELECT id, week_start, status FROM timesheets WHERE employee_id = ' . $emp_id . ' ORDER BY week_start DESC LIMIT 5');
    $pending = db_value("SELECT COUNT(*) FROM timesheets WHERE status = 'submitted' AND employee_id = " . $emp_id);
}

$page_title = 'Dashboard';
include 'header.php';
?>
<h1>Dashboard</h1>

<?php if ($role == 'admin' || $role == 'manager') { ?>
<table class="stats" cellspacing="10">
  <tr>
    <td class="stat">
      <div class="stat-label"><?php echo $role == 'manager' ? 'Department headcount' : 'Active employees'; ?></div>
      <div class="stat-value" id="stat-headcount"><?php echo (int)$headcount; ?></div>
    </td>
    <td class="stat">
      <div class="stat-label">Last payroll (gross)</div>
      <div class="stat-value" id="stat-last-payroll"><?php echo $last_run ? format_money($last_run['total_gross']) : '-'; ?></div>
      <?php if ($last_run) { ?><div class="small"><?php echo date('F Y', strtotime($last_run['period_start'])); ?>, net <?php echo format_money($last_run['total_net']); ?></div><?php } ?>
    </td>
    <td class="stat">
      <div class="stat-label">Pending timesheets</div>
      <div class="stat-value" id="stat-pending"><?php echo (int)$pending; ?></div>
      <div class="small"><a href="timesheets.php?status=submitted">review</a></div>
    </td>
    <td class="stat">
      <div class="stat-label">Next pay date</div>
      <div class="stat-value" id="stat-next-pay"><?php echo fmt_date(next_pay_date()); ?></div>
    </td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top" width="55%">
      <h2>Gross payroll by pay run</h2>
      <table class="bar-chart" id="payroll-chart">
        <?php foreach ($chart as $c) { $w = round($c['gross'] / $max * 100); ?>
        <tr>
          <td class="bar-label"><?php echo date('M Y', strtotime($c['period_start'])); ?></td>
          <td class="bar-cell"><div class="bar" style="width: <?php echo $w; ?>%;">&nbsp;</div></td>
          <td class="num"><?php echo format_money($c['gross']); ?></td>
        </tr>
        <?php } ?>
      </table>
    </td>
    <td valign="top" style="padding-left: 20px;">
      <h2>Headcount by department</h2>
      <table class="grid" width="100%">
        <tr><th>Department</th><th class="num">Active</th><th class="num">Total</th></tr>
        <?php foreach ($by_dept as $i => $d) { ?>
        <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
          <td><?php echo h($d['name']); ?></td>
          <td class="num"><?php echo (int)$d['active']; ?></td>
          <td class="num"><?php echo (int)$d['total']; ?></td>
        </tr>
        <?php } ?>
      </table>
    </td>
  </tr>
</table>
<?php } else { ?>
<table class="stats" cellspacing="10">
  <tr>
    <td class="stat">
      <div class="stat-label">Latest payslip</div>
      <?php if ($latest) { ?>
        <div class="stat-value"><?php echo format_money($latest['net']); ?></div>
        <div class="small">net for <?php echo date('F Y', strtotime($latest['period_start'])); ?> &middot; <a href="payslip.php?id=<?php echo $latest['id']; ?>">view</a></div>
      <?php } else { ?>
        <div class="stat-value">-</div>
      <?php } ?>
    </td>
    <td class="stat">
      <div class="stat-label">Next pay date</div>
      <div class="stat-value"><?php echo fmt_date(next_pay_date()); ?></div>
    </td>
    <td class="stat">
      <div class="stat-label">Timesheets awaiting approval</div>
      <div class="stat-value"><?php echo (int)$pending; ?></div>
      <div class="small"><a href="timesheet_edit.php">new timesheet</a></div>
    </td>
  </tr>
</table>
<h2>My recent timesheets</h2>
<table class="grid">
  <tr><th>Week starting</th><th>Status</th></tr>
  <?php foreach ($my_sheets as $s) { ?>
  <tr><td><a href="timesheet_view.php?id=<?php echo $s['id']; ?>"><?php echo fmt_date($s['week_start']); ?></a></td><td><?php echo status_label($s['status']); ?></td></tr>
  <?php } ?>
  <?php if (!$my_sheets) { ?><tr><td colspan="2" class="empty">No timesheets yet.</td></tr><?php } ?>
</table>
<?php } ?>
<?php include 'footer.php'; ?>
