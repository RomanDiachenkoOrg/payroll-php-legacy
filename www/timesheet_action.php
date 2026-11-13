<?php
include 'includes/functions.php';
include 'includes/timesheet_lib.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('timesheets.php');
}

$id = (int)post('id');
$action = post('action');
$ts = load_timesheet($id);
if (!$ts || !can_view_timesheet($ts)) {
    set_flash('Timesheet not found.', 'error');
    redirect('timesheets.php');
}

switch ($action) {
    case 'submit':
        if (!can_edit_timesheet($ts)) {
            set_flash('This timesheet cannot be submitted.', 'error');
        } elseif ($ts['total'] <= 0) {
            set_flash('Please enter some hours before submitting.', 'error');
        } elseif ($ts['employee_status'] != 'active') {
            set_flash('Timesheets can only be submitted for active employees.', 'error');
        } else {
            db_query("UPDATE timesheets SET status = 'submitted', submitted_at = NOW(), reject_reason = NULL WHERE id = $id");
            audit_log('submit', 'timesheet', $id, 'Submitted timesheet');
            set_flash('Timesheet submitted for approval.', 'success');
        }
        break;

    case 'approve':
        if (!can_review_timesheet($ts)) {
            set_flash('You cannot approve this timesheet.', 'error');
        } else {
            db_query("UPDATE timesheets SET status = 'approved', reviewed_by = " . (int)$_SESSION['user_id'] . ", reviewed_at = NOW() WHERE id = $id");
            audit_log('approve', 'timesheet', $id, 'Approved timesheet of ' . $ts['first_name'] . ' ' . $ts['last_name']);
            set_flash('Timesheet approved.', 'success');
            redirect('timesheets.php?status=submitted');
        }
        break;

    case 'reject':
        $reason = post('reason');
        if (!can_review_timesheet($ts)) {
            set_flash('You cannot reject this timesheet.', 'error');
        } elseif ($reason == '') {
            set_flash('A reason is required to reject a timesheet.', 'error');
        } else {
            $stmt = db_prepared("UPDATE timesheets SET status = 'rejected', reject_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?", 'sii', array($reason, $_SESSION['user_id'], $id));
            mysqli_stmt_close($stmt);
            audit_log('reject', 'timesheet', $id, 'Rejected timesheet of ' . $ts['first_name'] . ' ' . $ts['last_name'] . ': ' . $reason);
            set_flash('Timesheet rejected.', 'success');
            redirect('timesheets.php?status=submitted');
        }
        break;

    default:
        set_flash('Unknown action.', 'error');
}
redirect('timesheet_view.php?id=' . $id);
