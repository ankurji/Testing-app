<?php
declare(strict_types=1);

header_('Developer', 'developer');
?>
<h1>Developer</h1>
<div class="card" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
    <div class="avatar" aria-hidden="true">AB</div>
    <div>
        <h2 style="margin:0"><?= h(DEVELOPER_NAME) ?></h2>
        <p class="mute" style="margin:0">Developer · My Finances</p>
    </div>
</div>
<div class="card">
    <h2>About this app</h2>
    <p>My Finances is a personal finance tracker built with PHP and SQLite. It covers daily, weekly, monthly and yearly expenses and income, loans with EMI tracking, and savings goals.</p>
    <table>
        <tr><th>Developer</th><td><?= h(DEVELOPER_NAME) ?></td></tr>
        <tr><th>PHP version</th><td><?= h(PHP_VERSION) ?></td></tr>
        <tr><th>Database</th><td>SQLite</td></tr>
    </table>
</div>
<?php footer_();
