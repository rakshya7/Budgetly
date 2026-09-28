<?php

require_once "includes/auth.php";
require_once "config/database.php";
require_once "includes/message.php";

$userId = $_SESSION["user_id"];

$currentMonth = date("n");
$currentYear = date("Y");


/* =================================
   Time-Based Expense Summary
================================= */

$today = date("Y-m-d");


/* Today's Expenses */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM transactions
    WHERE user_id = ?
    AND type = 'expense'
    AND transaction_date = ?
");

$stmt->execute([
    $userId,
    $today
]);

$todayExpense = (float) $stmt->fetchColumn();


/* This Week's Expenses */

$weekStart = date(
    "Y-m-d",
    strtotime("monday this week")
);

$weekEnd = date(
    "Y-m-d",
    strtotime("sunday this week")
);

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM transactions
    WHERE user_id = ?
    AND type = 'expense'
    AND transaction_date BETWEEN ? AND ?
");

$stmt->execute([
    $userId,
    $weekStart,
    $weekEnd
]);

$weekExpense = (float) $stmt->fetchColumn();


/* =================================
   All-Time Income
================================= */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM transactions
    WHERE user_id = ?
    AND type = 'income'
");

$stmt->execute([$userId]);

$totalIncome = (float) $stmt->fetchColumn();


/* =================================
   All-Time Expenses
================================= */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM transactions
    WHERE user_id = ?
    AND type = 'expense'
");

$stmt->execute([$userId]);

$totalExpense = (float) $stmt->fetchColumn();


/* =================================
   All-Time Balance
================================= */

$balance = $totalIncome - $totalExpense;


/* =================================
   Current Month Income
================================= */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM transactions
    WHERE user_id = ?
    AND type = 'income'
    AND MONTH(transaction_date) = ?
    AND YEAR(transaction_date) = ?
");

$stmt->execute([
    $userId,
    $currentMonth,
    $currentYear
]);

$monthlyIncome = (float) $stmt->fetchColumn();


/* =================================
   Current Month Expenses
================================= */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM transactions
    WHERE user_id = ?
    AND type = 'expense'
    AND MONTH(transaction_date) = ?
    AND YEAR(transaction_date) = ?
");

$stmt->execute([
    $userId,
    $currentMonth,
    $currentYear
]);

$monthlyExpense = (float) $stmt->fetchColumn();


/* =================================
   Current Month Balance
================================= */

$monthlyBalance = $monthlyIncome - $monthlyExpense;


/* =================================
   This Year's Expenses
================================= */

$yearStart = date("Y-01-01");
$yearEnd = date("Y-12-31");

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM transactions
    WHERE user_id = ?
    AND type = 'expense'
    AND transaction_date BETWEEN ? AND ?
");

$stmt->execute([
    $userId,
    $yearStart,
    $yearEnd
]);

$yearExpense = (float) $stmt->fetchColumn();


/* =================================
   Monthly Budget
================================= */

/* Overall budget */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM budgets
    WHERE user_id = ?
    AND category_id IS NULL
    AND month = ?
    AND year = ?
");

$stmt->execute([
    $userId,
    $currentMonth,
    $currentYear
]);

$overallBudget = (float) $stmt->fetchColumn();


/* Category budgets */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM budgets
    WHERE user_id = ?
    AND category_id IS NOT NULL
    AND month = ?
    AND year = ?
");

$stmt->execute([
    $userId,
    $currentMonth,
    $currentYear
]);

$categoryBudget = (float) $stmt->fetchColumn();


/*
   Use overall budget if available.
   Otherwise use category budget total.
*/

$monthlyBudget = $overallBudget > 0
    ? $overallBudget
    : $categoryBudget;


/* Budget percentage */

if ($monthlyBudget > 0) {

    $budgetPercentage =
        ($monthlyExpense / $monthlyBudget) * 100;

} else {

    $budgetPercentage = 0;

}


/*
   Keep progress bar from becoming
   visually larger than 100%.
*/

$budgetProgress = min($budgetPercentage, 100);


/* =================================
   Spending by Category
================================= */

