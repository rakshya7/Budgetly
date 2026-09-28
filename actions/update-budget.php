<?php

require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/message.php";

$userId = $_SESSION["user_id"];


/* Only allow POST */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../budgets.php");
    exit;
}


/* Get form data */

$budgetId = $_POST["id"] ?? "";
$categoryId = $_POST["category_id"] ?? "";
$amount = $_POST["amount"] ?? "";
$month = $_POST["month"] ?? "";
$year = $_POST["year"] ?? "";


/* Convert empty category to NULL */

if ($categoryId === "") {
    $categoryId = null;
}


/* Validate budget ID */

if (!filter_var($budgetId, FILTER_VALIDATE_INT)) {
    die("Invalid budget ID.");
}


/* Validate amount */

if (!is_numeric($amount) || $amount <= 0) {
    setMessage(
    "error",
    "Invalid budget amount."
);

header("Location: ../budgets.php");
exit;
}


/* Validate month */

if (!filter_var($month, FILTER_VALIDATE_INT) || $month < 1 || $month > 12) {
    die("Invalid month.");
}


/* Validate year */

if (!filter_var($year, FILTER_VALIDATE_INT) || $year < 2020 || $year > 2100) {
    die("Invalid year.");
}


/* Validate category */

if ($categoryId !== null) {

    if (!filter_var($categoryId, FILTER_VALIDATE_INT)) {
        die("Invalid category.");
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM categories
        WHERE id = ?
        AND type = 'expense'
        AND (user_id IS NULL OR user_id = ?)
    ");

    $stmt->execute([
        $categoryId,
        $userId
    ]);

    if (!$stmt->fetch()) {
        die("Invalid category selected.");
    }
}


/* Check budget belongs to user */

$stmt = $pdo->prepare("
    SELECT id
    FROM budgets
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $budgetId,
    $userId
]);

if (!$stmt->fetch()) {
    die("Budget not found.");
}


/* Update budget */

$stmt = $pdo->prepare("
    UPDATE budgets
    SET
        category_id = ?,
        amount = ?,
        month = ?,
        year = ?
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $categoryId,
    $amount,
    $month,
    $year,
    $budgetId,
    $userId
]);


/* Redirect */

setMessage(
    "success",
    "Budget saved successfully."
);

header("Location: ../budgets.php");
exit;