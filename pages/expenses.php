<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost();
    if (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM expenses WHERE id = ?')->execute([(int)$_POST['id']]);
        flash('Expense deleted.');
    } else {
        $amount = toMinor($_POST['amount'] ?? 0);
        $category = in_array($_POST['category'] ?? '', CATEGORIES, true) ? $_POST['category'] : 'Other';
        if ($amount > 0) {
            db()->prepare('INSERT INTO expenses (date, amount, category, note) VALUES (?,?,?,?)')
                ->execute([validDate($_POST['date'] ?? ''), $amount, $category, trim($_POST['note'] ?? '')]);
            flash('Expense added.');
        } else {
            flash('Amount must be greater than zero.');
        }
    }
    redirect($_SERVER['REQUEST_URI']);
}

$period = in_array($_GET['period'] ?? '', ['day', 'week', 'month', 'year'], true) ? $_GET['period'] : 'month';
$date = validDate($_GET['date'] ?? date('Y-m-d'));
[$from, $to] = periodRange($period, $date);

$st = db()->prepare('SELECT * FROM expenses WHERE date BETWEEN ? AND ? ORDER BY date DESC, id DESC');
$st->execute([$from, $to]);
$rows = $st->fetchAll();
$total = array_sum(array_column($rows, 'amount'));

$byCat = [];
foreach ($rows as $r) {
    $byCat[$r['category']] = ($byCat[$r['category']] ?? 0) + $r['amount'];
}
arsort($byCat);

header_('Expenses', 'expenses');
?>
<h1>Expenses</h1>
<div class="card">
    <form method="post" class="row">
        <?= csrfField() ?>
        <label>Date <input type="date" name="date" value="<?= h(date('Y-m-d')) ?>" required></label>
        <label>Amount <input type="number" name="amount" step="0.01" min="0.01" required></label>
        <label>Category
            <select name="category"><?php foreach (CATEGORIES as $c): ?><option><?= h($c) ?></option><?php endforeach ?></select>
        </label>
        <label>Note <input name="note" maxlength="200"></label>
        <button>Add expense</button>
    </form>
</div>

<?php periodNav('expenses', $period, $date) ?>
<div class="grid">
    <div class="stat"><small>Total spent</small><b><?= money($total) ?></b></div>
    <div class="stat"><small>Entries</small><b><?= count($rows) ?></b></div>
    <?php foreach (array_slice($byCat, 0, 2, true) as $cat => $sum): ?>
        <div class="stat"><small><?= h($cat) ?></small><b><?= money($sum) ?></b></div>
    <?php endforeach ?>
</div>

<div class="card">
    <?php if (!$rows): ?>
        <p class="mute">No expenses in this period.</p>
    <?php else: ?>
        <table>
            <tr><th>Date</th><th>Category</th><th>Note</th><th class="num">Amount</th><th></th></tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['date']) ?></td><td><?= h($r['category']) ?></td><td><?= h($r['note']) ?></td>
                    <td class="num"><?= money((int)$r['amount']) ?></td><td><?= deleteForm('expenses', (int)$r['id']) ?></td>
                </tr>
            <?php endforeach ?>
        </table>
    <?php endif ?>
</div>
<?php footer_();
