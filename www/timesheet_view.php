<?php
include 'includes/functions.php';
include 'includes/timesheet_lib.php';
require_login();

$id = (int)get('id');
$ts = load_timesheet($id);
if (!$ts || !can_view_timesheet($ts)) {
    set_flash('Timesheet not found.', 'error');
    redirect('timesheets.php');
}
list($regular, $overtime) = split_week_hours($ts['total']);

$page_title = 'Timesheet';
include 'header.php';
?>
<p class="breadcrumb"><a href="timesheets.php">Timesheets</a> &raquo; <?php echo h($ts['first_name'] . ' ' . $ts['last_name']); ?>, week of <?php echo fmt_date($ts['week_start']); ?></p>
<h1>Week of <?php echo fmt_date($ts['week_start']); ?> <?php echo status_label($ts['status']); ?></h1>
<p><?php echo h($ts['first_name'] . ' ' . $ts['last_name']); ?> &middot; <?php echo h($ts['department_name']); ?></p>

<?php if ($ts['status'] == 'rejected' && $ts['reject_reason']) { ?>
<div class="msg msg-error"><b>Rejected:</b> <?php echo h($ts['reject_reason']); ?></div>
<?php } ?>

<table class="grid" id="timesheet-days">
  <tr><th>Day</th><th>Date</th><th class="num">Hours</th></tr>
  <?php $i = 0; foreach ($ts['hours'] as $date => $hrs) { ?>
  <tr class="<?php echo $i++ % 2 ? 'odd' : 'even'; ?>"><td><?php echo date('l', strtotime($date)); ?></td><td><?php echo fmt_date($date); ?></td><td class="num"><?php echo number_format($hrs, 2); ?></td></tr>
  <?php } ?>
  <tr class="total"><td colspan="2"><b>Total</b></td><td class="num"><b><?php echo number_format($ts['total'], 2); ?></b></td></tr>
  <tr><td colspan="2">Regular / overtime</td><td class="num"><?php echo number_format($regular, 2); ?> / <?php echo number_format($overtime, 2); ?></td></tr>
</table>
<?php if ($ts['notes']) { ?><p><b>Notes:</b> <?php echo h($ts['notes']); ?></p><?php } ?>
<?php if ($ts['reviewer_name']) { ?><p class="small">Reviewed by <?php echo h($ts['reviewer_name']); ?> on <?php echo fmt_date($ts['reviewed_at']); ?></p><?php } ?>

<div class="actions">
<?php if (can_edit_timesheet($ts)) { ?>
  <a href="timesheet_edit.php?id=<?php echo $id; ?>" class="button">Edit</a>
  <form method="post" action="timesheet_action.php" class="inline">
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    <input type="hidden" name="action" value="submit" />
    <input type="submit" value="Submit for Approval" class="button" id="submit-timesheet" />
  </form>
<?php } ?>
<?php if (can_review_timesheet($ts)) { ?>
  <form method="post" action="timesheet_action.php" class="inline">
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    <input type="hidden" name="action" value="approve" />
    <input type="submit" value="Approve" class="button button-ok" id="approve-timesheet" />
  </form>
  <a href="#" class="button button-danger" id="reject-toggle">Reject...</a>
  <form method="post" action="timesheet_action.php" id="reject-form" style="display: none;">
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    <input type="hidden" name="action" value="reject" />
    <p><label for="reason">Reason for rejection:</label><br />
    <textarea name="reason" id="reason" rows="3" cols="50"></textarea></p>
    <input type="submit" value="Reject Timesheet" class="button button-danger" id="confirm-reject" />
  </form>
<?php } ?>
</div>

<script type="text/javascript">
$('#reject-toggle').click(function () {
    $('#reject-form').slideToggle();
    return false;
});
$('#reject-form').submit(function () {
    if ($.trim($('#reason').val()) == '') {
        alert('Please enter a reason.');
        return false;
    }
});
</script>
<?php include 'footer.php'; ?>
