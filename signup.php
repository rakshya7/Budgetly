<?php

session_start();
require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {
        $error = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";

    } else {
        $check = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = "An account with this email already exists.";

        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $email,
                $hashedPassword
            ]);

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Create Account | Budgetly</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-page">

    <div class="logo">
        <img src="assets/images/logo.png" alt="Budgetly Logo">
    </div>

    <div class="auth-card">
        <h1>Create Account</h1>
        <p class="muted">
            Start managing your money today.
        </p>

        <?php if ($error !== ""): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">

            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                required
                maxlength="100"
                value="<?= htmlspecialchars($name ?? '') ?>"
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                required
                maxlength="150"
                value="<?= htmlspecialchars($email ?? '') ?>"
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

            <button type="submit" class="btn-primary">
                Create Account
            </button>

        </form>

        <p class="auth-footer">
            Already have an account?
            <a href="login.php">Login</a>
        </p>
    </div>

</body>
</html>