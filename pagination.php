<?php
/* pagination.php
   Shared pagination logic for list pages that support filters.
   Keeps filter values in the URL when moving between pages, so
   "page 2 of a filtered search" works correctly. */

define("ROWS_PER_PAGE", 10);

// Reads ?page= from the URL, always returns a valid page number >= 1.
function get_current_page() {
    $page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
    return max(1, $page);
}

// Renders Prev / page numbers / Next, preserving every other GET
// parameter (filters) already in the URL except "page" itself.
function render_pagination($current_page_num, $total_pages) {
    if ($total_pages <= 1) {
        return;
    }

    $build_url = function ($page) {
        $params = $_GET;
        $params["page"] = $page;
        return "?" . http_build_query($params);
    };

    echo '<div class="pagination">';

    if ($current_page_num > 1) {
        echo '<a href="' . htmlspecialchars($build_url($current_page_num - 1)) . '">&laquo; Prev</a>';
    } else {
        echo '<span class="disabled">&laquo; Prev</span>';
    }

    for ($p = 1; $p <= $total_pages; $p++) {
        if ($p == $current_page_num) {
            echo '<span class="active">' . $p . '</span>';
        } else {
            echo '<a href="' . htmlspecialchars($build_url($p)) . '">' . $p . '</a>';
        }
    }

    if ($current_page_num < $total_pages) {
        echo '<a href="' . htmlspecialchars($build_url($current_page_num + 1)) . '">Next &raquo;</a>';
    } else {
        echo '<span class="disabled">Next &raquo;</span>';
    }

    echo '</div>';
}