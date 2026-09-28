<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$userId = $_SESSION["user_id"];

$budgetId = $_GET["id"] ?? "";


/* Validate ID */

if (!filter_var($budgetId, FILTER_VALIDATE_INT)) {
    die("Invalid budget ID.");
}


/* Delete only user's own budget */

$stmt = $pdo->prepare("
    DELETE FROM budgets
    WHERE id = ?
    AND user_id = ?
");

$stmt->execute([
    $budgetId,
    $userId
]);


/* Redirect */

header("Location: ../budgets.php?success=budget_deleted");
exit;