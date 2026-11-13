<?php
/* database connection - included by every page */

$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$db) {
    die('<h2>Database connection failed</h2><p>' . htmlspecialchars(mysqli_connect_error()) . '</p>');
}
mysqli_set_charset($db, 'utf8');

/* run a query, die on error (old style but it works) */
function db_query($sql)
{
    global $db;
    $res = mysqli_query($db, $sql);
    if ($res === false) {
        error_log('SQL error: ' . mysqli_error($db) . ' -- ' . $sql);
        die('Database error. Please contact the administrator.');
    }
    return $res;
}

/* fetch all rows as assoc arrays */
function db_fetch_all($sql)
{
    $res = db_query($sql);
    $rows = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = $row;
    }
    mysqli_free_result($res);
    return $rows;
}

function db_fetch_one($sql)
{
    $res = db_query($sql);
    $row = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return $row;
}

function db_value($sql)
{
    $res = db_query($sql);
    $row = mysqli_fetch_row($res);
    mysqli_free_result($res);
    return $row ? $row[0] : null;
}

function esc($str)
{
    global $db;
    return mysqli_real_escape_string($db, $str);
}

/* prepared statement helper: db_prepared("SELECT ... WHERE id=?", "i", array($id)) */
function db_prepared($sql, $types = '', $params = array())
{
    global $db;
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        error_log('Prepare failed: ' . mysqli_error($db) . ' -- ' . $sql);
        die('Database error. Please contact the administrator.');
    }
    if ($types != '') {
        $refs = array();
        foreach ($params as $k => $v) {
            $refs[$k] = &$params[$k];
        }
        array_unshift($refs, $types);
        call_user_func_array(array($stmt, 'bind_param'), $refs);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}
