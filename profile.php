<?php

require_once "includes/auth.php";
require_once "config/database.php";
require_once "includes/message.php";

$userId = $_SESSION["user_id"];


/* Get user */

$stmt = $pdo->prepare("
    SELECT id, name, email
    FROM users
    WHERE id = ?
");

$stmt->execute([$userId]);

$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit;
}


/* Account activity (read-only) */

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_transactions,
        COALESCE(SUM(CASE WHEN type = 'income'  THEN amount END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount END), 0) AS total_expense
    FROM transactions
    WHERE user_id = ?
");

$stmt->execute([$userId]);

$activity = $stmt->fetch();

$totalTransactions = (int) $activity["total_transactions"];
$totalIncome = (float) $activity["total_income"];
$totalExpense = (float) $activity["total_expense"];
$balance = $totalIncome - $totalExpense;


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM budgets
    WHERE user_id = ?
");

$stmt->execute([$userId]);

$totalBudgets = (int) $stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM categories
    WHERE user_id = ?
");

$stmt->execute([$userId]);

$customCategories = (int) $stmt->fetchColumn();


$initial = strtoupper(
    function_exists("mb_substr")
        ? mb_substr($user["name"], 0, 1)
        : substr($user["name"], 0, 1)
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile | Expense Tracker</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<div class="app-layout">

    <?php $activePage = "profile"; require __DIR__ . "/includes/sidebar.php"; ?>


    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>My Profile</h1>

                <p>Your account details and activity.</p>

            </div>

        </header>


        <?php displayMessage(); ?>


        <section class="dashboard-section">

            <div class="profile-card">

                <div class="profile-card-avatar">
                    <?= htmlspecialchars($initial) ?>
                </div>

                <div class="profile-card-details">

                    <h2><?= htmlspecialchars($user["name"]) ?></h2>

                    <p><?= htmlspecialchars($user["email"]) ?></p>

                </div>

            </div>

        </section>


        <section class="dashboard-section">

            <div class="section-header">

                <div>

                    <h2>Account</h2>

                    <p>Details you signed up with</p>

                </div>

            </div>

            <dl class="detail-list">

                <div>
                    <dt>Full name</dt>
                    <dd><?= htmlspecialchars($user["name"]) ?></dd>
                </div>

                <div>
                    <dt>Email address</dt>
                    <dd><?= htmlspecialchars($user["email"]) ?></dd>
                </div>

            </dl>

        </section>


        <section class="dashboard-section">

            <div class="section-header">

                <div>

                    <h2>Activity</h2>

                    <p>Everything you've recorded so far</p>

                </div>

            </div>

            <div class="time-summary-grid">

                <div class="time-summary-card">
                    <span>Transactions</span>
                    <strong><?= number_format($totalTransactions) ?></strong>
                </div>

                <div class="time-summary-card">
                    <span>Total Income</span>
                    <strong class="income-text">
                        NPR <?= number_format($totalIncome, 2) ?>
                    </strong>
                </div>

                <div class="time-summary-card">
                    <span>Total Expenses</span>
                    <strong class="expense-text">
                        NPR <?= number_format($totalExpense, 2) ?>
                    </strong>
                </div>

                <div class="time-summary-card">
                    <span>Balance</span>
                    <strong>NPR <?= number_format($balance, 2) ?></strong>
                </div>

            </div>

            <dl class="detail-list detail-list-compact">

                <div>
                    <dt>Budgets set</dt>
                    <dd><?= number_format($totalBudgets) ?></dd>
                </div>

                <div>
                    <dt>Custom categories</dt>
                    <dd><?= number_format($customCategories) ?></dd>
                </div>

            </dl>

        </section>

    </main>

</div>

</body>

</html>
