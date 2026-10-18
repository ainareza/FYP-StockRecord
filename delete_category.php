<?php
/* this is delete_category.php
deleting a category can cause database error.
TODO: find better handling method
current method: disallow category deletion 
if it is used by products.*/

session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";

if (!isset($_GET["id"])) {
    header("Location: categories.php");
    exit;
}

$category_id = $_GET["id"];

// Check whether any products are linked to this category
$check = $conn->prepare("
    SELECT product_id
    FROM product_category
    WHERE category_id = ?
");

$check->bind_param("i", $category_id);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<script>
        alert('Cannot delete this category because it is still linked to one or more products.');
        window.location.href = 'categories.php';
    </script>";
    exit;
}

// Delete category
$stmt = $conn->prepare("
    DELETE FROM category
    WHERE category_id = ?
");

$stmt->bind_param("i", $category_id);

if ($stmt->execute()) {
    header("Location: categories.php");
    exit;
} else {
    echo "Failed to delete category.";
}

?>
