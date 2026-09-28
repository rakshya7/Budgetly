<?php

require_once "includes/auth.php";
require_once "config/database.php";
require_once "includes/message.php";

$userId = $_SESSION["user_id"];


/* Get expense categories */

$stmt = $pdo->prepare("
    SELECT id, name
    FROM categories
    WHERE type = 'expense'
    AND (user_id IS NULL OR user_id = ?)
    ORDER BY name
");

$stmt->execute([$userId]);

$categories = $stmt->fetchAll();


/* Get budgets */

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.amount,
        b.month,
        b.year,
        b.category_id,
        c.name AS category
    FROM budgets b
    LEFT JOIN categories c
        ON b.category_id = c.id
    WHERE b.user_id = ?
    ORDER BY b.year DESC, b.month DESC, c.name
");

$stmt->execute([$userId]);

$budgets = $stmt->fetchAll();


/*
 * Calculate spending for each budget
 */

foreach ($budgets as &$budget) {

    if ($budget["category_id"] === null) {

        /* Overall budget */

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
            $budget["month"],
            $budget["year"]
        ]);

    } else {

        /* Category budget */

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM transactions
            WHERE user_id = ?
            AND type = 'expense'
            AND category_id = ?
            AND MONTH(transaction_date) = ?
            AND YEAR(transaction_date) = ?
        ");

        $stmt->execute([
            $userId,
            $budget["category_id"],
            $budget["month"],
            $budget["year"]
        ]);
    }

    $budget["spent"] = $stmt->fetchColumn();

    $budget["remaining"] =
        $budget["amount"] - $budget["spent"];

    if ($budget["amount"] > 0) {

        $budget["percentage"] =
            ($budget["spent"] / $budget["amount"]) * 100;

    } else {

        $budget["percentage"] = 0;

    }
}

unset($budget);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Budgets | Expense Tracker</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="app-layout">

    <!-- Sidebar -->

    <?php $activePage = "budgets"; require __DIR__ . "/includes/sidebar.php"; ?>


    <!-- Main Content -->

    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>Budgets</h1>

                <p>
                    Set and track your monthly spending limits.
                </p>

            </div>

        </header>

        <?php displayMessage(); ?>


        <!-- Add Budget -->

        <section
            class="form-section budget-form-section"
        >

            <h2>Add Monthly Budget</h2>

            <p class="form-description">
                Set a spending limit for a month.
            </p>

            <form
                action="actions/save-budget.php"
                method="POST"
                class="transaction-form"
            >

                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        name="category_id"
                        id="category_id"
                    >

                        <option value="">
                            Overall Budget
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category["id"] ?>"
                            >
                                <?= htmlspecialchars(
                                    $category["name"]
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="amount">
                        Budget Amount (NPR)
                    </label>

                    <input
                        type="number"
                        name="amount"
                        id="amount"
                        min="0.01"
                        step="0.01"
                        placeholder="e.g. 15000"
                        required
                    >

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="month">
                            Month
                        </label>

                        <select
                            name="month"
                            id="month"
                            required
                        >

                            <?php

                            $currentMonth = date("n");

                            for (
                                $i = 1;
                                $i <= 12;
                                $i++
                            ):

                            ?>

                                <option
                                    value="<?= $i ?>"
                                    <?= $i == $currentMonth
                                        ? "selected"
                                        : "" ?>
                                >
                                    <?= date(
                                        "F",
                                        mktime(
                                            0,
                                            0,
                                            0,
                                            $i,
                                            1
                                        )
                                    ) ?>
                                </option>

                            <?php endfor; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="year">
                            Year
                        </label>

                        <input
                            type="number"
                            name="year"
                            id="year"
                            value="<?= date("Y") ?>"
                            min="2020"
                            max="2100"
                            required
                        >

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Save Budget
                    </button>

                </div>

            </form>

        </section>


        <!-- Budget Tracking -->

        <section class="dashboard-section">

            <div class="section-header">

                <div>

                    <h2>Budget Overview</h2>

                    <p>
                        Track your spending against your budgets.
                    </p>

                </div>

            </div>


            <?php if (count($budgets) > 0): ?>

                <div class="budget-grid">

                    <?php foreach ($budgets as $budget): ?>

                        <?php

                        $percentage = min(
                            100,
                            $budget["percentage"]
                        );

                        $isOverBudget =
                            $budget["spent"] >
                            $budget["amount"];

                        $remaining =
                            $budget["remaining"];

                        ?>

                        <div class="budget-card">

                            <div class="budget-card-header">

                                <div>

                                    <h3>
                                        <?= htmlspecialchars(
                                            $budget["category"]
                                            ?? "Overall Budget"
                                        ) ?>
                                    </h3>

                                    <p>
                                        <?= date(
                                            "F",
                                            mktime(
                                                0,
                                                0,
                                                0,
                                                $budget["month"],
                                                1
                                            )
                                        ) ?>

                                        <?= $budget["year"] ?>
                                    </p>

                                </div>

                                <div class="budget-actions">

                                    <a
                                        href="edit-budget.php?id=<?= $budget["id"] ?>"
                                        class="table-action"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        href="actions/delete-budget.php?id=<?= $budget["id"] ?>"
                                        class="table-action delete-action"
                                        onclick="return confirm('Are you sure you want to delete this budget?');"
                                    >
                                        Delete
                                    </a>

                                </div>

                            </div>


                            <div class="budget-numbers">

                                <div>

                                    <span>Budget</span>

                                    <strong>
                                        NPR <?= number_format(
                                            $budget["amount"],
                                            2
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>Spent</span>

                                    <strong>
                                        NPR <?= number_format(
                                            $budget["spent"],
                                            2
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        <?= $isOverBudget
                                            ? "Over Budget"
                                            : "Remaining" ?>
                                    </span>

                                    <strong
                                        class="<?= $isOverBudget
                                            ? "budget-danger"
                                            : "budget-success" ?>"
                                    >

                                        NPR <?= number_format(
                                            abs($remaining),
                                            2
                                        ) ?>

                                    </strong>

                                </div>

                            </div>


                            <div class="budget-progress">

                                <div
                                    class="budget-progress-bar <?= $isOverBudget
                                        ? "over-budget"
                                        : "" ?>"
                                    style="width: <?= $percentage ?>%;"
                                ></div>

                            </div>


                            <div class="budget-percentage">

                                <?php if ($isOverBudget): ?>

                                    <span class="budget-warning">
                                        ⚠ Budget exceeded by
                                        NPR <?= number_format(
                                            abs($remaining),
                                            2
                                        ) ?>
                                    </span>

                                <?php else: ?>

                                    <span>
                                        <?= number_format(
                                            $budget["percentage"],
                                            1
                                        ) ?>% used
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <h3>No budgets yet</h3>

                    <p>
                        Create your first monthly budget above.
                    </p>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>