<!-- this is add_transaction.php -->

<?php
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_id = (int) ($_POST["product_id"] ?? 0);
    $type = $_POST["type"] ?? "";
    $quantity = (int) ($_POST["quantity"] ?? 0);

    if ($product_id <= 0 || $type === "" || $quantity <= 0) {

        $message = "Please fill in all fields correctly.";

    } elseif (!in_array($type, ["IN", "OUT"], true)) {

        $message = "Invalid transaction type.";

    } elseif ($quantity <= 0) {

        $message = "Quantity must be greater than 0.";

    } else {

        // Get current stock
        $stmt = $conn->prepare("
            SELECT product_name, quantity
            FROM product
            WHERE product_id = ?
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {

            $message = "Product not found.";

        } else {

            $current_quantity = $product["quantity"];

            // Check Stock OUT
            if ($type == "OUT" && $quantity > $current_quantity) {

                $message = "Not enough stock available.";

            } else {

                // Calculate new quantity
                if ($type == "IN") {
                    $new_quantity = $current_quantity + $quantity;
                } else {
                    $new_quantity = $current_quantity - $quantity;
                }

                $conn->begin_transaction();

                try {
                    $update = $conn->prepare("UPDATE product SET quantity = ? WHERE product_id = ?");
                    $update->bind_param("ii", $new_quantity, $product_id);
                    if (!$update->execute()) {
                        throw new Exception("Stock could not be updated.");
                    }

                    $transaction = $conn->prepare("INSERT INTO `transaction` (product_id, user_id, type, quantity) VALUES (?, ?, ?, ?)");
                    $transaction->bind_param("iisi", $product_id, $_SESSION["user_id"], $type, $quantity);
                    if (!$transaction->execute()) {
                        throw new Exception("Transaction could not be recorded.");
                    }

                    $action = "Stock Transaction";
                    $description = "Recorded Stock " . $type . " for " . $quantity . " unit(s) of " . $product["product_name"] . ".";
                    $log = $conn->prepare("INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)");
                    $log->bind_param("iss", $_SESSION["user_id"], $action, $description);
                    if (!$log->execute()) {
                        throw new Exception("Activity log could not be recorded.");
                    }

                    $conn->commit();
                    header("Location: stock_transactions.php");
                    exit;
                } catch (Throwable $e) {
                    $conn->rollback();
                    $message = $e->getMessage();
                }

            }
        }
    }
}


// Get products for dropdown
$products = $conn->query("
    SELECT product_id, product_name, quantity
    FROM product
    ORDER BY product_name
");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Record Transaction</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "navbar.php"; ?>


    <h2>Record Stock Transaction</h2>

    <?php if ($message != ""): ?>

        <p><?php echo htmlspecialchars($message); ?></p>

    <?php endif; ?>


    <form method="POST">

        <label>Product</label>

        <select name="product_id" required>

            <option value="">Select Product</option>

            <?php while ($product = $products->fetch_assoc()): ?>

                <option value="<?php echo $product["product_id"]; ?>">

                    <?php echo htmlspecialchars($product["product_name"]); ?>

                </option>

            <?php endwhile; ?>

        </select>

        <br><br>


        <label>Transaction Type</label>

        <select name="type" required>

            <option value="">Select Type</option>
            <option value="IN">Stock IN</option>
            <option value="OUT">Stock OUT</option>

        </select>

        <br><br>


        <label>Quantity</label>

        <input
            type="number"
            name="quantity"
            min="1"
            required
        >

        <br><br>


        <button type="submit">Record Transaction</button>

    </form>



</body>

</html>