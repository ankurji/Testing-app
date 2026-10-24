<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost();
    if (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM income WHERE id = ?')->execute([(int)$_POST['id']]);
        flash('Income deleted.');
    } else {
        $amount = toMinor($_POST['amount'] ?? 0);
        $source = trim($_POST['source'] ?? '');
        if ($amount > 0 && $source !== '') {
            db()->prepare('INSERT INTO income (date, amount, source, note) VALUES (?,?,?,?)')
                ->execute([validDate($_POST['date'] ?? ''), $amount, $source, trim($_POST['note'] ?? '')]);
            flash('Income added.');
        } else {
            flash('Source and a positive amount are required.');
        }
    }
    redirect($_SERVER['REQUEST_URI']);
}

$period = in_array($_GET['period'] ?? '', ['day', 'week', 'month', 'year'], true) ? $_GET['period'] : 'month';
$date = validDate($_GET['date'] ?? date('Y-m-d'));
[$from, $to] = periodRange($period, $date);

$st = db()->prepare('SELECT * FROM income WHERE date BETWEEN ? AND ? ORDER BY date DESC, id DESC');
$st->execute([$from, $to]);
$rows = $st->fetchAll();

header_('Income', 'income');
?>
<h1>Income</h1>
<div class="card">
    <form method="post" class="row">
        <?= csrfField() ?>
        <label>Date <input type="date" name="date" value="<?= h(date('Y-m-d')) ?>" required></label>
        <label>Amount <input type="number" name="amount" step="0.01" min="0.01" required></label>
        <label>Source <input name="source" placeholder="Salary, freelance…" maxlength="100" required></label>
        <label>Note <input name="note" maxlength="200"></label>
        <button>Add income</button>
    </form>
</div>

<?php periodNav('income', $period, $date) ?>
<div class="grid"><div class="stat"><small>Total income</small><b><?= money((int)array_sum(array_column($rows, 'amount'))) ?></b></div></div>

<div class="card">
    <?php if (!$rows): ?>
        <p class="mute">No income in this period.</p>
    <?php else: ?>
        <table>
            <tr><th>Date</th><th>Source</th><th>Note</th><th class="num">Amount</th><th></th></tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['date']) ?></td><td><?= h($r['source']) ?></td><td><?= h($r['note']) ?></td>
                    <td class="num"><?= money((int)$r['amount']) ?></td><td><?= deleteForm('income', (int)$r['id']) ?></td>
                </tr>
            <?php endforeach ?>
        </table>
    <?php endif ?>
</div>
<?php footer_();
