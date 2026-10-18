<!-- This is index.hmtl -->
<?php
session_start();
require_once "auth.php";
require_login();

require_once "database.php";

$can_manage_stock = current_user_has_role([ROLE_OWNER, ROLE_STOREKEEPER]);

//total items
$result = $conn->query("SELECT COUNT(*) AS total_items FROM product");
$row = $result->fetch_assoc();
$total_items = $row['total_items'];

//low stock items
$result = $conn->query("SELECT COUNT(*) AS low_stock FROM product WHERE quantity <= minimum_stock");
$row = $result->fetch_assoc();
$low_stock = $row['low_stock'];

//total transactions
$result = $conn->query("SELECT COUNT(*) AS total_transactions FROM `transaction`");
$row = $result->fetch_assoc();
$total_transactions = $row['total_transactions'];

//recent transactions (with optional filters from the URL)
$get = fn($key) => (isset($_GET[$key]) && is_string($_GET[$key])) ? $_GET[$key] : "";

$filter_type    = $get("type");
$filter_product = $get("product_id");
$filter_from    = $get("from");
$filter_to      = $get("to");

// Only accept values in the expected format; anything else is ignored
if (!in_array($filter_type, ["IN", "OUT"]))                    $filter_type = "";
if (!ctype_digit($filter_product))                              $filter_product = "";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_from))        $filter_from = "";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_to))          $filter_to = "";

$where  = [];
$types  = "";
$params = [];

if ($filter_type !== "") {
    $where[]  = "`transaction`.type = ?";
    $types   .= "s";
    $params[] = $filter_type;
}
if ($filter_product !== "") {
    $where[]  = "`transaction`.product_id = ?";
    $types   .= "i";
    $params[] = (int) $filter_product;
}
if ($filter_from !== "") {
    $where[]  = "DATE(`transaction`.transaction_date) >= ?";
    $types   .= "s";
    $params[] = $filter_from;
}
if ($filter_to !== "") {
    $where[]  = "DATE(`transaction`.transaction_date) <= ?";
    $types   .= "s";
    $params[] = $filter_to;
}

$filters_active = count($where) > 0;

$sql = "
    SELECT
        `transaction`.transaction_id,
        product.product_name,
        `transaction`.type,
        `transaction`.quantity,
        user.name,
        `transaction`.transaction_date
    FROM `transaction`
    JOIN product
        ON `transaction`.product_id = product.product_id
    JOIN user
        ON `transaction`.user_id = user.user_id
";

if ($filters_active) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY `transaction`.transaction_date DESC";

$recent_stmt = $conn->prepare($sql);
if ($filters_active) {
    $recent_stmt->bind_param($types, ...$params);
}
$recent_stmt->execute();
$recent_transactions = $recent_stmt->get_result();

// Products for the filter dropdown
$filter_products = $conn->query("
    SELECT product_id, product_name
    FROM product
    ORDER BY product_name
");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Inventory Management System</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Navigation Bar -->
    <?php include "navbar.php"; ?>


    <!-- Welcome Section -->
    <section class="welcome">
        <h2>Welcome!</h2>
        <p>Your inventory overview</p>
    </section>


    <!-- Dashboard -->
    <section class="dashboard">

        <div class="card">
            <h3>Total Items</h3>
            <p><?php echo $total_items; ?></p>
        </div>

        <?php if ($can_manage_stock): ?>

        <div class="card <?php echo ($low_stock > 0) ? "card-danger" : ""; ?>">
            <h3>
                <a href="low_stock.php">Low Stock</a>
            </h3>
            <p><?php echo $low_stock; ?></p>
        </div>

        <div class="card">
            <h3>Transactions</h3>
            <p><?php echo $total_transactions; ?></p>
        </div>

        <?php endif; ?>

    </section>

<?php if ($can_manage_stock): ?>
<!-- Recent Transactions -->
<section class="recent">

    <h2>Recent Transactions</h2>

    <form method="GET" action="index.php" class="filter-bar">

        <div class="filter-field">
            <label for="filter-type">Type</label>
            <select name="type" id="filter-type">
                <option value="">All</option>
                <option value="IN" <?php echo $filter_type === "IN" ? "selected" : ""; ?>>IN</option>
                <option value="OUT" <?php echo $filter_type === "OUT" ? "selected" : ""; ?>>OUT</option>
            </select>
        </div>

        <div class="filter-field">
            <label for="filter-product">Product</label>
            <select name="product_id" id="filter-product">
                <option value="">All</option>
                <?php while ($p = $filter_products->fetch_assoc()): ?>
                    <option value="<?php echo $p["product_id"]; ?>"
                        <?php echo ((string) $p["product_id"] === $filter_product) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($p["product_name"]); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="filter-from">From</label>
            <input type="date" name="from" id="filter-from"
                   value="<?php echo htmlspecialchars($filter_from); ?>">
        </div>

        <div class="filter-field">
            <label for="filter-to">To</label>
            <input type="date" name="to" id="filter-to"
                   value="<?php echo htmlspecialchars($filter_to); ?>">
        </div>

        <button type="submit">Filter</button>

        <?php if ($filters_active): ?>
            <a class="reset-link" href="index.php">Reset</a>
        <?php endif; ?>

    </form>

    <div class="recent-table">

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>User</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>

            <?php if ($recent_transactions->num_rows > 0): ?>

                <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['name']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5"><?php echo $filters_active ? "No transactions match your filters." : "No transactions yet."; ?></td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>


    <!-- Quick Actions -->
    <?php if ($can_manage_stock): ?>
    <section class="quick-actions">

        <div class="box" onclick="location.href='stock_transactions.php'">
            <span class="title">Transactions</span>
            <p class="desc">View your transaction history</p>
        </div>

        <div class="box" onclick="location.href='low_stock.php'">
            <span class="title">Low Stock Items</span>
            <p class="desc">Check your stock levels</p>
        </div>

        <div class="box" onclick="location.href='reports.php'">
            <span class="title">Report</span>
            <p class="desc">Get your inventory report</p>
        </div>

        <?php if (current_user_has_role([ROLE_OWNER])): ?>
        <div class="box" onclick="location.href='manage_staff.php'">
            <span class="title">Manage Staff</span>
            <p class="desc">Add or deactivate staff accounts</p>
        </div>
        <?php endif; ?>

    </section>
    <?php endif; ?>

</body>
</html>