<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$userId = $_SESSION["user_id"];


/* Only allow POST */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../budgets.php");
    exit;
}


/* Get form data */

$categoryId = $_POST["category_id"] ?? "";
$amount = $_POST["amount"] ?? "";
$month = $_POST["month"] ?? "";
$year = $_POST["year"] ?? "";


/* Convert empty category to NULL */

if ($categoryId === "") {
    $categoryId = null;
}


/* Validate amount */

if (!is_numeric($amount) || $amount <= 0) {
    die("Invalid budget amount.");
}


/* Validate month */

if (!filter_var($month, FILTER_VALIDATE_INT) || $month < 1 || $month > 12) {
    die("Invalid month.");
}


/* Validate year */

if (!filter_var($year, FILTER_VALIDATE_INT) || $year < 2020 || $year > 2100) {
    die("Invalid year.");
}


/* Validate category if selected */

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


/* Check whether budget already exists */

if ($categoryId === null) {

    $stmt = $pdo->prepare("
        SELECT id
        FROM budgets
        WHERE user_id = ?
        AND category_id IS NULL
        AND month = ?
        AND year = ?
    ");

    $stmt->execute([
        $userId,
        $month,
        $year
    ]);

} else {

    $stmt = $pdo->prepare("
        SELECT id
        FROM budgets
        WHERE user_id = ?
        AND category_id = ?
        AND month = ?
        AND year = ?
    ");

    $stmt->execute([
        $userId,
        $categoryId,
        $month,
        $year
    ]);
}

$existingBudget = $stmt->fetch();


/* If budget already exists, update it */

if ($existingBudget) {

    $stmt = $pdo->prepare("
        UPDATE budgets
        SET amount = ?
        WHERE id = ?
        AND user_id = ?
    ");

    $stmt->execute([
        $amount,
        $existingBudget["id"],
        $userId
    ]);

} else {

    /* Create new budget */

    $stmt = $pdo->prepare("
        INSERT INTO budgets
        (user_id, category_id, amount, month, year)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $categoryId,
        $amount,
        $month,
        $year
    ]);
}


/* Return to budgets */

header("Location: ../budgets.php?success=budget_saved");
exit;