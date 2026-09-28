<?php

require_once "includes/auth.php";
require_once "config/database.php";
require_once "includes/message.php";

$userId = $_SESSION["user_id"];

$error = "";

/* Get categories */
$stmt = $pdo->prepare("
    SELECT id, name, type
    FROM categories
    WHERE user_id IS NULL OR user_id = ?
    ORDER BY type, name
");

$stmt->execute([$userId]);

$categories = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Transaction | Expense Tracker</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<div class="app-layout">

    <!-- Sidebar -->
    <?php $activePage = "add-transaction"; require __DIR__ . "/includes/sidebar.php"; ?>
   


    <!-- Main Content -->

    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>Add Transaction</h1>

                <p>
                    Record your income or expense.
                </p>

            </div>

        </header>

        <?php displayMessage(); ?>


        <section class="form-section">

            <form
                action="actions/save-transaction.php"
                method="POST"
                class="transaction-form"
            >

                <!-- Transaction Type -->

                <div class="form-group">

                    <label for="type">
                        Transaction Type
                    </label>

                    <select
                        name="type"
                        id="type"
                        required
                    >

                        <option value="">
                            Select type
                        </option>

                        <option value="expense">
                            Expense
                        </option>

                        <option value="income">
                            Income
                        </option>

                    </select>

                </div>


                <!-- Amount -->

                <div class="form-group">

                    <label for="amount">
                        Amount (NPR)
                    </label>

                    <input
                        type="number"
                        name="amount"
                        id="amount"
                        min="0.01"
                        step="0.01"
                        placeholder="e.g. 500"
                        required
                    >

                </div>


                <!-- Category -->

                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        name="category_id"
                        id="category_id"
                        required
                    >

                        <option value="">
                            Select category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category["id"] ?>"
                                data-type="<?= $category["type"] ?>"
                            >

                                <?= htmlspecialchars($category["name"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Date -->

                <div class="form-group">

                    <label for="transaction_date">
                        Date
                    </label>

                    <input
                        type="date"
                        name="transaction_date"
                        id="transaction_date"
                        value="<?= date('Y-m-d') ?>"
                        required
                    >

                </div>


                <!-- Description -->

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        name="description"
                        id="description"
                        rows="4"
                        maxlength="500"
                        placeholder="What was this transaction for?"
                    ></textarea>

                </div>


                <div class="form-actions">

                    <a
                        href="dashboard.php"
                        class="cancel-button"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Save Transaction
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

<script src="assets/js/app.js"></script>

</body>

</html>