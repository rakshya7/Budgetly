<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "includes/auth.php";
require_once "config/database.php";

$userId = $_SESSION["user_id"];


/* ---------------------------------
   Month and Year Filter
--------------------------------- */

$month = isset($_GET["month"])
    ? (int) $_GET["month"]
    : (int) date("n");

$year = isset($_GET["year"])
    ? (int) $_GET["year"]
    : (int) date("Y");


/* Validate month and year */

if ($month < 1 || $month > 12) {
    $month = (int) date("n");
}

if ($year < 2020 || $year > date("Y")) {
    $year = (int) date("Y");
}


/* ---------------------------------
   Total Income
--------------------------------- */

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
    $month,
    $year
]);

$totalIncome = (float) $stmt->fetchColumn();


/* ---------------------------------
   Total Expenses
--------------------------------- */

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
    $month,
    $year
]);

$totalExpense = (float) $stmt->fetchColumn();


/* ---------------------------------
   Balance
--------------------------------- */

$balance = $totalIncome - $totalExpense;


/* ---------------------------------
   Expenses by Category
--------------------------------- */

$stmt = $pdo->prepare("
    SELECT
        c.name AS category,
        SUM(t.amount) AS total
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
    $month,
    $year
]);

$categoryExpenses = $stmt->fetchAll();


/* ---------------------------------
   Daily Expenses
--------------------------------- */

$stmt = $pdo->prepare("
    SELECT
        DAY(transaction_date) AS day,
        SUM(amount) AS total
    FROM transactions
    WHERE user_id = ?
    AND type = 'expense'
    AND MONTH(transaction_date) = ?
    AND YEAR(transaction_date) = ?
    GROUP BY DAY(transaction_date)
    ORDER BY day
");

$stmt->execute([
    $userId,
    $month,
    $year
]);

$dailyExpenses = $stmt->fetchAll();


/* ---------------------------------
   Monthly Budget
--------------------------------- */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0)
    FROM budgets
    WHERE user_id = ?
    AND month = ?
    AND year = ?
");

$stmt->execute([
    $userId,
    $month,
    $year
]);

$monthlyBudget = (float) $stmt->fetchColumn();


/* ---------------------------------
   Budget Calculations
--------------------------------- */

$spentAmount = $totalExpense;

$remainingBudget = $monthlyBudget - $spentAmount;

$budgetPercentage = 0;

if ($monthlyBudget > 0) {
    $budgetPercentage = ($spentAmount / $monthlyBudget) * 100;
}


/* ---------------------------------
   Month Name
--------------------------------- */

