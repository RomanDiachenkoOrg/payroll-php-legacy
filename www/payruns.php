<?php
include 'includes/functions.php';
require_role(array('admin'));

$runs = db_fetch_all('SELECT r.*, COUNT(p.id) AS slips, COALESCE(SUM(p.gross), 0) AS gross, COALESCE(SUM(p.net), 0) AS net
                        FROM payruns r LEFT JOIN payslips p ON p.payrun_id = r.id
                       GROUP BY r.id ORDER BY r.period_start DESC');

$page_title = 'Pay Runs';
include 'header.php';
?>
<h1>Pay Runs</h1>
<p><a href="payrun_new.php" class="button" id="new-payrun">+ New Pay Run</a></p>
<table class="grid" width="100%" id="payrun-table">
  <tr><th>Period</th><th>Pay date</th><th>Status</th><th class="num">Payslips</th><th class="num">Gross</th><th class="num">Net</th></tr>
  <?php foreach ($runs as $i => $r) { ?>
  <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
    <td><a href="payrun_view.php?id=<?php echo $r['id']; ?>"><?php echo fmt_date($r['period_start']); ?> - <?php echo fmt_date($r['period_end']); ?></a></td>
    <td><?php echo fmt_date($r['pay_date']); ?></td>
    <td><?php echo status_label($r['status']); ?><?php if ($r['status'] == 'paid') echo ' <span class="locked">(locked)</span>'; ?></td>
    <td class="num"><?php echo (int)$r['slips']; ?></td>
    <td class="num"><?php echo format_money($r['gross']); ?></td>
    <td class="num"><?php echo format_money($r['net']); ?></td>
  </tr>
  <?php } ?>
  <?php if (!$runs) { ?><tr><td colspan="6" class="empty">No pay runs yet.</td></tr><?php } ?>
</table>
<?php include 'footer.php'; ?>
