# UI refactor

## New files
- includes/sidebar.php  — the one sidebar (logo, nav, profile, logout) + mobile top bar. Set `$activePage` before including.
- assets/js/sidebar.js  — opens/closes the mobile drawer.
- profile.php           — read-only profile page (name, email, activity totals).

## Changed
- All app pages (dashboard, transactions, add/edit-transaction, budgets, edit-budget, reports, categories)
  now include the shared sidebar instead of carrying their own copy, and use the same
  `.app-layout` / `.main-content` / `.topbar` structure.
- Flash messages on dashboard and transactions now sit under the page header
  (they were rendered between the title and the subtitle).
- assets/css/style.css rewritten as one file (~1,800 lines of duplicated and dead rules -> one
  sectioned stylesheet). The `.reports-sidebar` / `.categories-sidebar` / `.reports-layout` /
  `.categories-layout` rules and every duplicated block are gone.

## Not touched
- All queries, actions/, config/, includes/auth.php, includes/message.php, database structure.

## Includes earlier bug fixes
See the previous bug-fix list (leading blank lines, stray ```, budget action messages, etc.).
