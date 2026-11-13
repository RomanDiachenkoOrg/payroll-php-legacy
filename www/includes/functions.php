<?php
/* common helper functions */

require_once dirname(__FILE__) . '/../config.php';
require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/payroll_calc.php';

session_start();

function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function post($name, $default = '')
{
    return isset($_POST[$name]) ? trim($_POST[$name]) : $default;
}

function get($name, $default = '')
{
    return isset($_GET[$name]) ? trim($_GET[$name]) : $default;
}

function set_flash($msg, $type = 'info')
{
    $_SESSION['flash'] = array('msg' => $msg, 'type' => $type);
}

function show_flash()
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="msg msg-' . h($f['type']) . '" id="flash">' . h($f['msg']) . '</div>';
    }
}

function is_logged_in()
{
    return !empty($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

function user_role()
{
    return isset($_SESSION['role']) ? $_SESSION['role'] : '';
}

function is_admin()
{
    return user_role() == 'admin';
}

function is_manager()
{
    return user_role() == 'manager';
}

/* $roles = array('admin', 'manager') */
function require_role($roles)
{
    require_login();
    if (!in_array(user_role(), $roles)) {
        header('HTTP/1.1 403 Forbidden');
        $page_title = 'Access denied';
        include dirname(__FILE__) . '/../header.php';
        echo '<h1>Access denied</h1><p>You do not have permission to view this page.</p>';
        echo '<p><a href="dashboard.php">Back to dashboard</a></p>';
        include dirname(__FILE__) . '/../footer.php';
        exit;
    }
}

function my_employee_id()
{
    return isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : 0;
}

function my_department_id()
{
    return isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : 0;
}

function audit_log($action, $entity, $entity_id, $details)
{
    $uid = is_logged_in() ? (int)$_SESSION['user_id'] : null;
    $stmt = db_prepared(
        'INSERT INTO audit_log (created_at, user_id, action, entity, entity_id, details) VALUES (NOW(), ?, ?, ?, ?, ?)',
        'issss',
        array($uid, $action, $entity, (string)$entity_id, $details)
    );
    mysqli_stmt_close($stmt);
}

function get_setting($name, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = array();
        foreach (db_fetch_all('SELECT name, value FROM settings') as $r) {
            $cache[$r['name']] = $r['value'];
        }
    }
    return isset($cache[$name]) ? $cache[$name] : $default;
}

function get_tax_brackets()
{
    $rows = db_fetch_all('SELECT lower_bound, upper_bound, rate FROM tax_brackets ORDER BY lower_bound');
    $out = array();
    foreach ($rows as $r) {
        $out[] = array(
            'lower' => (float)$r['lower_bound'],
            'upper' => $r['upper_bound'] === null ? null : (float)$r['upper_bound'],
            'rate' => (float)$r['rate'],
        );
    }
    return $out;
}

function fmt_date($d)
{
    if (!$d || $d == '0000-00-00') {
        return '';
    }
    return date(DATE_FMT, strtotime($d));
}

function valid_date($d)
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) {
        return false;
    }
    return checkdate($m[2], $m[3], $m[1]);
}

function status_label($status)
{
    return '<span class="status status-' . h($status) . '">' . h(ucfirst($status)) . '</span>';
}

function department_options($selected = '', $empty_label = '-- select --')
{
    $html = '<option value="">' . h($empty_label) . '</option>';
    $rows = db_fetch_all('SELECT id, name FROM departments ORDER BY name');
    foreach ($rows as $d) {
        $sel = ($d['id'] == $selected) ? ' selected="selected"' : '';
        $html .= '<option value="' . $d['id'] . '"' . $sel . '>' . h($d['name']) . '</option>';
    }
    return $html;
}

/* build a query string keeping current GET params */
function qs($overrides = array())
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        }
    }
    return '?' . http_build_query($params);
}

function monday_of($date)
{
    $ts = strtotime($date);
    $dow = (int)date('N', $ts);
    return date('Y-m-d', strtotime('-' . ($dow - 1) . ' days', $ts));
}

function next_pay_date()
{
    $row = db_fetch_one("SELECT pay_date FROM payruns WHERE status <> 'paid' AND pay_date >= CURDATE() ORDER BY pay_date LIMIT 1");
    if ($row) {
        return $row['pay_date'];
    }
    $day = (int)get_setting('pay_day', 28);
    $d = date('Y-m-') . sprintf('%02d', $day);
    if ($d < date('Y-m-d')) {
        $d = date('Y-m-', strtotime('first day of next month')) . sprintf('%02d', $day);
    }
    return $d;
}
