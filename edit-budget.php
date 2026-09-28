<?php

require_once "includes/auth.php";
require_once "config/database.php";

$userId = $_SESSION["user_id"];

$budgetId = $_GET["id"] ?? "";

if (!filter_var($budgetId, FILTER_VALIDATE_INT)) {
    die("Invalid budget ID.");
}


/* Get budget */

$stmt = $pdo->prepare("
    SELECT
        id,
        category_id,
        amount,
        month,
        year
    FROM budgets
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $budgetId,
    $userId
]);

$budget = $stmt->fetch();

if (!$budget) {
    die("Budget not found.");
}


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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Budget | Expense Tracker</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<div class="app-layout">

    <!-- Sidebar -->
    <?php $activePage = "budgets"; require __DIR__ . "/includes/sidebar.php"; ?>


    <!-- Main Content -->

    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>Edit Budget</h1>

                <p>
                    Update your monthly budget.
                </p>

            </div>

        </header>


        <section class="form-section budget-form-section">

            <form
                action="actions/update-budget.php"
                method="POST"
                class="transaction-form"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?= $budget["id"] ?>"
                >


                <!-- Category -->

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
                                <?= $budget["category_id"] == $category["id"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($category["name"]) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Amount -->

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
                        value="<?= htmlspecialchars($budget["amount"]) ?>"
                        required
                    >

                </div>


                <!-- Month + Year -->

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

                            <?php for ($i = 1; $i <= 12; $i++): ?>

                                <option
                                    value="<?= $i ?>"
                                    <?= $budget["month"] == $i ? "selected" : "" ?>
                                >
                                    <?= date(
                                        "F",
                                        mktime(0, 0, 0, $i, 1)
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
                            min="2020"
                            max="2100"
                            value="<?= htmlspecialchars($budget["year"]) ?>"
                            required
                        >

                    </div>

                </div>


                <!-- Buttons -->

                <div class="form-actions">

                    <a
                        href="budgets.php"
                        class="cancel-button"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Update Budget
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

</body>

</html>