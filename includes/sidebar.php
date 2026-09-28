<?php

/*
 * Shared sidebar and mobile top bar.
 *
 * Usage (inside <div class="app-layout">):
 *
 *     <?php $activePage = "dashboard"; require __DIR__ . "/includes/sidebar.php"; ?>
 *
 * $activePage: dashboard | transactions | add-transaction |
 *              budgets | reports | categories | profile
 */

$activePage = $activePage ?? "";

$sbName = $_SESSION["user_name"] ?? "User";

$sbInitial = strtoupper(
    function_exists("mb_substr")
        ? mb_substr($sbName, 0, 1)
        : substr($sbName, 0, 1)
);

$sbIcons = [
    "dashboard"       => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
    "transactions"    => '<path d="M8 3 4 7l4 4"/><path d="M4 7h16"/><path d="m16 21 4-4-4-4"/><path d="M20 17H4"/>',
    "add-transaction" => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
    "budgets"         => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
    "reports"         => '<path d="M3 3v18h18"/><path d="M8 17V9M13 17V5M18 17v-6"/>',
    "categories"      => '<path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7" cy="7" r="1.5"/>',
];

$sbNav = [
    "dashboard"       => ["dashboard.php",       "Dashboard"],
    "transactions"    => ["transactions.php",    "Transactions"],
    "add-transaction" => ["add-transaction.php", "Add Transaction"],
    "budgets"         => ["budgets.php",         "Budgets"],
    "reports"         => ["reports.php",         "Reports"],
    "categories"      => ["categories.php",      "Categories"],
];

?>

<!-- Mobile top bar (hidden on tablet/desktop) -->

<header class="mobile-bar">

    <button
        type="button"
        class="mobile-menu-btn"
        id="sidebarToggle"
        aria-label="Toggle menu"
        aria-controls="sidebar"
        aria-expanded="false"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <a href="dashboard.php" class="mobile-bar-logo">
        <img src="assets/images/logo.png" alt="Expense Tracker">
    </a>

</header>

<div class="sidebar-overlay" id="sidebarOverlay"></div>


<!-- Sidebar -->

<aside class="sidebar" id="sidebar">

    <a href="dashboard.php" class="sidebar-logo">
        <img src="assets/images/logo.png" alt="Expense Tracker">
    </a>


    <nav class="sidebar-nav" aria-label="Main navigation">

        <?php foreach ($sbNav as $sbKey => $sbLink): ?>

            <a
                href="<?= $sbLink[0] ?>"
                title="<?= $sbLink[1] ?>"
                class="<?= $activePage === $sbKey ? "active" : "" ?>"
                <?= $activePage === $sbKey ? 'aria-current="page"' : "" ?>
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <?= $sbIcons[$sbKey] ?>
                </svg>

                <span class="nav-label"><?= $sbLink[1] ?></span>
            </a>

        <?php endforeach; ?>

    </nav>


    <div class="sidebar-bottom">

        <a
            href="profile.php"
            title="My profile"
            class="sidebar-profile <?= $activePage === "profile" ? "active" : "" ?>"
            <?= $activePage === "profile" ? 'aria-current="page"' : "" ?>
        >
            <span class="profile-avatar">
                <?= htmlspecialchars($sbInitial) ?>
            </span>

            <span class="profile-info">
                <strong><?= htmlspecialchars($sbName) ?></strong>
                <span>View profile</span>
            </span>
        </a>

        <a href="logout.php" class="sidebar-logout" title="Logout">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <path d="m16 17 5-5-5-5"/>
                <path d="M21 12H9"/>
            </svg>

            <span class="nav-label">Logout</span>
        </a>

    </div>

</aside>

<script src="assets/js/sidebar.js"></script>
