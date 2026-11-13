<?php
include 'includes/functions.php';
require_role(array('admin'));

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('payruns.php');
}

$id = (int)post('id');
$action = post('action');
$run = db_fetch_one('SELECT * FROM payruns WHERE id = ' . $id);
if (!$run) {
    set_flash('Pay run not found.', 'error');
    redirect('payruns.php');
}

if ($action == 'calculate') {
    if ($run['status'] != 'draft' && $run['status'] != 'calculated') {
        set_flash('A ' . $run['status'] . ' pay run cannot be recalculated.', 'error');
        redirect('payrun_view.php?id=' . $id);
    }

    $brackets = get_tax_brackets();
    $social_rate = (float)get_setting('social_rate', 0.05);
    $pension_rate = (float)get_setting('pension_rate', 0.03);
    $start = $run['period_start'];
    $end = $run['period_end'];

    mysqli_begin_transaction($db);
    db_query('DELETE FROM payslips WHERE payrun_id = ' . $id);

    $employees = db_fetch_all("SELECT e.*, d.name AS department_name,
                                      (SELECT COALESCE(SUM(amount), 0) FROM employee_deductions ed
                                         JOIN deduction_types dt ON dt.id = ed.deduction_type_id AND dt.active = 1
                                        WHERE ed.employee_id = e.id) AS other_deductions
                                 FROM employees e LEFT JOIN departments d ON d.id = e.department_id
                                WHERE e.hire_date <= '" . esc($end) . "'
                                ORDER BY e.id");

    $stmt = mysqli_prepare($db, 'INSERT INTO payslips (payrun_id, employee_id, department_name, pay_type, annual_salary, hourly_rate,
                                    regular_hours, overtime_hours, gross, income_tax, social, pension, other_deductions, net)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $count = 0;
    foreach ($employees as $e) {
        $regular = 0;
        $overtime = 0;
        if ($e['pay_type'] == 'hourly') {
            // approved weeks whose Monday falls inside the period
            $weeks = array();
            $rows = db_fetch_all("SELECT t.id, COALESCE(SUM(x.hours), 0) AS total FROM timesheets t
                                    LEFT JOIN timesheet_entries x ON x.timesheet_id = t.id
                                   WHERE t.employee_id = " . (int)$e['id'] . " AND t.status = 'approved'
                                     AND t.week_start BETWEEN '" . esc($start) . "' AND '" . esc($end) . "'
                                   GROUP BY t.id");
            foreach ($rows as $r) {
                $weeks[] = (float)$r['total'];
            }
            list($regular, $overtime) = calc_hours($weeks);
            $gross = calc_hourly_gross($e['hourly_rate'], $weeks);
        } else {
            $gross = calc_salaried_gross($e['annual_salary']);
        }
        if ($gross <= 0) {
            continue;
        }
        $p = calc_payslip($gross, $e['pension_opt_in'], $e['other_deductions'], $brackets, $social_rate, $pension_rate);
        mysqli_stmt_bind_param($stmt, 'iissdddddddddd', $id, $e['id'], $e['department_name'], $e['pay_type'], $e['annual_salary'], $e['hourly_rate'],
            $regular, $overtime, $p['gross'], $p['income_tax'], $p['social'], $p['pension'], $p['other_deductions'], $p['net']);
        mysqli_stmt_execute($stmt);
        $count++;
    }
    mysqli_stmt_close($stmt);
    db_query("UPDATE payruns SET status = 'calculated', calculated_at = NOW() WHERE id = $id");
    mysqli_commit($db);

    audit_log('calculate', 'payrun', $id, "Calculated pay run: $count payslips");
    set_flash("Pay run calculated: $count payslips.", 'success');
} elseif ($action == 'approve') {
    $slips = db_value('SELECT COUNT(*) FROM payslips WHERE payrun_id = ' . $id);
    if ($run['status'] != 'calculated' || !$slips) {
        set_flash('Only a calculated pay run can be approved.', 'error');
    } else {
        db_query("UPDATE payruns SET status = 'approved', approved_by = " . (int)$_SESSION['user_id'] . ", approved_at = NOW() WHERE id = $id");
        audit_log('approve', 'payrun', $id, 'Approved pay run');
        set_flash('Pay run approved.', 'success');
    }
} elseif ($action == 'pay') {
    if ($run['status'] != 'approved') {
        set_flash('Only an approved pay run can be marked as paid.', 'error');
    } else {
        db_query("UPDATE payruns SET status = 'paid', paid_at = NOW() WHERE id = $id");
        audit_log('pay', 'payrun', $id, 'Marked pay run as paid');
        set_flash('Pay run marked as paid. It is now locked.', 'success');
    }
} else {
    set_flash('Unknown action.', 'error');
}
redirect('payrun_view.php?id=' . $id);
