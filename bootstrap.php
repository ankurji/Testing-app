<?php
declare(strict_types=1);

const CURRENCY = '₹';
const CATEGORIES = ['Food', 'Groceries', 'Rent', 'Utilities', 'Transport', 'Health', 'Shopping', 'Entertainment', 'Education', 'Travel', 'Other'];

session_start();

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0775, true);
    }
    $pdo = new PDO('sqlite:' . __DIR__ . '/data/finance.sqlite', options: [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS expenses (
            id INTEGER PRIMARY KEY, date TEXT NOT NULL, amount INTEGER NOT NULL,
            category TEXT NOT NULL, note TEXT NOT NULL DEFAULT ''
        );
        CREATE TABLE IF NOT EXISTS income (
            id INTEGER PRIMARY KEY, date TEXT NOT NULL, amount INTEGER NOT NULL,
            source TEXT NOT NULL, note TEXT NOT NULL DEFAULT ''
        );
        CREATE TABLE IF NOT EXISTS loans (
            id INTEGER PRIMARY KEY, name TEXT NOT NULL, lender TEXT NOT NULL DEFAULT '',
            principal INTEGER NOT NULL, rate REAL NOT NULL, tenure_months INTEGER NOT NULL,
            start_date TEXT NOT NULL, emi INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS loan_payments (
            id INTEGER PRIMARY KEY, loan_id INTEGER NOT NULL REFERENCES loans(id) ON DELETE CASCADE,
            date TEXT NOT NULL, amount INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS goals (
            id INTEGER PRIMARY KEY, name TEXT NOT NULL, target INTEGER NOT NULL,
            saved INTEGER NOT NULL DEFAULT 0, deadline TEXT
        );
        SQL);
    return $pdo;
}

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Stored as integer minor units (paise/cents) to avoid float drift. */
function toMinor(string|float $v): int
{
    return (int)round((float)$v * 100);
}

function money(int $minor): string
{
    return CURRENCY . number_format($minor / 100, 2);
}

function csrfToken(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrfToken(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid request.');
    }
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

function validDate(string $d): string
{
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d ? $d : date('Y-m-d');
}

/** @return array{0:string,1:string,2:string} [from, to, label], inclusive range for day|week|month|year. */
function periodRange(string $period, string $date): array
{
    $d = new DateTimeImmutable(validDate($date));
    return match ($period) {
        'day' => [$d->format('Y-m-d'), $d->format('Y-m-d'), $d->format('D, d M Y')],
        'week' => [
            $from = $d->modify('monday this week')->format('Y-m-d'),
            $to = $d->modify('sunday this week')->format('Y-m-d'),
            date('d M', strtotime($from)) . ' – ' . date('d M Y', strtotime($to)),
        ],
        'year' => [$d->format('Y-01-01'), $d->format('Y-12-31'), $d->format('Y')],
        default => [$d->format('Y-m-01'), $d->format('Y-m-t'), $d->format('F Y')],
    };
}

function sumBetween(string $table, string $from, string $to): int
{
    $st = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM $table WHERE date BETWEEN ? AND ?");
    $st->execute([$from, $to]);
    return (int)$st->fetchColumn();
}

function calcEmi(int $principalMinor, float $annualRate, int $months): int
{
    if ($months <= 0) {
        return $principalMinor;
    }
    $r = $annualRate / 12 / 100;
    if ($r == 0.0) {
        return (int)round($principalMinor / $months);
    }
    $f = (1 + $r) ** $months;
    return (int)round($principalMinor * $r * $f / ($f - 1));
}