$stmt = $pdo->prepare("
    SELECT
        c.name AS category,
        COALESCE(SUM(t.amount), 0) AS total

    FROM transactions t

    JOIN categories c
        ON t.category_id = c.id

    WHERE t.user_id = ?
    AND t.type = 'expense'
    AND MONTH(t.transaction_date) = ?
    AND YEAR(t.transaction_date) = ?

    GROUP BY c.id, c.name

    ORDER BY total DESC
");

$stmt->execute([
    $userId,
    $currentMonth,
    $currentYear
]);

$categorySpending = $stmt->fetchAll();


/* =================================
   Recent Transactions
================================= */

$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.type,
        t.amount,
        t.description,
        t.transaction_date,
        c.name AS category

    FROM transactions t

    JOIN categories c
        ON t.category_id = c.id

    WHERE t.user_id = ?

    ORDER BY
        t.transaction_date DESC,
        t.id DESC

    LIMIT 5
");

$stmt->execute([$userId]);

$recentTransactions = $stmt->fetchAll();

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dashboard | Expense Tracker
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


<div class="app-layout">


    <!-- =================================
         Sidebar
    ================================== -->

    <?php $activePage = "dashboard"; require __DIR__ . "/includes/sidebar.php"; ?>



    <!-- =================================
         Main Content
    ================================== -->

    <main class="main-content">


        <!-- Header -->

        <header class="topbar">


            <div>

                <h1>
                    Dashboard
                </h1>




                <p>
                    Welcome back,
                    <?= htmlspecialchars($_SESSION["user_name"]) ?>.
                </p>

            </div>


            <a
                href="add-transaction.php"
                class="btn-primary-small"
            >
                + Add Transaction
            </a>


        </header>

        <?php displayMessage(); ?>



        <!-- =================================
             Time-Based Expense Summary
        ================================== -->

        <div class="time-summary-grid">


            <!-- Today -->

            <div class="time-summary-card">

                <span>
                    Today
                </span>


                <strong>
                    NPR
                    <?= number_format($todayExpense, 2) ?>
                </strong>

            </div>



            <!-- This Week -->

            <div class="time-summary-card">

                <span>
                    This Week
                </span>


                <strong>
                    NPR
                    <?= number_format($weekExpense, 2) ?>
                </strong>

            </div>



            <!-- This Month -->

            <div class="time-summary-card">

                <span>
                    This Month
                </span>


                <strong>
                    NPR
                    <?= number_format($monthlyExpense, 2) ?>
                </strong>

            </div>



            <!-- This Year -->

            <div class="time-summary-card">

                <span>
                    This Year
                </span>


                <strong>
                    NPR
                    <?= number_format($yearExpense, 2) ?>
                </strong>

            </div>


        </div>



        <!-- =================================
             All-Time Summary
        ================================== -->

        <section class="dashboard-section">


            <div class="section-header">

                <div>

                    <h2>
                        Overall Summary
                    </h2>

                    <p>
                        Your complete financial overview
                    </p>

                </div>

            </div>



            <div class="summary-grid">


                <!-- Income -->

                <div class="summary-card">

                    <span>
                        Total Income
                    </span>


                    <strong class="income-text">

                        NPR
                        <?= number_format(
                            $totalIncome,
                            2
                        ) ?>

                    </strong>

                </div>



                <!-- Expense -->

                <div class="summary-card">

                    <span>
                        Total Expenses
                    </span>


                    <strong class="expense-text">

                        NPR
                        <?= number_format(
                            $totalExpense,
                            2
                        ) ?>

                    </strong>

                </div>



                <!-- Balance -->

                <div class="summary-card">

                    <span>
                        Balance
                    </span>


                    <strong>

                        NPR
                        <?= number_format(
                            $balance,
                            2
                        ) ?>

                    </strong>

                </div>


            </div>


        </section>



        <!-- =================================
             Monthly Overview
        ================================== -->

        <section class="dashboard-section">


            <div class="section-header">

                <div>

                    <h2>
                        Monthly Overview
                    </h2>

                    <p>
                        <?= date("F Y") ?>
                    </p>

                </div>

            </div>



            <div class="monthly-overview-grid">


                <!-- Monthly Income -->

                <div class="overview-item">

                    <span>
                        Income
                    </span>


                    <strong class="overview-income">

                        NPR
                        <?= number_format(
                            $monthlyIncome,
                            2
                        ) ?>

                    </strong>

                </div>



                <!-- Monthly Expense -->

                <div class="overview-item">

                    <span>
                        Expenses
                    </span>


                    <strong class="overview-expense">

                        NPR
                        <?= number_format(
                            $monthlyExpense,
                            2
                        ) ?>

                    </strong>

                </div>



                <!-- Monthly Balance -->

                <div class="overview-item">

                    <span>
                        Balance
                    </span>


                    <strong>

                        NPR
                        <?= number_format(
                            $monthlyBalance,
                            2
                        ) ?>

                    </strong>

                </div>



                <!-- Monthly Budget -->

                <div class="overview-item">

                    <span>
                        Budget
                    </span>


                    <strong>

                        NPR
                        <?= number_format(
                            $monthlyBudget,
                            2
                        ) ?>

                    </strong>

                </div>


            </div>


        </section>



        <!-- =================================
             Budget Progress
        ================================== -->

        <section class="dashboard-section">


            <div class="section-header">

                <div>

                    <h2>
                        Monthly Budget
                    </h2>

                    <p>
                        Track your spending against your budget
                    </p>

                </div>


                <a
                    href="budgets.php"
                    class="table-action"
                >
                    Manage Budget
                </a>

            </div>



            <?php if ($monthlyBudget > 0): ?>


                <div class="dashboard-budget">


                    <div class="dashboard-budget-header">

                        <span>
                            Spent
                        </span>


                        <span>

                            NPR
                            <?= number_format(
                                $monthlyExpense,
                                2
                            ) ?>

                            /
                            NPR
                            <?= number_format(
                                $monthlyBudget,
                                2
                            ) ?>

                        </span>

                    </div>



                    <div class="budget-progress">

                        <div
                            class="budget-progress-bar"
                            style="width: <?= $budgetProgress ?>%;"
                        >
                        </div>

                    </div>



                    <div class="budget-percentage">

                        <span>

                            <?= number_format(
                                $budgetPercentage,
                                1
                            ) ?>% used

                        </span>


                        <?php if ($monthlyExpense > $monthlyBudget): ?>

                            <span class="budget-warning">

                                Budget exceeded by NPR

                                <?= number_format(
                                    $monthlyExpense -
                                    $monthlyBudget,
                                    2
                                ) ?>

                            </span>


                        <?php else: ?>

                            <span>

                                Remaining: NPR

                                <?= number_format(
                                    $monthlyBudget -
                                    $monthlyExpense,
                                    2
                                ) ?>

                            </span>

                        <?php endif; ?>


                    </div>


                </div>


            <?php else: ?>


                <div class="empty-state">

                    <h3>
                        No budget set
                    </h3>


                    <p>
                        Set a monthly budget to track your spending.
                    </p>


                    <a
                        href="budgets.php"
                        class="btn-primary-small"
                    >
                        Set Budget
                    </a>

                </div>


            <?php endif; ?>


        </section>



        <!-- =================================
             Spending by Category
        ================================== -->

        <section class="dashboard-section">


            <div class="section-header">

                <div>

                    <h2>
                        Spending by Category
                    </h2>


                    <p>
                        <?= date("F Y") ?>
                    </p>

                </div>


            </div>



            <?php if (count($categorySpending) > 0): ?>


                <div class="category-list">


                    <?php foreach (
                        $categorySpending
                        as $category
                    ): ?>


                        <?php

                        $categoryPercentage = 0;

                        if ($monthlyExpense > 0) {

                            $categoryPercentage =
                                (
                                    (float) $category["total"]
                                    /
                                    $monthlyExpense
                                ) * 100;
                        }

                        ?>


                        <div class="category-item">


                            <div class="category-item-header">


                                <span>

                                    <?= htmlspecialchars(
                                        $category["category"]
                                    ) ?>

                                </span>


                                <strong>

                                    NPR
                                    <?= number_format(
                                        $category["total"],
                                        2
                                    ) ?>

                                </strong>


                            </div>



                            <div class="category-progress">


                                <div
                                    class="category-progress-bar"
                                    style="width: <?= min(
                                        $categoryPercentage,
                                        100
                                    ) ?>%;"
                                >
                                </div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty-state">

                    <h3>
                        No expenses this month
                    </h3>


                    <p>
                        Your category spending will appear here.
                    </p>

                </div>


            <?php endif; ?>


        </section>



        <!-- =================================
             Recent Transactions
        ================================== -->

        <section class="dashboard-section">


            <div class="section-header">


                <div>

                    <h2>
                        Recent Transactions
                    </h2>


                    <p>
                        Your latest financial activity
                    </p>

                </div>


                <a
                    href="transactions.php"
                    class="table-action"
                >
                    View All
                </a>


            </div>



            <?php if (count($recentTransactions) > 0): ?>


                <div class="transaction-table">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Date
                                </th>


                                <th>
                                    Category
                                </th>


                                <th>
                                    Description
                                </th>


                                <th>
                                    Type
                                </th>


                                <th>
                                    Amount
                                </th>

                            </tr>

                        </thead>



                        <tbody>


                        <?php foreach (
                            $recentTransactions
                            as $transaction
                        ): ?>


                            <tr>


                                <td>

                                    <?= htmlspecialchars(
                                        $transaction[
                                            "transaction_date"
                                        ]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $transaction[
                                            "category"
                                        ]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $transaction[
                                            "description"
                                        ] ?: "-"
                                    ) ?>

                                </td>


                                <td>


                                    <span
                                        class="<?= $transaction["type"] === "income"
                                            ? "income-label"
                                            : "expense-label" ?>"
                                    >

                                        <?= ucfirst(
                                            $transaction["type"]
                                        ) ?>

                                    </span>


                                </td>


                                <td>

                                    <?= $transaction["type"] === "income"
                                        ? "+"
                                        : "-" ?>


                                    NPR

                                    <?= number_format(
                                        $transaction["amount"],
                                        2
                                    ) ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php else: ?>


                <div class="empty-state">

                    <h3>
                        No transactions yet
                    </h3>


                    <p>
                        Add your first transaction to get started.
                    </p>


                    <a
                        href="add-transaction.php"
                        class="btn-primary-small"
                    >
                        Add Transaction
                    </a>

                </div>


            <?php endif; ?>


        </section>


    </main>


</div>


</body>

</html>

