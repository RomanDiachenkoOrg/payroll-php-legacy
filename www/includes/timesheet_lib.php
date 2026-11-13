<?php
/* timesheet helpers */

function load_timesheet($id)
{
    $ts = db_fetch_one('SELECT t.*, e.first_name, e.last_name, e.department_id, e.status AS employee_status, d.name AS department_name,
                               u.full_name AS reviewer_name
                          FROM timesheets t
                          JOIN employees e ON e.id = t.employee_id
                          LEFT JOIN departments d ON d.id = e.department_id
                          LEFT JOIN users u ON u.id = t.reviewed_by
                         WHERE t.id = ' . (int)$id);
    if ($ts) {
        $ts['hours'] = array();
        for ($i = 0; $i < 7; $i++) {
            $ts['hours'][date('Y-m-d', strtotime($ts['week_start'] . " +$i days"))] = 0;
        }
        foreach (db_fetch_all('SELECT work_date, hours FROM timesheet_entries WHERE timesheet_id = ' . (int)$id) as $e) {
            $ts['hours'][$e['work_date']] = (float)$e['hours'];
        }
        $ts['total'] = array_sum($ts['hours']);
    }
    return $ts;
}

function can_view_timesheet($ts)
{
    if (is_admin()) return true;
    if ($ts['employee_id'] == my_employee_id()) return true;
    if (is_manager() && $ts['department_id'] == my_department_id()) return true;
    return false;
}

function can_edit_timesheet($ts)
{
    if (!in_array($ts['status'], array('draft', 'rejected'))) return false;
    return is_admin() || $ts['employee_id'] == my_employee_id();
}

function can_review_timesheet($ts)
{
    if ($ts['status'] != 'submitted') return false;
    if (is_admin()) return true;
    if (!is_manager()) return false;
    if ($ts['employee_id'] == my_employee_id()) return false; // no self approval
    return $ts['department_id'] == my_department_id();
}
