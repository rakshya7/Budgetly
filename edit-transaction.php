<?php

require_once "includes/auth.php";
require_once "config/database.php";
require_once "includes/message.php";

$userId = $_SESSION["user_id"];

$transactionId = $_GET["id"] ?? "";

if (!filter_var($transactionId, FILTER_VALIDATE_INT)) {
    die("Invalid transaction ID.");
}

/* Get transaction */
$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.type,
        t.amount,
        t.category_id,
        t.description,
        t.transaction_date
    FROM transactions t
    WHERE t.id = ?
    AND t.user_id = ?
");

$stmt->execute([$transactionId, $userId]);

$transaction = $stmt->fetch();

if (!$transaction) {
    die("Transaction not found.");
}


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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Transaction | Expense Tracker</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<div class="app-layout">

    <!-- Sidebar -->

    <?php $activePage = "transactions"; require __DIR__ . "/includes/sidebar.php"; ?>


    <!-- Main Content -->

    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>Edit Transaction</h1>

                <p>
                    Update your transaction details.
                </p>

            </div>

        </header>

        <?php displayMessage(); ?>


        <section class="form-section">

            <form
                action="actions/update-transaction.php"
                method="POST"
                class="transaction-form"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?= $transaction["id"] ?>"
                >


                <!-- Type -->

                <div class="form-group">

                    <label for="type">
                        Transaction Type
                    </label>

                    <select name="type" id="type" required>

                        <option value="expense"
                            <?= $transaction["type"] === "expense" ? "selected" : "" ?>>
                            Expense
                        </option>

                        <option value="income"
                            <?= $transaction["type"] === "income" ? "selected" : "" ?>>
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
                        value="<?= htmlspecialchars($transaction["amount"]) ?>"
                        required
                    >

                </div>


                <!-- Category -->

                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select name="category_id" id="category_id" required>

                        <option value="">
                            Select category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category["id"] ?>"
                                data-type="<?= $category["type"] ?>"
                                <?= $transaction["category_id"] == $category["id"] ? "selected" : "" ?>
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
                        value="<?= htmlspecialchars($transaction["transaction_date"]) ?>"
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
                    ><?= htmlspecialchars($transaction["description"] ?? "") ?></textarea>

                </div>


                <!-- Buttons -->

                <div class="form-actions">

                    <a
                        href="transactions.php"
                        class="cancel-button"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Update Transaction
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>


<script src="assets/js/app.js"></script>

</body>

</html>