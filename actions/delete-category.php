<?php

require_once "../includes/auth.php";
require_once "../includes/message.php";
require_once "../config/database.php";

$userId = $_SESSION["user_id"];

$categoryId = $_GET["id"] ?? "";


/* Validate category ID */

if (!filter_var($categoryId, FILTER_VALIDATE_INT)) {

    setMessage(
        "error",
        "Invalid category ID."
    );

    header("Location: ../categories.php");
    exit;
}


/* Check whether category belongs to current user */

$stmt = $pdo->prepare("
    SELECT id, name
    FROM categories
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $categoryId,
    $userId
]);

$category = $stmt->fetch();


if (!$category) {

    setMessage(
        "error",
        "Category not found."
    );

    header("Location: ../categories.php");
    exit;
}


/* Check whether transactions use this category */

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM transactions
    WHERE category_id = ?
    AND user_id = ?
");

$stmt->execute([
    $categoryId,
    $userId
]);

$transactionCount = (int) $stmt->fetchColumn();


/* Prevent deletion if category is being used */

if ($transactionCount > 0) {

    setMessage(
        "error",
        "Cannot delete this category because it is being used by existing transactions."
    );

    header("Location: ../categories.php");
    exit;
}


/* Delete category */

$stmt = $pdo->prepare("
    DELETE FROM categories
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $categoryId,
    $userId
]);


setMessage(
    "success",
    "Category deleted successfully."
);

header("Location: ../categories.php");
exit;