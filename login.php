<?php

session_start();
require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare(
        "SELECT id, name, email, password
         FROM users
         WHERE email = ?"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];

        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | Expense Tracker</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-page">
    <div class="logo">
    <img src="assets/images/logo.png" alt="Expense Tracker Logo">
</div>

    <div class="auth-card">
        <h1>Welcome Back!</h1>
        <p class="muted">
            Login to manage your finances.
        </p>

        <?php if (isset($_GET["registered"])): ?>
            <div class="success-message">
                Account created successfully. Please log in.
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >

            <button type="submit" class="btn-primary">
                Login
            </button>

        </form>

        <p class="auth-footer">
            Don't have an account?
            <a href="signup.php">Sign up</a>
        </p>
    </div>

</body>
</html>