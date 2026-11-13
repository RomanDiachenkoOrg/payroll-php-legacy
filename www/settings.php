<?php
include 'includes/functions.php';
require_role(array('admin'));

$errors = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company = post('company_name');
    $social = post('social_rate');
    $pension = post('pension_rate');
    $pay_day = post('pay_day');

    if ($company == '') $errors[] = 'Company name is required.';
    if (!is_numeric($social) || $social < 0 || $social > 1) $errors[] = 'Social rate must be between 0 and 1.';
    if (!is_numeric($pension) || $pension < 0 || $pension > 1) $errors[] = 'Pension rate must be between 0 and 1.';
    if (!ctype_digit($pay_day) || $pay_day < 1 || $pay_day > 28) $errors[] = 'Pay day must be between 1 and 28.';

    $brackets = array();
    $lowers = isset($_POST['lower']) ? $_POST['lower'] : array();
    foreach ($lowers as $i => $lower) {
        $lower = trim($lower);
        $upper = trim($_POST['upper'][$i]);
        $rate = trim($_POST['rate'][$i]);
        if ($lower === '' && $rate === '') {
            continue; // empty row
        }
        if (!empty($_POST['delete'][$i])) {
            continue;
        }
        if (!is_numeric($lower) || !is_numeric($rate) || ($upper !== '' && !is_numeric($upper))) {
            $errors[] = 'Bracket ' . ($i + 1) . ': please enter numbers.';
        } elseif ($upper !== '' && $upper <= $lower) {
            $errors[] = 'Bracket ' . ($i + 1) . ': upper bound must be greater than the lower bound.';
        } elseif ($rate < 0 || $rate > 1) {
            $errors[] = 'Bracket ' . ($i + 1) . ': rate must be between 0 and 1.';
        }
        $brackets[] = array($lower, $upper, $rate);
    }
    if (!$brackets) {
        $errors[] = 'At least one tax bracket is required.';
    }

    if (!$errors) {
        foreach (array('company_name' => $company, 'social_rate' => $social, 'pension_rate' => $pension, 'pay_day' => $pay_day) as $k => $v) {
            $stmt = db_prepared('REPLACE INTO settings (name, value) VALUES (?, ?)', 'ss', array($k, $v));
            mysqli_stmt_close($stmt);
        }
        db_query('DELETE FROM tax_brackets');
        foreach ($brackets as $b) {
            db_query('INSERT INTO tax_brackets (lower_bound, upper_bound, rate) VALUES (' . (float)$b[0] . ', ' . ($b[1] === '' ? 'NULL' : (float)$b[1]) . ', ' . (float)$b[2] . ')');
        }
        audit_log('update', 'settings', '', 'Updated tax brackets and rates');
        set_flash('Settings saved.', 'success');
        redirect('settings.php');
    }
}

$brackets = db_fetch_all('SELECT * FROM tax_brackets ORDER BY lower_bound');
$brackets[] = array('lower_bound' => '', 'upper_bound' => '', 'rate' => ''); // blank row for adding

$page_title = 'Tax & Rates';
include 'header.php';
?>
<h1>Tax Brackets &amp; Rates</h1>
<?php if ($errors) { ?><div class="msg msg-error"><?php echo implode('<br />', array_map('h', $errors)); ?></div><?php } ?>
<form method="post" action="settings.php" id="settings-form">
<h2>General</h2>
<table class="form">
  <tr><th><label for="company_name">Company name</label></th><td><input type="text" name="company_name" id="company_name" value="<?php echo h(get_setting('company_name')); ?>" size="30" /></td></tr>
  <tr><th><label for="social_rate">Social contribution rate</label></th><td><input type="text" name="social_rate" id="social_rate" value="<?php echo h(get_setting('social_rate')); ?>" size="8" /> <span class="hint">0.05 = 5%</span></td></tr>
  <tr><th><label for="pension_rate">Pension rate</label></th><td><input type="text" name="pension_rate" id="pension_rate" value="<?php echo h(get_setting('pension_rate')); ?>" size="8" /></td></tr>
  <tr><th><label for="pay_day">Pay day of month</label></th><td><input type="text" name="pay_day" id="pay_day" value="<?php echo h(get_setting('pay_day')); ?>" size="4" /></td></tr>
</table>

<h2>Monthly income tax brackets</h2>
<table class="grid" id="bracket-table">
  <tr><th>From (USD)</th><th>To (USD)</th><th>Rate</th><th>Delete</th></tr>
  <?php foreach ($brackets as $i => $b) { ?>
  <tr>
    <td><input type="text" name="lower[<?php echo $i; ?>]" value="<?php echo h($b['lower_bound']); ?>" size="10" title="From" /></td>
    <td><input type="text" name="upper[<?php echo $i; ?>]" value="<?php echo h($b['upper_bound']); ?>" size="10" title="To (empty = no limit)" /></td>
    <td><input type="text" name="rate[<?php echo $i; ?>]" value="<?php echo h($b['rate']); ?>" size="6" title="Rate" /></td>
    <td align="center"><?php if ($b['lower_bound'] !== '') { ?><input type="checkbox" name="delete[<?php echo $i; ?>]" value="1" title="Delete" /><?php } ?></td>
  </tr>
  <?php } ?>
</table>
<p class="hint">Rate is a fraction (0.15 = 15%). Leave "To" empty for the top bracket. Use the last row to add a bracket.</p>
<p><input type="submit" value="Save Settings" class="button" id="save-settings" /></p>
</form>
<?php include 'footer.php'; ?>
