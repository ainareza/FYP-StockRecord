<?php
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);
require_once "database.php";
require_once "pagination.php";

$get = fn($key) => (isset($_GET[$key]) && is_string($_GET[$key])) ? $_GET[$key] : "";
$filter_product = $get("product_id");
$filter_type = $get("type");
$filter_user = $get("user_id");
$filter_from = $get("from");
$filter_to = $get("to");
if (!ctype_digit($filter_product)) $filter_product = "";
if (!in_array($filter_type, ["IN", "OUT"], true)) $filter_type = "";
if (!ctype_digit($filter_user)) $filter_user = "";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_from)) $filter_from = "";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_to)) $filter_to = "";

$filters_active = ($filter_product !== "" || $filter_type !== "" || $filter_user !== "" || $filter_from !== "" || $filter_to !== "");
$where = ["1=1"]; $types = ""; $params = [];
if ($filter_product !== "") { $where[] = "`transaction`.product_id = ?"; $types .= "i"; $params[] = (int)$filter_product; }
if ($filter_type !== "") { $where[] = "`transaction`.type = ?"; $types .= "s"; $params[] = $filter_type; }
if ($filter_user !== "") { $where[] = "`transaction`.user_id = ?"; $types .= "i"; $params[] = (int)$filter_user; }
if ($filter_from !== "") { $where[] = "DATE(`transaction`.transaction_date) >= ?"; $types .= "s"; $params[] = $filter_from; }
if ($filter_to !== "") { $where[] = "DATE(`transaction`.transaction_date) <= ?"; $types .= "s"; $params[] = $filter_to; }
$where_sql = implode(" AND ", $where);

$count = $conn->prepare("SELECT COUNT(*) AS total FROM `transaction` WHERE $where_sql");
if ($types !== "") $count->bind_param($types, ...$params);
$count->execute();
$total_rows = (int)$count->get_result()->fetch_assoc()["total"];
$total_pages = max(1, (int)ceil($total_rows / ROWS_PER_PAGE));
$current_page_num = min(get_current_page(), $total_pages);
$offset = ($current_page_num - 1) * ROWS_PER_PAGE;

$sql = "SELECT `transaction`.transaction_id, product.product_name, `transaction`.type, `transaction`.quantity, user.name, `transaction`.transaction_date FROM `transaction` JOIN product ON `transaction`.product_id = product.product_id JOIN user ON `transaction`.user_id = user.user_id WHERE $where_sql ORDER BY `transaction`.transaction_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$all_types = $types . "ii";
$all_params = array_merge($params, [ROWS_PER_PAGE, $offset]);
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$transactions = $stmt->get_result();

$filter_products = $conn->query("SELECT product_id, product_name FROM product ORDER BY product_name");
$filter_users = $conn->query("SELECT DISTINCT user.user_id, user.name FROM user JOIN `transaction` ON user.user_id = `transaction`.user_id ORDER BY user.name");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Stock Transactions</title><link rel="stylesheet" href="style.css"></head>
<body>
<?php include "navbar.php"; ?>

<h2>Stock Transactions</h2>

<form method="GET" class="filter-bar">
    <div class="filter-field"><label>Product</label>
    <select name="product_id"><option value="">All</option><?php while($p=$filter_products->fetch_assoc()): ?>
        <option value="<?php echo $p['product_id']; ?>" <?php echo ((string)$p['product_id']===$filter_product)?'selected':''; ?>><?php echo htmlspecialchars($p['product_name']); ?></option>
        <?php endwhile; ?></select></div>
    <div class="filter-field"><label>Type</label>
    <select name="type"><option value="">All</option>
    <option value="IN" <?php echo $filter_type==='IN'?'selected':''; ?>>IN</option>
    <option value="OUT" <?php echo $filter_type==='OUT'?'selected':''; ?>>OUT</option>
</select>
</div>
    <div class="filter-field"><label>User</label>
    <select name="user_id"><option value="">All</option><?php while($u=$filter_users->fetch_assoc()): ?><option value="<?php echo $u['user_id']; ?>" <?php echo ((string)$u['user_id']===$filter_user)?'selected':''; ?>><?php echo htmlspecialchars($u['name']); ?></option><?php endwhile; ?></select></div>
    <div class="filter-field"><label>From</label>
    <input type="date" name="from" value="<?php echo htmlspecialchars($filter_from); ?>"></div>
    <div class="filter-field"><label>To</label>
    <input type="date" name="to" value="<?php echo htmlspecialchars($filter_to); ?>"></div>
    <button type="submit">Filter</button><?php if($filters_active): ?><a class="reset-link" href="stock_transactions.php">Reset</a><?php endif; ?>
</form>
<table>
    <tr>
        <th>#</th>
        <th>Product</th>
        <th>Type</th>
        <th>Quantity</th>
        <th>User</th>
        <th>Date</th>
    </tr>
<?php if($transactions->num_rows>0): $row_number=1; while($transaction=$transactions->fetch_assoc()): ?><tr><td><?php echo $offset+$row_number++; ?></td><td><?php echo htmlspecialchars($transaction['product_name']); ?></td><td><span class="<?php echo strtolower($transaction['type']); ?>"><?php echo htmlspecialchars($transaction['type']); ?></span></td><td><?php echo htmlspecialchars($transaction['quantity']); ?></td><td><?php echo htmlspecialchars($transaction['name']); ?></td><td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td></tr><?php endwhile; else: ?><tr><td colspan="6"><?php echo $filters_active ? 'No transactions match your filters.' : 'No transactions yet.'; ?></td></tr><?php endif; ?></table>
<?php render_pagination($current_page_num, $total_pages); ?>
</body>
</html>
