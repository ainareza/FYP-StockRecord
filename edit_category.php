<?php

/* this is edit_category.php 
purpose: to allow user to edit a category's name.
Categories now link to products via the product_category
junction table (many-to-many), not a direct FK on product. */

session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";

$message = "";

if (!isset($_GET["id"])) {
    header("Location: categories.php");
    exit;
}

$category_id = $_GET["id"];

// Get current category
$stmt = $conn->prepare("
    SELECT category_name
    FROM category
    WHERE category_id = ?
");

$stmt->bind_param("i", $category_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: categories.php");
    exit;
}

$category = $result->fetch_assoc();


// Update category
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $category_name = trim($_POST["category_name"]);

    if (empty($category_name)) {

        $message = "Please enter a category name.";

    } else {

        $update = $conn->prepare("
            UPDATE category
            SET category_name = ?
            WHERE category_id = ?
        ");

        $update->bind_param(
            "si",
            $category_name,
            $category_id
        );

        if ($update->execute()) {

            header("Location: categories.php");
            exit;

        } else {

            $message = "Failed to update category.";

        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Category</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include "navbar.php"; ?>

<h2>Edit Category</h2>

<?php if (!empty($message)): ?>

    <p><?php echo htmlspecialchars($message); ?></p>

<?php endif; ?>

<form method="POST">

    <label>Category Name:</label>

    <input
        type="text"
        name="category_name"
        value="<?php echo htmlspecialchars($category["category_name"]); ?>"
        required
    >

    <button type="submit">Update Category</button>

</form>


</body>
</html>