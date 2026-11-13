<?php
include 'includes/functions.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = strtolower(post('email'));
    $password = post('password');

    if ($email == '' || $password == '') {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = db_prepared(
            'SELECT u.id, u.email, u.password, u.full_name, u.role, u.active, u.employee_id, e.department_id
               FROM users u LEFT JOIN employees e ON e.id = u.employee_id
              WHERE u.email = ?',
            's',
            array($email)
        );
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($user && $user['password'] == md5($password)) {
            if (!$user['active']) {
                $error = 'Your account has been disabled.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['department_id'] = $user['department_id'];
                db_query('UPDATE users SET last_login = NOW() WHERE id = ' . (int)$user['id']);
                audit_log('login', 'user', $user['id'], 'Logged in');

                $next = get('next');
                if ($next != '' && substr($next, 0, 1) == '/' && substr($next, 0, 2) != '//') {
                    redirect($next);
                }
                redirect('dashboard.php');
            }
        } else {
            audit_log('login_failed', 'user', '', 'Failed login for ' . $email);
            $error = 'Invalid email or password.';
        }
    }
}

if (is_logged_in()) {
    redirect('dashboard.php');
}

$page_title = 'Log in';
include 'header.php';
?>
<div id="login-box">
  <h1>Payroll Login</h1>
  <?php if ($error) { ?>
    <div class="msg msg-error" id="login-error"><?php echo h($error); ?></div>
  <?php } ?>
  <form method="post" action="login.php<?php echo get('next') ? '?next=' . urlencode(get('next')) : ''; ?>" id="login-form">
    <table class="form">
      <tr>
        <th><label for="email">Email:</label></th>
        <td><input type="text" name="email" id="email" size="30" value="<?php echo h($email); ?>" /></td>
      </tr>
      <tr>
        <th><label for="password">Password:</label></th>
        <td><input type="password" name="password" id="password" size="30" /></td>
      </tr>
      <tr>
        <td></td>
        <td><input type="submit" value="Log in" class="button" id="login-button" /></td>
      </tr>
    </table>
  </form>
  <p class="hint">Forgot your password? Please contact HR.</p>
</div>
<script type="text/javascript">
$(function () { $('#email').focus(); });
</script>
<?php include 'footer.php'; ?>
