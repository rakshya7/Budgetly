<?php

require_once "../includes/auth.php";
require_once "../includes/message.php";
require_once "../config/database.php";

$userId = $_SESSION["user_id"];


/* Get form data */

$transactionId = $_POST["id"] ?? "";
$type = $_POST["type"] ?? "";
$amount = trim($_POST["amount"] ?? "");
$categoryId = $_POST["category_id"] ?? "";
$date = $_POST["transaction_date"] ?? "";
$description = trim($_POST["description"] ?? "");


/* Validate transaction ID */

if (!filter_var($transactionId, FILTER_VALIDATE_INT)) {

    setMessage(
        "error",
        "Invalid transaction ID."
    );

    header("Location: ../transactions.php");
    exit;
}


/* Check transaction ownership */

$stmt = $pdo->prepare("
    SELECT id
    FROM transactions
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $transactionId,
    $userId
]);

$transaction = $stmt->fetch();


if (!$transaction) {

    setMessage(
        "error",
        "Transaction not found."
    );

    header("Location: ../transactions.php");
    exit;
}


/* Validate transaction type */

if (!in_array($type, ["income", "expense"])) {

    setMessage(
        "error",
        "Invalid transaction type."
    );

    header(
        "Location: ../edit-transaction.php?id="
        . $transactionId
    );

    exit;
}


/* Validate amount */

if (
    $amount === "" ||
    !is_numeric($amount) ||
    $amount <= 0
) {

    setMessage(
        "error",
        "Amount must be a valid number greater than 0."
    );

    header(
        "Location: ../edit-transaction.php?id="
        . $transactionId
    );

    exit;
}


/* Limit amount */

if ($amount > 9999999999.99) {

    setMessage(
        "error",
        "Amount is too large."
    );

    header(
        "Location: ../edit-transaction.php?id="
        . $transactionId
    );

    exit;
}


/* Validate category */

if (!filter_var($categoryId, FILTER_VALIDATE_INT)) {

    setMessage(
        "error",
        "Please select a valid category."
    );

    header(
        "Location: ../edit-transaction.php?id="
        . $transactionId
    );

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

    header(
        "Location: ../edit-transaction.php?id="
        . $transactionId
    );

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

    header(
        "Location: ../edit-transaction.php?id="
        . $transactionId
    );

    exit;
}


/* Update transaction */

$stmt = $pdo->prepare("
    UPDATE transactions
    SET
        category_id = ?,
        type = ?,
        amount = ?,
        description = ?,
        transaction_date = ?
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $categoryId,
    $type,
    $amount,
    $description,
    $date,
    $transactionId,
    $userId
]);


/* Success */

setMessage(
    "success",
    "Transaction updated successfully."
);

header("Location: ../transactions.php");
exit;