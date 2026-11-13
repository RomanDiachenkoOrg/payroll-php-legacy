<?php
include 'includes/functions.php';
require_role(array('admin'));

$id = (int)get('id');
$dept = array('name' => '', 'code' => '', 'description' => '');
$errors = array();

if ($id) {
    $dept = db_fetch_one('SELECT * FROM departments WHERE id = ' . $id);
    if (!$dept) {
        set_flash('Department not found.', 'error');
        redirect('departments.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dept['name'] = post('name');
    $dept['code'] = strtoupper(post('code'));
    $dept['description'] = post('description');

    if ($dept['name'] == '') {
        $errors[] = 'Name is required.';
    } elseif (db_value("SELECT COUNT(*) FROM departments WHERE name = '" . esc($dept['name']) . "' AND id <> $id")) {
        $errors[] = 'A department with this name already exists.';
    }
    if ($dept['code'] == '') {
        $errors[] = 'Code is required.';
    } elseif (strlen($dept['code']) > 10) {
        $errors[] = 'Code can be at most 10 characters.';
    } elseif (db_value("SELECT COUNT(*) FROM departments WHERE code = '" . esc($dept['code']) . "' AND id <> $id")) {
        $errors[] = 'This code is already in use.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = db_prepared('UPDATE departments SET name = ?, code = ?, description = ? WHERE id = ?', 'sssi', array($dept['name'], $dept['code'], $dept['description'], $id));
            mysqli_stmt_close($stmt);
            audit_log('update', 'department', $id, 'Updated department ' . $dept['name']);
        } else {
            $stmt = db_prepared('INSERT INTO departments (name, code, description, created_at) VALUES (?, ?, ?, NOW())', 'sss', array($dept['name'], $dept['code'], $dept['description']));
            $id = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);
            audit_log('create', 'department', $id, 'Created department ' . $dept['name']);
        }
        set_flash('Department saved.', 'success');
        redirect('departments.php');
    }
}

$page_title = $id ? 'Edit Department' : 'Add Department';
include 'header.php';
?>
<p class="breadcrumb"><a href="departments.php">Departments</a> &raquo; <?php echo $id ? h($dept['name']) : 'New'; ?></p>
<h1><?php echo $page_title; ?></h1>
<?php if ($errors) { ?><div class="msg msg-error"><?php echo implode('<br />', array_map('h', $errors)); ?></div><?php } ?>
<form method="post" action="department_edit.php<?php echo $id ? '?id=' . $id : ''; ?>">
<table class="form">
  <tr><th><label for="name">Name *</label></th><td><input type="text" name="name" id="name" value="<?php echo h($dept['name']); ?>" size="30" /></td></tr>
  <tr><th><label for="code">Code *</label></th><td><input type="text" name="code" id="code" value="<?php echo h($dept['code']); ?>" size="10" maxlength="10" /></td></tr>
  <tr><th><label for="description">Description</label></th><td><textarea name="description" id="description" rows="3" cols="40"><?php echo h($dept['description']); ?></textarea></td></tr>
  <tr><td></td><td><input type="submit" value="Save" class="button" /> <a href="departments.php">Cancel</a></td></tr>
</table>
</form>
<?php include 'footer.php'; ?>
