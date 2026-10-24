<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM loans WHERE id = ?')->execute([(int)$_POST['id']]);
        flash('Loan deleted.');
    } elseif ($action === 'payment') {
        $amount = toMinor($_POST['amount'] ?? 0);
        if ($amount > 0) {
            db()->prepare('INSERT INTO loan_payments (loan_id, date, amount) VALUES (?,?,?)')
                ->execute([(int)$_POST['loan_id'], validDate($_POST['date'] ?? ''), $amount]);
            flash('Payment recorded.');
        }
    } else {
        $principal = toMinor($_POST['principal'] ?? 0);
        $rate = max(0.0, (float)($_POST['rate'] ?? 0));
        $months = (int)($_POST['tenure_months'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($principal > 0 && $months > 0 && $name !== '') {
            $emi = calcEmi($principal, $rate, $months);
            db()->prepare('INSERT INTO loans (name, lender, principal, rate, tenure_months, start_date, emi) VALUES (?,?,?,?,?,?,?)')
                ->execute([$name, trim($_POST['lender'] ?? ''), $principal, $rate, $months, validDate($_POST['start_date'] ?? ''), $emi]);
            flash('Loan added. EMI calculated: ' . money($emi));
        } else {
            flash('Name, principal and tenure are required.');
        }
    }
    redirect('index.php?page=loans');
}

$loans = db()->query(
    'SELECT l.*, COALESCE((SELECT SUM(amount) FROM loan_payments WHERE loan_id = l.id), 0) AS paid
     FROM loans l ORDER BY l.start_date DESC'
)->fetchAll();

header_('Loans', 'loans');
?>
<h1>Loans</h1>
<div class="card">
    <h2>Add a loan</h2>
    <form method="post" class="row">
        <?= csrfField() ?>
        <label>Name <input name="name" placeholder="Home loan" required maxlength="100"></label>
        <label>Lender <input name="lender" maxlength="100"></label>
        <label>Principal <input type="number" name="principal" step="0.01" min="1" required></label>
        <label>Rate % p.a. <input type="number" name="rate" step="0.01" min="0" value="0" required></label>
        <label>Tenure (months) <input type="number" name="tenure_months" min="1" max="600" required></label>
        <label>Start date <input type="date" name="start_date" value="<?= h(date('Y-m-d')) ?>" required></label>
        <button>Add loan</button>
    </form>
</div>

<?php if (!$loans): ?>
    <p class="mute">No loans yet.</p>
<?php endif ?>

<?php foreach ($loans as $l):
    $totalPayable = (int)$l['emi'] * (int)$l['tenure_months'];
    $paid = (int)$l['paid'];
    $remaining = max(0, $totalPayable - $paid);
    $pct = $totalPayable > 0 ? min(100, (int)round($paid / $totalPayable * 100)) : 0;
    $pay = db()->prepare('SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY date DESC, id DESC LIMIT 5');
    $pay->execute([$l['id']]);
    ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <h2><?= h($l['name']) ?> <span class="mute"><?= h($l['lender']) ?></span></h2>
            <?= deleteForm('loans', (int)$l['id'], 'Delete this loan and all its payments?') ?>
        </div>
        <div class="grid">
            <div class="stat"><small>Principal @ <?= h((string)$l['rate']) ?>% / <?= (int)$l['tenure_months'] ?> mo</small><b><?= money((int)$l['principal']) ?></b></div>
            <div class="stat"><small>Monthly EMI</small><b><?= money((int)$l['emi']) ?></b></div>
            <div class="stat"><small>Paid so far</small><b class="good"><?= money($paid) ?></b></div>
            <div class="stat"><small>Remaining (EMI × tenure − paid)</small><b class="bad"><?= money($remaining) ?></b></div>
        </div>
        <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
        <p class="mute"><?= $pct ?>% repaid · started <?= h($l['start_date']) ?></p>

        <form method="post" class="row">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="payment"><input type="hidden" name="loan_id" value="<?= (int)$l['id'] ?>">
            <label>Date <input type="date" name="date" value="<?= h(date('Y-m-d')) ?>" required></label>
            <label>Payment <input type="number" name="amount" step="0.01" min="0.01" value="<?= $l['emi'] / 100 ?>" required></label>
            <button>Record payment</button>
        </form>
        <?php if ($recent = $pay->fetchAll()): ?>
            <table>
                <tr><th>Recent payments</th><th class="num">Amount</th></tr>
                <?php foreach ($recent as $p): ?>
                    <tr><td><?= h($p['date']) ?></td><td class="num"><?= money((int)$p['amount']) ?></td></tr>
                <?php endforeach ?>
            </table>
        <?php endif ?>
    </div>
<?php endforeach ?>
<?php footer_();
