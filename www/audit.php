<?php
include 'includes/functions.php';
require_role(array('admin'));

$action = get('action');
$user_id = (int)get('user_id');
$from = get('from');
$to = get('to');
$q = get('q');
$page = max(1, (int)get('page', 1));
$per_page = 25;

$where = '1=1';
if ($action != '') $where .= " AND a.action = '" . esc($action) . "'";
if ($user_id) $where .= ' AND a.user_id = ' . $user_id;
if (valid_date($from)) $where .= " AND a.created_at >= '$from 00:00:00'";
if (valid_date($to)) $where .= " AND a.created_at <= '$to 23:59:59'";
if ($q != '') $where .= " AND a.details LIKE '%" . esc($q) . "%'";

$total = db_value("SELECT COUNT(*) FROM audit_log a WHERE $where");
$pages = max(1, ceil($total / $per_page));
$offset = ($page - 1) * $per_page;
$rows = db_fetch_all("SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.user_id WHERE $where ORDER BY a.created_at DESC, a.id DESC LIMIT $offset, $per_page");
$actions = db_fetch_all('SELECT DISTINCT action FROM audit_log ORDER BY action');
$users = db_fetch_all('SELECT id, email FROM users ORDER BY email');

$page_title = 'Audit Log';
include 'header.php';
?>
<h1>Audit Log</h1>
<form method="get" action="audit.php" class="filter" id="audit-filter">
  <label for="action">Action:</label>
  <select name="action" id="action"><option value="">All</option>
    <?php foreach ($actions as $a) { ?><option value="<?php echo h($a['action']); ?>"<?php if ($a['action'] == $action) echo ' selected="selected"'; ?>><?php echo h($a['action']); ?></option><?php } ?>
  </select>
  <label for="user_id">User:</label>
  <select name="user_id" id="user_id"><option value="">All</option>
    <?php foreach ($users as $u) { ?><option value="<?php echo $u['id']; ?>"<?php if ($u['id'] == $user_id) echo ' selected="selected"'; ?>><?php echo h($u['email']); ?></option><?php } ?>
  </select>
  <label for="from">From:</label> <input type="text" name="from" id="from" value="<?php echo h($from); ?>" size="10" />
  <label for="to">To:</label> <input type="text" name="to" id="to" value="<?php echo h($to); ?>" size="10" />
  <label for="q">Text:</label> <input type="text" name="q" id="q" value="<?php echo h($q); ?>" size="15" />
  <input type="submit" value="Filter" class="button" />
</form>
<table class="grid" width="100%" id="audit-table">
  <tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th></tr>
  <?php foreach ($rows as $i => $r) { ?>
  <tr class="<?php echo $i % 2 ? 'odd' : 'even'; ?>">
    <td nowrap="nowrap" class="small"><?php echo date('m/d/Y H:i', strtotime($r['created_at'])); ?></td>
    <td class="small"><?php echo h($r['email']); ?></td>
    <td><?php echo h($r['action']); ?></td>
    <td class="small"><?php echo h($r['entity']); ?><?php if ($r['entity_id'] !== '') echo ' #' . h($r['entity_id']); ?></td>
    <td class="small"><?php echo h($r['details']); ?></td>
  </tr>
  <?php } ?>
  <?php if (!$rows) { ?><tr><td colspan="5" class="empty">No entries.</td></tr><?php } ?>
</table>
<div class="pager">
  <?php echo (int)$total; ?> entries &nbsp;|&nbsp; Page <?php echo $page; ?> of <?php echo $pages; ?>
  <?php if ($page > 1) { ?><a href="<?php echo h(qs(array('page' => $page - 1))); ?>">&laquo; Prev</a><?php } ?>
  <?php if ($page < $pages) { ?><a href="<?php echo h(qs(array('page' => $page + 1))); ?>">Next &raquo;</a><?php } ?>
</div>
<?php include 'footer.php'; ?>
