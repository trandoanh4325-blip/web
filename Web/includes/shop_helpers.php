<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

function ensure_logged_in(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../Login.html');
        exit();
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_vnd(float $amount): string
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

function get_cart(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function set_cart(array $cart): void
{
    $_SESSION['cart'] = $cart;
}
?>
