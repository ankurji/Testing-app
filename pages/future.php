<?php
declare(strict_types=1);

$roadmap = [
    'Planned' => [
        ['Monthly budgets', 'Set a limit per category and get warned when you are close to it.'],
        ['Recurring bills', 'Auto-add rent, subscriptions and EMIs on their due dates.'],
        ['CSV export and import', 'Download your data or load it from a bank statement.'],
        ['Login and user accounts', 'Password protection so the app is safe to host online.'],
    ],
    'Ideas' => [
        ['Charts and trends', 'Month-over-month graphs of spending and income.'],
        ['Loan amortization schedule', 'A true interest-aware outstanding balance and payoff date.'],
        ['Multi-currency support', 'Track money in more than one currency.'],
        ['Reminders', 'Email or browser alerts for upcoming EMIs and goal deadlines.'],
        ['Mobile app view', 'An installable, phone-friendly version of the app.'],
    ],
];

header_('Future development', 'future');
?>
<h1>Future development</h1>
<p class="mute">Features I'm considering for upcoming versions of My Finances.</p>
<?php foreach ($roadmap as $stage => $items): ?>
    <div class="card">
        <h2><?= h($stage) ?></h2>
        <table>
            <?php foreach ($items as [$title, $desc]): ?>
                <tr><td><strong><?= h($title) ?></strong></td><td class="mute"><?= h($desc) ?></td></tr>
            <?php endforeach ?>
        </table>
    </div>
<?php endforeach ?>
<?php footer_();
