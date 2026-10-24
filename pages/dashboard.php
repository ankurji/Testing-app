<?php
declare(strict_types=1);

$today = date('Y-m-d');
[$wFrom, $wTo] = periodRange('week', $today);
[$mFrom, $mTo, $mLabel] = periodRange('month', $today);

$spentToday = sumBetween('expenses', $today, $today);
$spentWeek = sumBetween('expenses', $wFrom, $wTo);
$spentMonth = sumBetween('expenses', $mFrom, $mTo);
$incomeMonth = sumBetween('income', $mFrom, $mTo);
$net = $incomeMonth - $spentMonth;

$emiDue = (int)db()->query('SELECT COALESCE(SUM(emi),0) FROM loans')->fetchColumn();
$loanRemaining = (int)db()->query(
    'SELECT COALESCE(SUM(MAX(l.emi * l.tenure_months - COALESCE(p.paid,0), 0)),0)
     FROM loans l LEFT JOIN (SELECT loan_id, SUM(amount) paid FROM loan_payments GROUP BY loan_id) p ON p.loan_id = l.id'
)->fetchColumn();
$goalSaved = (int)db()->query('SELECT COALESCE(SUM(saved),0) FROM goals')->fetchColumn();

$st = db()->prepare('SELECT category, SUM(amount) total FROM expenses WHERE date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC');
$st->execute([$mFrom, $mTo]);
$cats = $st->fetchAll();

header_('Dashboard', 'dashboard');
?>
<h1>Overview · <?= h($mLabel) ?></h1>
<div class="grid">
    <div class="stat"><small>Spent today</small><b><?= money($spentToday) ?></b></div>
    <div class="stat"><small>Spent this week</small><b><?= money($spentWeek) ?></b></div>
    <div class="stat"><small>Spent this month</small><b><?= money($spentMonth) ?></b></div>
    <div class="stat"><small>Income this month</small><b class="good"><?= money($incomeMonth) ?></b></div>
    <div class="stat"><small>Net this month</small><b class="<?= $net >= 0 ? 'good' : 'bad' ?>"><?= money($net) ?></b></div>
</div>
<div class="grid">
    <div class="stat"><small>Total monthly EMIs</small><b><?= money($emiDue) ?></b></div>
    <div class="stat"><small>Loans remaining</small><b class="bad"><?= money($loanRemaining) ?></b></div>
    <div class="stat"><small>Saved toward goals</small><b class="good"><?= money($goalSaved) ?></b></div>
</div>

<div class="card">
    <h2>Spending by category (this month)</h2>
    <?php if (!$cats): ?>
        <p class="mute">No expenses yet this month.</p>
    <?php else: foreach ($cats as $c):
        $pct = $spentMonth > 0 ? (int)round($c['total'] / $spentMonth * 100) : 0; ?>
        <div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between"><span><?= h($c['category']) ?></span><span><?= money((int)$c['total']) ?> · <?= $pct ?>%</span></div>
            <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
        </div>
    <?php endforeach; endif ?>
</div>
<?php footer_();
