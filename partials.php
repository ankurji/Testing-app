<?php
declare(strict_types=1);

function header_(string $title, string $active): void
{
    $nav = ['dashboard' => 'Dashboard', 'expenses' => 'Expenses', 'income' => 'Income', 'loans' => 'Loans', 'goals' => 'Savings goals', 'developer' => 'Developer'];
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . h($title) . ' · My Finances</title><link rel="stylesheet" href="assets/style.css"></head><body>';
    echo '<header><strong>💰 My Finances</strong><nav>';
    foreach ($nav as $k => $label) {
        echo '<a href="index.php?page=' . $k . '"' . ($k === $active ? ' class="on"' : '') . '>' . h($label) . '</a>';
    }
    echo '</nav></header><main>';
    if ($m = flash()) {
        echo '<p class="flash">' . h($m) . '</p>';
    }
}

function footer_(): void
{
    echo '</main><footer>Developed by <a href="index.php?page=developer">' . h(DEVELOPER_NAME) . '</a></footer></body></html>';
}

function deleteForm(string $page, int $id, string $confirm = 'Delete this entry?'): string
{
    return '<form method="post" action="index.php?page=' . $page . '" onsubmit="return confirm(' . h(json_encode($confirm)) . ')" class="inline">'
        . csrfField() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . $id . '">'
        . '<button class="link danger">Delete</button></form>';
}

function periodNav(string $page, string $period, string $date): void
{
    $unit = ['day' => 'day', 'week' => 'week', 'month' => 'month', 'year' => 'year'][$period] ?? 'month';
    $d = new DateTimeImmutable(validDate($date));
    $prev = $d->modify("-1 $unit")->format('Y-m-d');
    $next = $d->modify("+1 $unit")->format('Y-m-d');
    echo '<div class="tabs">';
    foreach (['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly', 'year' => 'Yearly'] as $k => $label) {
        echo '<a href="?page=' . $page . '&period=' . $k . '&date=' . $date . '"' . ($k === $period ? ' class="on"' : '') . '>' . $label . '</a>';
    }
    echo '<span class="spacer"></span><a href="?page=' . $page . '&period=' . $period . '&date=' . $prev . '">‹</a>'
        . '<strong>' . h(periodRange($period, $date)[2]) . '</strong>'
        . '<a href="?page=' . $page . '&period=' . $period . '&date=' . $next . '">›</a>'
        . '<a href="?page=' . $page . '&period=' . $period . '">Today</a></div>';
}