$monthName = date(
    "F",
    mktime(0, 0, 0, $month, 1)
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

    <title>Reports - Expense Tracker</title>

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>


<body>

<div class="app-layout">


    <!-- =========================
         Sidebar
    ========================= -->

    <?php $activePage = "reports"; require __DIR__ . "/includes/sidebar.php"; ?>
    <!-- =========================
         Main Content
    ========================= -->

    <main class="main-content">


        <header class="topbar">
        <div>

            <h1>
                Reports
            </h1>

            <p>
                Monthly financial summary
            </p>

        </div>
        </header>


        <!-- =========================
             Month / Year Filter
        ========================= -->

        <form
            method="GET"
            class="report-filter"
        >

            <div class="report-filter-group">

                <label for="month">
                    Month
                </label>

                <select
                    name="month"
                    id="month"
                >

                    <?php for ($m = 1; $m <= 12; $m++): ?>

                        <option
                            value="<?php echo $m; ?>"
                            <?php echo ($month == $m) ? "selected" : ""; ?>
                        >

                            <?php
                            echo date(
                                "F",
                                mktime(0, 0, 0, $m, 1)
                            );
                            ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <div class="report-filter-group">

                <label for="year">
                    Year
                </label>

                <select
                    name="year"
                    id="year"
                >

                    <?php

                    $currentYear = date("Y");

                    for (
                        $y = $currentYear - 5;
                        $y <= $currentYear;
                        $y++
                    ):

                    ?>

                        <option
                            value="<?php echo $y; ?>"
                            <?php echo ($year == $y) ? "selected" : ""; ?>
                        >

                            <?php echo $y; ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <button type="submit">
                Apply Filter
            </button>

        </form>


        <!-- =========================
             Summary Cards
        ========================= -->

        <div class="reports-summary">


            <div class="reports-card">

                <span>
                    Total Income
                </span>

                <strong>
                    NPR <?php echo number_format($totalIncome, 2); ?>
                </strong>

            </div>


            <div class="reports-card">

                <span>
                    Total Expenses
                </span>

                <strong>
                    NPR <?php echo number_format($totalExpense, 2); ?>
                </strong>

            </div>


            <div class="reports-card">

                <span>
                    Balance
                </span>

                <strong>
                    NPR <?php echo number_format($balance, 2); ?>
                </strong>

            </div>


        </div>


        <!-- =========================
             Budget Overview
        ========================= -->

        <section class="reports-section">

            <h2>
                Budget Overview
            </h2>

            <p class="reports-section-subtitle">
                <?php echo $monthName . " " . $year; ?>
            </p>


            <div class="budget-report-grid">


                <div>

                    <span>
                        Monthly Budget
                    </span>

                    <strong>
                        NPR <?php echo number_format($monthlyBudget, 2); ?>
                    </strong>

                </div>


                <div>

                    <span>
                        Spent
                    </span>

                    <strong>
                        NPR <?php echo number_format($spentAmount, 2); ?>
                    </strong>

                </div>


                <div>

                    <span>
                        <?php
                        echo $remainingBudget >= 0
                            ? "Remaining"
                            : "Over Budget";
                        ?>
                    </span>

                    <strong
                        class="<?php
                        echo $remainingBudget >= 0
                            ? "budget-ok"
                            : "budget-over";
                        ?>"
                    >

                        NPR
                        <?php
                        echo number_format(
                            abs($remainingBudget),
                            2
                        );
                        ?>

                    </strong>

                </div>


            </div>


            <?php if ($monthlyBudget > 0): ?>

                <div class="report-budget-bar">

                    <div
                        class="<?php
                        echo $budgetPercentage > 100
                            ? "over"
                            : "";
                        ?>"
                        style="width: <?php echo min($budgetPercentage, 100); ?>%;"
                    ></div>

                </div>


                <div class="report-budget-text">

                    <span>
                        <?php
                        echo number_format(
                            $budgetPercentage,
                            1
                        );
                        ?>% used
                    </span>


                    <?php if ($budgetPercentage > 100): ?>

                        <strong class="budget-over">
                            Budget exceeded
                        </strong>

                    <?php endif; ?>

                </div>


            <?php else: ?>

                <p class="reports-empty">
                    No budget has been set for this month.
                </p>

            <?php endif; ?>


        </section>


        <!-- =========================
             Expenses by Category
        ========================= -->

        <section class="reports-section">

            <h2>
                Expenses by Category
            </h2>

            <p class="reports-section-subtitle">
                <?php echo $monthName . " " . $year; ?>
            </p>


            <?php if (count($categoryExpenses) > 0): ?>


                <?php foreach ($categoryExpenses as $item): ?>

                    <?php

                    $percentage = 0;

                    if ($totalExpense > 0) {

                        $percentage =
                            ($item["total"] / $totalExpense) * 100;

                    }

                    ?>


                    <div class="category-row">


                        <div class="category-header">

                            <span>
                                <?php
                                echo htmlspecialchars(
                                    $item["category"]
                                );
                                ?>
                            </span>


                            <strong>
                                NPR
                                <?php
                                echo number_format(
                                    $item["total"],
                                    2
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="category-bar">

                            <div
                                class="category-fill"
                                style="width: <?php echo $percentage; ?>%;"
                            ></div>

                        </div>


                        <span class="percentage">

                            <?php
                            echo number_format(
                                $percentage,
                                1
                            );
                            ?>%

                        </span>

                    </div>


                <?php endforeach; ?>


            <?php else: ?>

                <p class="reports-empty">
                    No expenses recorded for this month.
                </p>

            <?php endif; ?>


        </section>


        <!-- =========================
             Charts
        ========================= -->

        <div class="charts-grid">


            <!-- Category Chart -->

            <section class="reports-section">

                <h2>
                    Expense Distribution
                </h2>

                <p class="reports-section-subtitle">
                    Expenses by category
                </p>


                <?php if (count($categoryExpenses) > 0): ?>

                    <div class="chart-container">

                        <canvas id="categoryChart"></canvas>

                    </div>

                <?php else: ?>

                    <p class="reports-empty">
                        No category data available.
                    </p>

                <?php endif; ?>


            </section>


            <!-- Daily Chart -->

            <section class="reports-section">

                <h2>
                    Daily Spending
                </h2>

                <p class="reports-section-subtitle">
                    Expenses throughout the month
                </p>


                <?php if (count($dailyExpenses) > 0): ?>

                    <div class="chart-container">

                        <canvas id="dailyChart"></canvas>

                    </div>

                <?php else: ?>

                    <p class="reports-empty">
                        No daily expense data available.
                    </p>

                <?php endif; ?>


            </section>


        </div>


    </main>

</div>


<!-- =========================
     Chart JavaScript
========================= -->

<script>

const categoryLabels = <?php
echo json_encode(
    array_column(
        $categoryExpenses,
        "category"
    )
);
?>;


const categoryValues = <?php
echo json_encode(
    array_map(
        "floatval",
        array_column(
            $categoryExpenses,
            "total"
        )
    )
);
?>;


const dailyLabels = <?php
echo json_encode(
    array_map(
        function ($item) {
            return "Day " . $item["day"];
        },
        $dailyExpenses
    )
);
?>;


const dailyValues = <?php
echo json_encode(
    array_map(
        "floatval",
        array_column(
            $dailyExpenses,
            "total"
        )
    )
);
?>;


/* =========================
   Category Doughnut Chart
========================= */

const categoryCanvas =
    document.getElementById("categoryChart");


if (
    categoryCanvas &&
    categoryLabels.length > 0
) {

    new Chart(
        categoryCanvas,
        {

            type: "doughnut",

            data: {

                labels: categoryLabels,

                datasets: [
                    {
                        data: categoryValues
                    }
                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        position: "bottom"
                    }

                }

            }

        }
    );

}


/* =========================
   Daily Expense Bar Chart
========================= */

const dailyCanvas =
    document.getElementById("dailyChart");


if (
    dailyCanvas &&
    dailyLabels.length > 0
) {

    new Chart(
        dailyCanvas,
        {

            type: "bar",

            data: {

                labels: dailyLabels,

                datasets: [
                    {
                        label: "Daily Expenses",
                        data: dailyValues
                    }
                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                scales: {

                    y: {
                        beginAtZero: true
                    }

                }

            }

        }
    );

}

</script>


</body>
</html>