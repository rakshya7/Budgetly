<?php

require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/message.php";

$userId = $_SESSION["user_id"];

$name = trim($_POST["name"] ?? "");
$type = $_POST["type"] ?? "";


/* Validate */

if ($name === "") {
    setMessage("error", "Category name is required.");
header("Location: ../categories.php");
exit;
}

if (!in_array($type, ["income", "expense"])) {
    setMessage("error", "Invalid category type.");
header("Location: ../categories.php");
exit;
}


/* Check duplicate */

$stmt = $pdo->prepare("
    SELECT id
    FROM categories
    WHERE user_id = ?
    AND name = ?
    AND type = ?
");

$stmt->execute([
    $userId,
    $name,
    $type
]);

if ($stmt->fetch()) {
    setMessage(
    "error",
    "You already have a category with this name."
);

header("Location: ../categories.php");
exit;
}


/* Insert */

$stmt = $pdo->prepare("
    INSERT INTO categories
    (user_id, name, type)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $userId,
    $name,
    $type
]);


setMessage(
    "success",
    "Category added successfully."
);

header("Location: ../categories.php");
exit;