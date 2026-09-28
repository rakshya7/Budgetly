<?php

require_once "includes/auth.php";
require_once "config/database.php";
require_once "includes/message.php";

$userId = $_SESSION["user_id"];


/* Get user's custom categories */

$stmt = $pdo->prepare("
    SELECT id, name, type
    FROM categories
    WHERE user_id = ?
    ORDER BY type, name
");

$stmt->execute([$userId]);

$categories = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Categories - Expense Tracker</title>

    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>

<div class="app-layout">


    <!-- =========================
         Sidebar
    ========================= -->

    <?php $activePage = "categories"; require __DIR__ . "/includes/sidebar.php"; ?>


    <!-- =========================
         Main Content
    ========================= -->

    <main class="main-content">


        <header class="topbar">
        <div>

            <h1>
                Categories
            </h1>

            <p>
                Create and manage your personal categories
            </p>

        </div>
        </header>


        <?php displayMessage(); ?>


        <!-- =========================
             Add Category
        ========================= -->

        <section class="category-form">

            <h2>
                Add New Category
            </h2>


            <form
                method="POST"
                action="actions/save-category.php"
            >

                <div class="form-row">


                    <div class="form-group">

                        <label for="name">
                            Category Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            placeholder="e.g. Gym"
                            required
                            maxlength="100"
                        >

                    </div>


                    <div class="form-group">

                        <label for="type">
                            Type
                        </label>

                        <select
                            name="type"
                            id="type"
                            required
                        >

                            <option value="expense">
                                Expense
                            </option>

                            <option value="income">
                                Income
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="add-button"
                    >
                        Add Category
                    </button>


                </div>

            </form>

        </section>


        <!-- =========================
             Categories
        ========================= -->

        <div class="category-grid">


            <!-- Expense Categories -->

            <section class="category-card">

                <h2>
                    Expense Categories
                </h2>


                <?php

                $expenseFound = false;

                foreach ($categories as $category):

                    if ($category["type"] !== "expense") {
                        continue;
                    }

                    $expenseFound = true;

                ?>

                    <div class="category-item">

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $category["name"]
                            );
                            ?>
                        </span>


                        <a
                            href="actions/delete-category.php?id=<?php echo $category["id"]; ?>"
                            class="delete-button"
                            onclick="return confirm('Delete this category?');"
                        >
                            Delete
                        </a>

                    </div>

                <?php endforeach; ?>


                <?php if (!$expenseFound): ?>

                    <p class="empty-message">
                        No custom expense categories yet.
                    </p>

                <?php endif; ?>

            </section>


            <!-- Income Categories -->

            <section class="category-card">

                <h2>
                    Income Categories
                </h2>


                <?php

                $incomeFound = false;

                foreach ($categories as $category):

                    if ($category["type"] !== "income") {
                        continue;
                    }

                    $incomeFound = true;

                ?>

                    <div class="category-item">

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $category["name"]
                            );
                            ?>
                        </span>


                        <a
                            href="actions/delete-category.php?id=<?php echo $category["id"]; ?>"
                            class="delete-button"
                            onclick="return confirm('Delete this category?');"
                        >
                            Delete
                        </a>

                    </div>

                <?php endforeach; ?>


                <?php if (!$incomeFound): ?>

                    <p class="empty-message">
                        No custom income categories yet.
                    </p>

                <?php endif; ?>

            </section>


        </div>


    </main>

</div>

</body>

</html>