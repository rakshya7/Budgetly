<?php

require_once "../includes/auth.php";
require_once "../includes/message.php";
require_once "../config/database.php";

$userId = $_SESSION["user_id"];


/* Get form data */

$type = $_POST["type"] ?? "";
$amount = trim($_POST["amount"] ?? "");
$categoryId = $_POST["category_id"] ?? "";
$date = $_POST["transaction_date"] ?? "";
$description = trim($_POST["description"] ?? "");


/* Validate transaction type */

if (!in_array($type, ["income", "expense"])) {

    setMessage(
        "error",
        "Invalid transaction type."
    );

    header("Location: ../add-transaction.php");
    exit;
}


/* Validate amount */

if ($amount === "" || !is_numeric($amount) || $amount <= 0) {

    setMessage(
        "error",
        "Amount must be a valid number greater than 0."
    );

    header("Location: ../add-transaction.php");
    exit;
}


/* Limit amount */

if ($amount > 9999999999.99) {

    setMessage(
        "error",
        "Amount is too large."
    );

    header("Location: ../add-transaction.php");
    exit;
}


/* Validate category ID */

if (!filter_var($categoryId, FILTER_VALIDATE_INT)) {

    setMessage(
        "error",
        "Please select a valid category."
    );

    header("Location: ../add-transaction.php");
    exit;
}


/* Validate date */

$dateObject = DateTime::createFromFormat(
    "Y-m-d",
    $date
);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $date
) {

    setMessage(
        "error",
        "Please enter a valid transaction date."
    );

    header("Location: ../add-transaction.php");
    exit;
}


/* Check category ownership and type */

$stmt = $pdo->prepare("
    SELECT id
    FROM categories
    WHERE id = ?
    AND type = ?
    AND (user_id IS NULL OR user_id = ?)
");

$stmt->execute([
    $categoryId,
    $type,
    $userId
]);

$category = $stmt->fetch();


if (!$category) {

    setMessage(
        "error",
        "Invalid category selected."
    );

    header("Location: ../add-transaction.php");
    exit;
}


/* Insert transaction */

$stmt = $pdo->prepare("
    INSERT INTO transactions
    (
        user_id,
        category_id,
        type,
        amount,
        description,
        transaction_date
    )
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $userId,
    $categoryId,
    $type,
    $amount,
    $description,
    $date
]);


/* Success */

setMessage(
    "success",
    "Transaction added successfully."
);

header("Location: ../dashboard.php");
exit;