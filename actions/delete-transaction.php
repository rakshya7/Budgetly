<?php

require_once "../includes/auth.php";
require_once "../includes/message.php";
require_once "../config/database.php";

$userId = $_SESSION["user_id"];

$transactionId = $_GET["id"] ?? "";


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


/* Delete transaction */

$stmt = $pdo->prepare("
    DELETE FROM transactions
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $transactionId,
    $userId
]);


/* Success */

setMessage(
    "success",
    "Transaction deleted successfully."
);

header("Location: ../transactions.php");
exit;