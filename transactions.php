<?php

require_once "includes/auth.php";
require_once "config/database.php";
require_once "includes/message.php";

$userId = $_SESSION["user_id"];


/* ---------------------------------
   Get Categories
--------------------------------- */

$stmt = $pdo->prepare("
    SELECT id, name, type
    FROM categories
    WHERE user_id IS NULL OR user_id = ?
    ORDER BY name
");

$stmt->execute([$userId]);
$categories = $stmt->fetchAll();


/* ---------------------------------
   Get Filters
--------------------------------- */

$type = $_GET["type"] ?? "";
$categoryId = $_GET["category_id"] ?? "";
$search = trim($_GET["search"] ?? "");

$fromDate = $_GET["from_date"] ?? "";
$toDate = $_GET["to_date"] ?? "";

$minAmount = $_GET["min_amount"] ?? "";
$maxAmount = $_GET["max_amount"] ?? "";


/* ---------------------------------
   Validate Date Filters
--------------------------------- */

if ($fromDate !== "") {

    $dateObject = DateTime::createFromFormat(
        "Y-m-d",
        $fromDate
    );

    if (
        !$dateObject ||
        $dateObject->format("Y-m-d") !== $fromDate
    ) {
        $fromDate = "";
    }
}


if ($toDate !== "") {

    $dateObject = DateTime::createFromFormat(
        "Y-m-d",
        $toDate
    );

    if (
        !$dateObject ||
        $dateObject->format("Y-m-d") !== $toDate
    ) {
        $toDate = "";
    }
}


/* ---------------------------------
   Validate Amount Filters
--------------------------------- */

if (
    $minAmount !== "" &&
    (!is_numeric($minAmount) || $minAmount < 0)
) {
    $minAmount = "";
}


if (
    $maxAmount !== "" &&
    (!is_numeric($maxAmount) || $maxAmount < 0)
) {
    $maxAmount = "";
}


/* ---------------------------------
   Build Transactions Query
--------------------------------- */

$query = "
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
";

$params = [$userId];


/* ---------------------------------
   Type Filter
--------------------------------- */

if (
    $type === "income" ||
    $type === "expense"
) {

    $query .= " AND t.type = ?";
    $params[] = $type;
}


/* ---------------------------------
   Category Filter
--------------------------------- */

if (filter_var($categoryId, FILTER_VALIDATE_INT)) {

    $query .= " AND t.category_id = ?";
    $params[] = $categoryId;
}


/* ---------------------------------
   Search Filter
--------------------------------- */

if ($search !== "") {

    $query .= "
        AND (
            c.name LIKE ?
            OR t.description LIKE ?
        )
    ";

    $searchTerm = "%" . $search . "%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
}


/* ---------------------------------
   From Date Filter
--------------------------------- */

if ($fromDate !== "") {

    $query .= "
        AND t.transaction_date >= ?
    ";

    $params[] = $fromDate;
}


/* ---------------------------------
   To Date Filter
--------------------------------- */

if ($toDate !== "") {

    $query .= "
        AND t.transaction_date <= ?
    ";

    $params[] = $toDate;
}


/* ---------------------------------
   Minimum Amount Filter
--------------------------------- */

if ($minAmount !== "") {

    $query .= "
        AND t.amount >= ?
    ";

    $params[] = $minAmount;
}


/* ---------------------------------
   Maximum Amount Filter
--------------------------------- */

if ($maxAmount !== "") {

    $query .= "
        AND t.amount <= ?
    ";

    $params[] = $maxAmount;
}


/* ---------------------------------
   Order Results
--------------------------------- */

$query .= "
    ORDER BY
        t.transaction_date DESC,
        t.id DESC
";


/* ---------------------------------
   Execute Query
--------------------------------- */

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$transactions = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Transactions | Expense Tracker</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>

<div class="app-layout">


    <!-- =========================
         Sidebar
    ========================== -->

    <?php $activePage = "transactions"; require __DIR__ . "/includes/sidebar.php"; ?>



    <!-- =========================
         Main Content
    ========================== -->

    <main class="main-content">


        <!-- =========================
             Topbar
        ========================== -->

        <header class="topbar">

            <div>

                <h1>
                    Transactions
                </h1>


                <p>
                    View and manage your financial transactions.
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



        <!-- =========================
             Filters
        ========================== -->

        <section class="filter-section">

            <form
                method="GET"
                class="filter-form"
            >


                <!-- Search -->

                <div class="filter-group">

                    <label for="search">
                        Search
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Search description or category"
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>



                <!-- Type -->

                <div class="filter-group">

                    <label for="type">
                        Type
                    </label>

                    <select
                        name="type"
                        id="type"
                    >

                        <option value="">
                            All Types
                        </option>

                        <option
                            value="income"
                            <?= $type === "income" ? "selected" : "" ?>
                        >
                            Income
                        </option>

                        <option
                            value="expense"
                            <?= $type === "expense" ? "selected" : "" ?>
                        >
                            Expense
                        </option>

                    </select>

                </div>



                <!-- Category -->

                <div class="filter-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        name="category_id"
                        id="category_id"
                    >

                        <option value="">
                            All Categories
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category["id"] ?>"
                                <?= (
                                    (string) $categoryId ===
                                    (string) $category["id"]
                                ) ? "selected" : "" ?>
                            >

                                <?= htmlspecialchars($category["name"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- From Date -->

                <div class="filter-group">

                    <label for="from_date">
                        From Date
                    </label>

                    <input
                        type="date"
                        id="from_date"
                        name="from_date"
                        value="<?= htmlspecialchars($fromDate) ?>"
                    >

                </div>



                <!-- To Date -->

                <div class="filter-group">

                    <label for="to_date">
                        To Date
                    </label>

                    <input
                        type="date"
                        id="to_date"
                        name="to_date"
                        value="<?= htmlspecialchars($toDate) ?>"
                    >

                </div>



                <!-- Minimum Amount -->

                <div class="filter-group">

                    <label for="min_amount">
                        Min Amount
                    </label>

                    <input
                        type="number"
                        id="min_amount"
                        name="min_amount"
                        min="0"
                        step="0.01"
                        placeholder="0"
                        value="<?= htmlspecialchars($minAmount) ?>"
                    >

                </div>



                <!-- Maximum Amount -->

                <div class="filter-group">

                    <label for="max_amount">
                        Max Amount
                    </label>

                    <input
                        type="number"
                        id="max_amount"
                        name="max_amount"
                        min="0"
                        step="0.01"
                        placeholder="999999"
                        value="<?= htmlspecialchars($maxAmount) ?>"
                    >

                </div>



                <!-- Buttons -->

                <div class="filter-buttons">

                    <button
                        type="submit"
                        class="btn-primary-small"
                    >
                        Filter
                    </button>

                    <a
                        href="transactions.php"
                        class="cancel-button"
                    >
                        Clear
                    </a>

                </div>


            </form>

        </section>



        <!-- =========================
             Transactions
        ========================== -->

        <section class="dashboard-section">


            <div class="section-header">

                <div>

                    <h2>
                        All Transactions
                    </h2>

                    <p>
                        <?= count($transactions) ?> transaction(s) found
                    </p>

                </div>

            </div>



            <?php if (count($transactions) > 0): ?>


                <!-- Transaction Table -->

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

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($transactions as $transaction): ?>

                                <tr>


                                    <!-- Date -->

                                    <td>
                                        <?= htmlspecialchars(
                                            $transaction["transaction_date"]
                                        ) ?>
                                    </td>



                                    <!-- Category -->

                                    <td>
                                        <?= htmlspecialchars(
                                            $transaction["category"]
                                        ) ?>
                                    </td>



                                    <!-- Description -->

                                    <td>
                                        <?= htmlspecialchars(
                                            $transaction["description"] ?: "-"
                                        ) ?>
                                    </td>



                                    <!-- Type -->

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



                                    <!-- Amount -->

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



                                    <!-- Actions -->

                                    <td>

                                        <a
                                            href="edit-transaction.php?id=<?= $transaction["id"] ?>"
                                            class="table-action"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="actions/delete-transaction.php?id=<?= $transaction["id"] ?>"
                                            class="table-action delete-action"
                                            onclick="return confirm('Are you sure you want to delete this transaction?');"
                                        >
                                            Delete
                                        </a>

                                    </td>


                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <!-- Empty State -->

                <div class="empty-state">

                    <h3>
                        No transactions found
                    </h3>

                    <p>
                        Try changing your filters or add a new transaction.
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
