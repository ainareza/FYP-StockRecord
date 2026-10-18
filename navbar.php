<?php
/* navbar.php
   Shared navigation bar, included on every logged-in page
   so the links and styling stay identical everywhere.
   Assumes session_start() has already been called by the
   including page. */

$current_page = basename($_SERVER["PHP_SELF"]);

// Map sub-pages (add/edit/delete) to the nav item they belong under,
// so e.g. add_product.php still highlights "Products".
$page_group = [
    "index.php"              => "index.php",
    "products.php"           => "products.php",
    "add_product.php"        => "products.php",
    "edit_product.php"       => "products.php",
    "categories.php"         => "categories.php",
    "add_category.php"       => "categories.php",
    "edit_category.php"      => "categories.php",
    "stock_transactions.php" => "stock_transactions.php",
    "add_transaction.php"    => "stock_transactions.php",
    "low_stock.php"          => "low_stock.php",
    "reports.php"            => "reports.php",
    "activity_log.php"       => "activity_log.php",
    "profile.php"            => "profile.php",
    "manage_staff.php"       => "manage_staff.php",
    "add_staff.php"           => "manage_staff.php",
    "edit_staff.php"          => "manage_staff.php",
];

$active_page = $page_group[$current_page] ?? "";

// Back button shown at the top left of each page (under the navbar).
// Form pages go back to their own list; list pages go back to the Dashboard.
// [target file, label]. Pages not listed here (e.g. index.php) get no button.
$back_links = [
    // list pages -> Dashboard
    "products.php"           => ["index.php", "Dashboard"],
    "categories.php"         => ["index.php", "Dashboard"],
    "stock_transactions.php" => ["index.php", "Dashboard"],
    "low_stock.php"          => ["index.php", "Dashboard"],
    "activity_log.php"       => ["index.php", "Dashboard"],
    "reports.php"            => ["index.php", "Dashboard"],
    "manage_staff.php"       => ["index.php", "Dashboard"],
    "profile.php"            => ["index.php", "Dashboard"],
    // form pages -> their list
    "add_product.php"        => ["products.php", "Products"],
    "edit_product.php"       => ["products.php", "Products"],
    "add_category.php"       => ["categories.php", "Categories"],
    "edit_category.php"      => ["categories.php", "Categories"],
    "add_transaction.php"    => ["stock_transactions.php", "Transactions"],
    "add_staff.php"          => ["manage_staff.php", "Manage Staff"],
    "edit_staff.php"         => ["manage_staff.php", "Manage Staff"],
];

// "Add" button shown at the top right of a list page, alongside its back
// button. [target file, label, allowed roles or null for anyone who can
// already see the page]. Only list pages that have an Add action appear here.
$add_links = [
    "products.php"           => ["add_product.php", "Add Product", [ROLE_OWNER, ROLE_STOREKEEPER]],
    "categories.php"         => ["add_category.php", "Add Category", null],
    "stock_transactions.php" => ["add_transaction.php", "Record Transaction", null],
    "manage_staff.php"       => ["add_staff.php", "Add Staff", null],
];

$role_id = $_SESSION["role_id"] ?? null;
$can_manage_stock = in_array($role_id, [ROLE_OWNER, ROLE_STOREKEEPER]);
$is_owner = $role_id === ROLE_OWNER;

// Resolve the add link for THIS page, respecting its role restriction
$add_link = null;
if (isset($add_links[$current_page])) {
    [$add_href, $add_label, $add_roles] = $add_links[$current_page];
    if ($add_roles === null || in_array($role_id, $add_roles)) {
        $add_link = [$add_href, $add_label];
    }
}

function nav_class($page, $active_page) {
    return $page === $active_page ? "active" : "";
}
?>
<nav class="navbar">
    <div class="navdiv">

        <div class="logo">
            <a href="index.php">StockRecord</a>
        </div>

        <ul>
            <li><a href="index.php" class="<?php echo nav_class('index.php', $active_page); ?>">Home</a></li>
            <?php if ($can_manage_stock): ?>
                <li><a href="categories.php" class="<?php echo nav_class('categories.php', $active_page); ?>">Categories</a></li>
            <?php endif; ?>
            <li><a href="products.php" class="<?php echo nav_class('products.php', $active_page); ?>">Products</a></li>

            <?php if ($can_manage_stock): ?>
                <li><a href="stock_transactions.php" class="<?php echo nav_class('stock_transactions.php', $active_page); ?>">Transactions</a></li>
                <li><a href="low_stock.php" class="<?php echo nav_class('low_stock.php', $active_page); ?>">Low Stock</a></li>
                <li><a href="reports.php" class="<?php echo nav_class('reports.php', $active_page); ?>">Report</a></li>
                <li><a href="activity_log.php" class="<?php echo nav_class('activity_log.php', $active_page); ?>">Activity Log</a></li>
            <?php endif; ?>

            <?php if (isset($_SESSION["user_id"])): ?>
                <li><a href="profile.php" class="<?php echo nav_class('profile.php', $active_page); ?>">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="signin.php">Sign In</a></li>
            <?php endif; ?>
        </ul>

    </div>
</nav>

<?php if (isset($back_links[$current_page]) || $add_link): ?>
<div class="page-actions">
    <?php if (isset($back_links[$current_page])): ?>
        <a class="back-button" href="<?php echo $back_links[$current_page][0]; ?>">&larr; Back to <?php echo $back_links[$current_page][1]; ?></a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>

    <?php if ($add_link): ?>
        <a class="add-button" href="<?php echo $add_link[0]; ?>">+ <?php echo $add_link[1]; ?></a>
    <?php endif; ?>
</div>
<?php endif; 
?>