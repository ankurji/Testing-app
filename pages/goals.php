<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM goals WHERE id = ?')->execute([(int)$_POST['id']]);
        flash('Goal deleted.');
    } elseif ($action === 'deposit') {
        $amount = toMinor($_POST['amount'] ?? 0);
        if ($amount > 0) {
            db()->prepare('UPDATE goals SET saved = saved + ? WHERE id = ?')->execute([$amount, (int)$_POST['id']]);
            flash('Savings added.');
        }
    } else {
        $target = toMinor($_POST['target'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($target > 0 && $name !== '') {
            $deadline = ($_POST['deadline'] ?? '') !== '' ? validDate($_POST['deadline']) : null;
            db()->prepare('INSERT INTO goals (name, target, saved, deadline) VALUES (?,?,?,?)')
                ->execute([$name, $target, toMinor($_POST['saved'] ?? 0), $deadline]);
            flash('Goal added.');
        } else {
            flash('Name and a positive target are required.');
        }
    }
    redirect('index.php?page=goals');
}

$goals = db()->query('SELECT * FROM goals ORDER BY id DESC')->fetchAll();

header_('Savings goals', 'goals');
?>
<h1>Savings goals</h1>
<div class="card">
    <form method="post" class="row">
        <?= csrfField() ?>
        <label>Goal <input name="name" placeholder="Emergency fund" required maxlength="100"></label>
        <label>Target <input type="number" name="target" step="0.01" min="1" required></label>
        <label>Already saved <input type="number" name="saved" step="0.01" min="0" value="0"></label>
        <label>Deadline <input type="date" name="deadline"></label>
        <button>Add goal</button>
    </form>
</div>

<?php if (!$goals): ?><p class="mute">No goals yet.</p><?php endif ?>
<?php foreach ($goals as $g):
    $pct = min(100, (int)round($g['saved'] / $g['target'] * 100)); ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <h2><?= h($g['name']) ?></h2>
            <?= deleteForm('goals', (int)$g['id'], 'Delete this goal?') ?>
        </div>
        <p><?= money((int)$g['saved']) ?> of <?= money((int)$g['target']) ?>
            <?= $g['deadline'] ? '<span class="mute">· by ' . h($g['deadline']) . '</span>' : '' ?></p>
        <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
        <p class="mute"><?= $pct ?>%</p>
        <form method="post" class="row">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="deposit"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
            <label>Add savings <input type="number" name="amount" step="0.01" min="0.01" required></label>
            <button>Add</button>
        </form>
    </div>
<?php endforeach ?>
<?php footer_();
