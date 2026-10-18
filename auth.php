<?php
/* auth.php
   Shared login + role-based access helpers.
   Include this AFTER session_start() on every protected page,
   in place of the old manual isset($_SESSION["user_id"]) check. */

require_once "database.php";

define("ROLE_OWNER", 1);
define("ROLE_STOREKEEPER", 2);
define("ROLE_SALES_ASSOCIATE", 3);

// Require any logged-in user, regardless of role.
// Also re-checks the database on every request so that a deactivated
// account is signed out immediately, not only at its next sign in.
function require_login() {
    // Tell the browser never to cache this page. Without this, pressing
    // the Back button after logout can show a stale cached copy of a
    // protected page instead of asking the server again — which means
    // the server never gets the chance to notice the session is gone.
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION["user_id"])) {
        header("Location: signin.php");
        exit;
    }

    global $conn;

    $stmt = $conn->prepare("SELECT is_active FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !$row["is_active"]) {
        session_unset();
        session_destroy();
        header("Location: signin.php");
        exit;
    }
}

// Require the logged-in user's role to be one of the allowed roles.
// Example: require_role([ROLE_OWNER, ROLE_STOREKEEPER]);
function require_role(array $allowed_roles) {
    require_login();

    if (!in_array($_SESSION["role_id"], $allowed_roles)) {
        header("Location: access_denied.php");
        exit;
    }
}

// Convenience check for use inside a page (e.g. to hide a button),
// rather than blocking the whole page.
function current_user_has_role(array $roles) {
    return isset($_SESSION["role_id"]) && in_array($_SESSION["role_id"], $roles);
}