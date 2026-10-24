<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partials.php';

$pages = ['dashboard', 'expenses', 'income', 'loans', 'goals'];
$page = in_array($_GET['page'] ?? '', $pages, true) ? $_GET['page'] : 'dashboard';

require __DIR__ . "/pages/$page.php";
