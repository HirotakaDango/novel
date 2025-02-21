<?php
// Database Initialization
$db = new SQLite3('novel_db.sqlite');
$db->exec("
    CREATE TABLE IF NOT EXISTS settings (
        setting_name TEXT PRIMARY KEY,
        setting_value TEXT
    );
    CREATE TABLE IF NOT EXISTS novels (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS chapters (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        novel_id INTEGER,
        chapter_number INTEGER,
        title TEXT,
        content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(novel_id, chapter_number),
        FOREIGN KEY (novel_id) REFERENCES novels(id)
    );
");

// Settings Functions
function get_setting($db, $setting_name) {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_name = :setting_name");
    $stmt->bindValue(':setting_name', $setting_name, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row ? $row['setting_value'] : null;
}

function set_setting($db, $setting_name, $setting_value) {
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (setting_name, setting_value) VALUES (:setting_name, :setting_value)");
    $stmt->bindValue(':setting_name', $setting_name, SQLITE3_TEXT);
    $stmt->bindValue(':setting_value', $setting_value, SQLITE3_TEXT);
    $stmt->execute();
}

// Password Management
function is_owner() {
    session_start();
    return isset($_SESSION['owner']) && $_SESSION['owner'] === true;
}

function authenticate_owner($db, $password) {
    $hashed_password = get_setting($db, 'admin_password');
    if ($hashed_password && password_verify($password, $hashed_password)) {
        session_start();
        $_SESSION['owner'] = true;
        return true;
    }
    return false;
}

function setup_password_form() {
    echo '<div class="container mt-5">';
    echo '<h2 class="fw-bold fs-2 mb-5 text-white">Set Up Password</h2>';
    echo '<form method="post" action="./?section=admin_setup" class="p-4 bg-dark rounded-3">';
    echo '<div class="mb-4">';
    echo '<input type="password" class="form-control rounded-pill px-4" id="password" name="password" placeholder="Enter password" required>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary rounded-pill w-100">Set Password</button>';
    echo '</form>';
    echo '</div>';
}

function admin_login_form() {
    echo '<div class="container mt-5">';
    echo '<h2 class="fw-bold fs-2 mb-5 text-white">Login</h2>';
    echo '<form method="post" action="./?section=admin_login" class="p-4 bg-dark rounded-3">';
    echo '<div class="mb-4">';
    echo '<input type="password" class="form-control rounded-pill px-4" id="password" name="password" placeholder="Enter password" required>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary rounded-pill w-100">Login</button>';
    echo '</form>';
    echo '</div>';
}

// Input Sanitization
function sanitize_input($input) {
    return filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);
}

// New function to strip HTML tags
function strip_tags_input($input) {
    return strip_tags($input);
}


// --- Routing and Page Rendering ---
$section = $_GET['section'] ?? 'home';
$title_param = sanitize_input($_GET['title'] ?? '');
$page = intval($_GET['page'] ?? 1);
$chapter_param = intval($_GET['chapter'] ?? 0);

if (!isset($_GET['section'])) {
    if (!empty($title_param) && $chapter_param > 0) {
        $section = 'chapter';
    } elseif (!empty($title_param)) {
        $section = 'title';
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg" href="https://icons.getbootstrap.com/assets/icons/book.svg">
    <style>
        body {
            padding-top: 80px;
            background-color: #212121;
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .chapter-content { white-space: pre-wrap; line-height: 1.8; }
        .navbar { background-color: #303030; border-bottom: 1px solid #4285F4; }
        .navbar-brand, .nav-link { color: #ffffff !important; }
        .card {
            background: linear-gradient(135deg, #333 0%, #2a2a2a 100%);
            border: none;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #4285F4;
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            font-weight: 500;
        }
        .btn-primary:hover { background-color: #34C759; }
        .btn-outline-light {
            border-color: #ffffff;
            color: #ffffff;
            border-radius: 50px;
            padding: 8px 16px;
            font-weight: 500;
        }
        .btn-outline-light:hover { background-color: #F28C38; color: #ffffff; }
        .form-control {
            background-color: #424242;
            border: none;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 50px;
        }
        .form-control:focus {
            background-color: #424242;
            color: #ffffff;
            box-shadow: 0 0 0 3px #4285F4;
        }
        textarea.form-control { border-radius: 20px; }
        .dropdown-menu {
            background-color: #424242;
            border: none;
            border-radius: 16px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        .dropdown-item {
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 12px;
        }
        .dropdown-item:hover {
            background-color: #555;
            color: #ffffff;
        }
        . {
            background: none;
            border: none;
            color: #ffffff;
            padding: 8px;
            font-size: 1.5rem;
            line-height: 1;
        }
        .:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        .alert { border-radius: 16px; background-color: #34C759; color: #ffffff; }
        .alert-danger { background-color: #F28C38; }
        .pagination .page-link { border-radius: 50%; color: #ffffff; }
        .pagination .page-item.active .page-link { background-color: #4285F4; border-color: #4285F4; }
    </style>
    <script>
        function stripHtmlTags(inputElement) {
            let inputValue = inputElement.value;
            let sanitizedValue = inputValue.replace(/<[^>]*>/g, '');
            inputElement.value = sanitizedValue;
        }
    </script>
</head>
<body>

<nav class="navbar navbar-expand-md fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="./">Enlight</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link fw-medium <?php if ($section == 'home') echo 'active'; ?>" href="./?section=home">Home</a></li>
                <?php if (is_owner()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link fw-medium  <?php if ($section == 'upload' || $section == 'edit' || $section == 'admin' || $section == 'setting' || $section == 'upload_title' || $section == 'edit_title' || $section == 'upload_chapter') echo 'active'; ?>" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="./?section=upload_title">New Novel</a></li>
                        <li><a class="dropdown-item" href="./?section=admin">Manage</a></li>
                        <li><a class="dropdown-item" href="./?section=setting">Import/Export</a></li>
                        <li><a class="dropdown-item" href="./?section=logout">Logout</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
<?php

switch ($section) {
    case 'home':
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        $total_novels_result = $db->querySingle("SELECT COUNT(*) FROM novels");
        $total_novels = intval($total_novels_result);
        $total_pages = ceil($total_novels / $per_page);

        $novels_stmt = $db->prepare("SELECT id, title FROM novels ORDER BY updated_at DESC LIMIT :limit OFFSET :offset");
        $novels_stmt->bindValue(':limit', $per_page, SQLITE3_INTEGER);
        $novels_stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $novels_result = $novels_stmt->execute();

        echo '<h1 class="fw-bold fs-1 mb-5 text-white">Titles</h1>';
        while ($novel_row = $novels_result->fetchArray(SQLITE3_ASSOC)) {
            echo '<div class="card">';
            echo '<div class="d-flex justify-content-between align-items-center">';
            echo '<h5 class="mb-0 fw-bold fs-4"><a href="./?title=' . urlencode($novel_row['title']) . '" class="text-white text-decoration-none">' . $novel_row['title'] . '</a></h5>';
            if (is_owner()) {
                echo '<div class="dropdown">';
                echo '<button class="btn " type="button" data-bs-toggle="dropdown" aria-expanded="false">⋮</button>';
                echo '<ul class="dropdown-menu dropdown-menu-end">';
                echo '<li><a class="dropdown-item" href="./?section=upload_chapter&title=' . urlencode($novel_row['title']) . '">Add Chapter</a></li>';
                echo '<li><a class="dropdown-item" href="./?section=edit_title&title=' . urlencode($novel_row['title']) . '">Edit</a></li>';
                echo '<li><a class="dropdown-item" href="./?section=delete_title&title=' . urlencode($novel_row['title']) . '" onclick="return confirm(\'Are you sure?\')">Delete</a></li>';
                echo '</ul>';
                echo '</div>';
            }
            echo '</div></div>';
        }

        if ($total_pages > 1) {
            echo '<nav aria-label="Page navigation" class="mt-5">';
            echo '<ul class="pagination justify-content-center">';
            for ($i = 1; $i <= $total_pages; $i++) {
                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="./?section=home&page=' . $i . '">' . $i . '</a></li>';
            }
            echo '</ul></nav>';
        }
        break;

    case 'title':
        if (empty($title_param)) {
            echo '<div class="alert alert-danger">Invalid title.</div>';
            break;
        }

        $novel_stmt = $db->prepare("SELECT id, title FROM novels WHERE title = :title");
        $novel_stmt->bindValue(':title', $title_param, SQLITE3_TEXT);
        $novel_result = $novel_stmt->execute();
        $novel = $novel_result->fetchArray(SQLITE3_ASSOC);

        if (!$novel) {
            echo '<div class="alert alert-danger">Novel not found.</div>';
            break;
        }

        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        $total_chapters_result = $db->querySingle("SELECT COUNT(*) FROM chapters WHERE novel_id = " . $novel['id']);
        $total_chapters = intval($total_chapters_result);
        $total_pages = ceil($total_chapters / $per_page);

        $chapters_stmt = $db->prepare("SELECT chapter_number, title FROM chapters WHERE novel_id = :novel_id ORDER BY chapter_number ASC LIMIT :limit OFFSET :offset");
        $chapters_stmt->bindValue(':novel_id', $novel['id'], SQLITE3_INTEGER);
        $chapters_stmt->bindValue(':limit', $per_page, SQLITE3_INTEGER);
        $chapters_stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $chapters_result = $chapters_stmt->execute();

        echo '<div class="d-flex justify-content-between align-items-center mb-5">';
        echo '<h1 class="fw-bold fs-1 text-white">' . $novel['title'] . '</h1>';
        if (is_owner()) {
            echo '<button class="btn btn-primary" onclick="location.href=\'./?section=upload_chapter&title=' . urlencode($title_param) . '\'">New Chapter</button>';
        }
        echo '</div>';
        echo '<div class="card">';
        while ($chapter_row = $chapters_result->fetchArray(SQLITE3_ASSOC)) {
            echo '<div class="d-flex justify-content-between align-items-center py-2">';
            echo '<a href="./?title=' . urlencode($title_param) . '&chapter=' . $chapter_row['chapter_number'] . '" class="text-white text-decoration-none fs-4">' . ($chapter_row['title'] ?: 'Chapter ' . $chapter_row['chapter_number']) . '</a>';
            if (is_owner()) {
                echo '<div class="dropdown">';
                echo '<button class="btn " type="button" data-bs-toggle="dropdown" aria-expanded="false">⋮</button>';
                echo '<ul class="dropdown-menu dropdown-menu-end">';
                echo '<li><a class="dropdown-item" href="./?section=edit&title=' . urlencode($title_param) . '&chapter=' . $chapter_row['chapter_number'] . '">Edit</a></li>';
                echo '<li><a class="dropdown-item" href="./?section=edit&title=' . urlencode($title_param) . '&chapter=' . $chapter_row['chapter_number'] . '&action=delete" onclick="return confirm(\'Are you sure?\')">Delete</a></li>';
                echo '</ul>';
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        if ($total_pages > 1) {
            echo '<nav aria-label="Chapter navigation" class="mt-5">';
            echo '<ul class="pagination justify-content-center">';
            for ($i = 1; $i <= $total_pages; $i++) {
                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="./?title=' . urlencode($title_param) . '&page=' . $i . '">' . $i . '</a></li>';
            }
            echo '</ul></nav>';
        }
        break;

    case 'chapter':
        if (empty($title_param) || empty($chapter_param)) {
            echo '<div class="alert alert-danger">Invalid title or chapter.</div>';
            break;
        }

        $novel_stmt = $db->prepare("SELECT id, title FROM novels WHERE title = :title");
        $novel_stmt->bindValue(':title', $title_param, SQLITE3_TEXT);
        $novel_result = $novel_stmt->execute();
        $novel = $novel_result->fetchArray(SQLITE3_ASSOC);

        if (!$novel) {
            echo '<div class="alert alert-danger">Novel not found.</div>';
            break;
        }

        $chapter_stmt = $db->prepare("SELECT chapter_number, title, content FROM chapters WHERE novel_id = :novel_id AND chapter_number = :chapter_number");
        $chapter_stmt->bindValue(':novel_id', $novel['id'], SQLITE3_INTEGER);
        $chapter_stmt->bindValue(':chapter_number', $chapter_param, SQLITE3_INTEGER);
        $chapter_result = $chapter_stmt->execute();
        $chapter = $chapter_result->fetchArray(SQLITE3_ASSOC);

        if (!$chapter) {
            echo '<div class="alert alert-danger">Chapter not found.</div>';
            break;
        }

        echo '<div class="card">';
        echo '<div class="d-flex justify-content-between align-items-center mb-4">';
        echo '<h2 class="fw-bold fs-2">' . ($chapter['title'] ?: 'Chapter ' . $chapter['chapter_number']) . '</h2>';
        echo '<div class="dropdown">';
        echo '<button class="btn " type="button" data-bs-toggle="dropdown" aria-expanded="false">⋮</button>';
        echo '<ul class="dropdown-menu dropdown-menu-end">';
        if (is_owner()) {
            echo '<li><a class="dropdown-item" href="./?section=edit&title=' . urlencode($title_param) . '&chapter=' . $chapter_param . '">Edit</a></li>';
        }
        echo '<li><a class="dropdown-item" href="./?title=' . urlencode($title_param) . '">Back</a></li>';
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        echo '<div class="chapter-content fs-4">' . $chapter['content'] . '</div>';
        echo '</div>';
        break;

    case 'upload_chapter':
        if (!is_owner()) {
            echo '<div class="alert alert-danger">You must be logged in.</div>';
            admin_login_form();
            break;
        }

        if (empty($title_param)) {
            echo '<div class="alert alert-danger">Invalid title.</div>';
            break;
        }

        $novel_stmt = $db->prepare("SELECT id, title FROM novels WHERE title = :title");
        $novel_stmt->bindValue(':title', $title_param, SQLITE3_TEXT);
        $novel_result = $novel_stmt->execute();
        $novel_upload_chapter = $novel_result->fetchArray(SQLITE3_ASSOC);

        if (!$novel_upload_chapter) {
            echo '<div class="alert alert-danger">Novel not found.</div>';
            break;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $chapter_title = sanitize_input($_POST['chapter_title']);
            // Apply strip_tags_input for chapter_content
            $chapter_content = strip_tags_input($_POST['chapter_content']);
            if (empty($chapter_content)) {
                echo '<div class="alert alert-danger">Chapter content is required.</div>';
            } else {
                try {
                    $chapter_number_result = $db->querySingle("SELECT MAX(chapter_number) FROM chapters WHERE novel_id = " . $novel_upload_chapter['id']);
                    $next_chapter_number = intval($chapter_number_result) + 1;

                    $chapter_insert_stmt = $db->prepare("INSERT INTO chapters (novel_id, chapter_number, title, content) VALUES (:novel_id, :chapter_number, :title, :content)");
                    $chapter_insert_stmt->bindValue(':novel_id', $novel_upload_chapter['id'], SQLITE3_INTEGER);
                    $chapter_insert_stmt->bindValue(':chapter_number', $next_chapter_number, SQLITE3_INTEGER);
                    $chapter_insert_stmt->bindValue(':title', $chapter_title, SQLITE3_TEXT);
                    $chapter_insert_stmt->bindValue(':content', $chapter_content, SQLITE3_TEXT);
                    $chapter_insert_stmt->execute();

                    echo '<div class="alert alert-success">Chapter uploaded! <a href="./?title=' . urlencode($title_param) . '&chapter=' . $next_chapter_number . '" class="text-white">View</a></div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                }
            }
        }
        ?>
        <h2 class="fw-bold fs-1 mb-5 text-white">New Chapter</h2>
        <form method="post" class="card">
            <div class="mb-4">
                <input type="text" class="form-control rounded-pill px-4" id="chapter_title" name="chapter_title" placeholder="Chapter Title (Optional)">
            </div>
            <div class="mb-4">
                <!-- Added oninput attribute here -->
                <textarea class="form-control rounded-3" id="chapter_content" name="chapter_content" rows="10" placeholder="Write your chapter..." required oninput="stripHtmlTags(this)"></textarea>
            </div>
            <button type="submit" class="btn btn-primary rounded-pill w-100">Upload</button>
            <a href="./?title=<?php echo urlencode($title_param); ?>" class="btn btn-outline-light rounded-pill w-100 mt-3">Cancel</a>
        </form>
        <?php
        break;

    case 'upload':
    case 'edit':
    case 'admin':
    case 'setting':
    case 'upload_title':
    case 'edit_title':
    case 'delete_title':
        if (!is_owner()) {
            $admin_password_set = get_setting($db, 'admin_password');
            if (!$admin_password_set && $section != 'admin_setup') {
                setup_password_form();
            } else {
                admin_login_form();
            }
            break;
        }

        switch ($section) {
            case 'upload_title':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $novel_title = sanitize_input($_POST['novel_title']);
                    if (empty($novel_title)) {
                        echo '<div class="alert alert-danger">Novel title is required.</div>';
                    } else {
                        try {
                            $novel_insert_stmt = $db->prepare("INSERT INTO novels (title) VALUES (:title)");
                            $novel_insert_stmt->bindValue(':title', $novel_title, SQLITE3_TEXT);
                            $novel_insert_stmt->execute();
                            echo '<div class="alert alert-success">Novel created! <a href="./?title=' . urlencode($novel_title) . '" class="text-white">View</a></div>';
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                        }
                    }
                }
                ?>
                <h2 class="fw-bold fs-1 mb-5 text-white">New Novel</h2>
                <form method="post" class="card">
                    <div class="mb-4">
                        <input type="text" class="form-control rounded-pill px-4" id="novel_title" name="novel_title" placeholder="Novel Title" required>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill w-100">Create</button>
                    <a href="./?section=admin" class="btn btn-outline-light rounded-pill w-100 mt-3">Cancel</a>
                </form>
                <?php
                break;

            case 'edit_title':
                if (empty($title_param)) {
                    echo '<div class="alert alert-danger">Invalid title.</div>';
                    break;
                }

                $novel_stmt = $db->prepare("SELECT id, title FROM novels WHERE title = :title");
                $novel_stmt->bindValue(':title', $title_param, SQLITE3_TEXT);
                $novel_result = $novel_stmt->execute();
                $novel_edit_title = $novel_result->fetchArray(SQLITE3_ASSOC);

                if (!$novel_edit_title) {
                    echo '<div class="alert alert-danger">Novel not found.</div>';
                    break;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $updated_title = sanitize_input($_POST['novel_title']);
                    if (empty($updated_title)) {
                        echo '<div class="alert alert-danger">Novel title cannot be empty.</div>';
                    } else {
                        try {
                            $update_stmt = $db->prepare("UPDATE novels SET title = :title, updated_at = DATETIME('now') WHERE id = :novel_id");
                            $update_stmt->bindValue(':title', $updated_title, SQLITE3_TEXT);
                            $update_stmt->bindValue(':novel_id', $novel_edit_title['id'], SQLITE3_INTEGER);
                            $update_stmt->execute();
                            echo '<div class="alert alert-success">Novel updated! <a href="./?title=' . urlencode($updated_title) . '" class="text-white">View</a></div>';
                            header('Location: ./?title=' . urlencode($updated_title));
                            exit();
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                        }
                    }
                }
                ?>
                <h2 class="fw-bold fs-1 mb-5 text-white">Edit Novel</h2>
                <form method="post" class="card">
                    <div class="mb-4">
                        <input type="text" class="form-control rounded-pill px-4" id="novel_title" name="novel_title" value="<?php echo $novel_edit_title['title']; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill w-100">Update</button>
                    <a href="./?title=<?php echo urlencode($title_param); ?>" class="btn btn-outline-light rounded-pill w-100 mt-3">Cancel</a>
                </form>
                <?php
                break;

            case 'delete_title':
                if (empty($title_param)) {
                    echo '<div class="alert alert-danger">Invalid title.</div>';
                    break;
                }

                $novel_stmt = $db->prepare("SELECT id, title FROM novels WHERE title = :title");
                $novel_stmt->bindValue(':title', $title_param, SQLITE3_TEXT);
                $novel_result = $novel_stmt->execute();
                $novel_delete_title = $novel_result->fetchArray(SQLITE3_ASSOC);

                if (!$novel_delete_title) {
                    echo '<div class="alert alert-danger">Novel not found.</div>';
                    break;
                }

                try {
                    $db->exec('BEGIN');
                    $delete_chapters_stmt = $db->prepare("DELETE FROM chapters WHERE novel_id = :novel_id");
                    $delete_chapters_stmt->bindValue(':novel_id', $novel_delete_title['id'], SQLITE3_INTEGER);
                    $delete_chapters_stmt->execute();

                    $delete_novel_stmt = $db->prepare("DELETE FROM novels WHERE id = :novel_id");
                    $delete_novel_stmt->bindValue(':novel_id', $novel_delete_title['id'], SQLITE3_INTEGER);
                    $delete_novel_stmt->execute();
                    $db->exec('COMMIT');

                    echo '<div class="alert alert-success">Novel deleted! <a href="./?section=admin" class="text-white">Manage</a></div>';
                    header('Location: ./?section=admin');
                    exit();
                } catch (Exception $e) {
                    $db->exec('ROLLBACK');
                    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                }
                break;

            case 'edit':
                if (empty($title_param) || empty($chapter_param)) {
                    echo '<div class="alert alert-danger">Invalid title or chapter.</div>';
                    break;
                }

                $novel_stmt = $db->prepare("SELECT id, title FROM novels WHERE title = :title");
                $novel_stmt->bindValue(':title', $title_param, SQLITE3_TEXT);
                $novel_result = $novel_stmt->execute();
                $novel = $novel_result->fetchArray(SQLITE3_ASSOC);

                if (!$novel) {
                    echo '<div class="alert alert-danger">Novel not found.</div>';
                    break;
                }

                $chapter_stmt = $db->prepare("SELECT chapter_number, title, content FROM chapters WHERE novel_id = :novel_id AND chapter_number = :chapter_number");
                $chapter_stmt->bindValue(':novel_id', $novel['id'], SQLITE3_INTEGER);
                $chapter_stmt->bindValue(':chapter_number', $chapter_param, SQLITE3_INTEGER);
                $chapter_result = $chapter_stmt->execute();
                $chapter_edit = $chapter_result->fetchArray(SQLITE3_ASSOC);

                if (!$chapter_edit) {
                    echo '<div class="alert alert-danger">Chapter not found.</div>';
                    break;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
                    if ($_POST['action'] === 'update') {
                        // Apply strip_tags_input for chapter_content
                        $updated_content = strip_tags_input($_POST['chapter_content']);
                        $updated_chapter_title = sanitize_input($_POST['chapter_title']);
                        $update_stmt = $db->prepare("UPDATE chapters SET content = :content, title = :chapter_title, updated_at = DATETIME('now') WHERE novel_id = :novel_id AND chapter_number = :chapter_number");
                        $update_stmt->bindValue(':content', $updated_content, SQLITE3_TEXT);
                        $update_stmt->bindValue(':chapter_title', $updated_chapter_title, SQLITE3_TEXT);
                        $update_stmt->bindValue(':novel_id', $novel['id'], SQLITE3_INTEGER);
                        $update_stmt->bindValue(':chapter_number', $chapter_param, SQLITE3_INTEGER);
                        $update_stmt->execute();
                        echo '<div class="alert alert-success">Chapter updated! <a href="./?title=' . urlencode($title_param) . '&chapter=' . $chapter_param . '" class="text-white">View</a></div>';
                        $chapter_edit['content'] = $updated_content;
                        $chapter_edit['title'] = $updated_chapter_title;
                    } elseif ($_POST['action'] === 'delete') {
                        $delete_stmt = $db->prepare("DELETE FROM chapters WHERE novel_id = :novel_id AND chapter_number = :chapter_number");
                        $delete_stmt->bindValue(':novel_id', $novel['id'], SQLITE3_INTEGER);
                        $delete_stmt->bindValue(':chapter_number', $chapter_param, SQLITE3_INTEGER);
                        $delete_stmt->execute();
                        echo '<div class="alert alert-success">Chapter deleted! <a href="./?title=' . urlencode($title_param) . '" class="text-white">View</a></div>';
                        header('Location: ./?title=' . urlencode($title_param));
                        exit();
                    }
                } elseif (isset($_GET['action']) && $_GET['action'] === 'delete') {
                    $delete_stmt = $db->prepare("DELETE FROM chapters WHERE novel_id = :novel_id AND chapter_number = :chapter_number");
                    $delete_stmt->bindValue(':novel_id', $novel['id'], SQLITE3_INTEGER);
                    $delete_stmt->bindValue(':chapter_number', $chapter_param, SQLITE3_INTEGER);
                    $delete_stmt->execute();
                    echo '<div class="alert alert-success">Chapter deleted! <a href="./?title=' . urlencode($title_param) . '" class="text-white">View</a></div>';
                    header('Location: ./?title=' . urlencode($title_param));
                    exit();
                }
                ?>
                <h2 class="fw-bold fs-1 mb-5 text-white">Edit Chapter</h2>
                <form method="post" class="card">
                    <div class="mb-4">
                        <input type="text" class="form-control rounded-pill px-4" id="chapter_title" name="chapter_title" value="<?php echo strip_tags($chapter_edit['title']); ?>" placeholder="Chapter Title (Optional)">
                    </div>
                    <div class="mb-4">
                        <!-- Added oninput attribute here -->
                        <textarea class="form-control rounded-3" id="chapter_content" name="chapter_content" rows="10" placeholder="Edit your chapter..." required oninput="stripHtmlTags(this)"><?php echo strip_tags($chapter_edit['content']); ?></textarea>
                    </div>
                    <button type="submit" name="action" value="update" class="btn btn-primary rounded-pill w-100">Save</button>
                    <button type="submit" name="action" value="delete" class="btn btn-outline-light rounded-pill w-100 mt-3" onclick="return confirm('Are you sure?')">Delete</button>
                    <a href="./?title=<?php echo urlencode($title_param); ?>&chapter=<?php echo $chapter_param; ?>" class="btn btn-outline-light rounded-pill w-100 mt-3">Cancel</a>
                </form>
                <?php
                break;

            case 'admin':
                echo '<h1 class="fw-bold fs-1 mb-5 text-white">Manage Novels</h1>';
                echo '<button class="btn btn-primary rounded-pill mb-5" onclick="location.href=\'./?section=upload_title\'">New Novel</button>';
                $novels_result_admin = $db->query("SELECT id, title FROM novels ORDER BY title ASC");
                echo '<div class="card">';
                while ($novel_admin_row = $novels_result_admin->fetchArray(SQLITE3_ASSOC)) {
                    echo '<div class="d-flex justify-content-between align-items-center py-2">';
                    echo '<span class="fs-4 fw-bold">' . $novel_admin_row['title'] . '</span>';
                    echo '<div>';
                    echo '<button class="btn btn-primary btn-sm me-2 rounded-pill" onclick="location.href=\'./?section=upload_chapter&title=' . urlencode($novel_admin_row['title']) . '\'">New Chapter</button>';
                    echo '<div class="dropdown d-inline-block">';
                    echo '<button class="btn " type="button" data-bs-toggle="dropdown" aria-expanded="false">⋮</button>';
                    echo '<ul class="dropdown-menu dropdown-menu-end">';
                    echo '<li><a class="dropdown-item" href="./?section=edit_title&title=' . urlencode($novel_admin_row['title']) . '">Edit</a></li>';
                    echo '<li><a class="dropdown-item" href="./?section=delete_title&title=' . urlencode($novel_admin_row['title']) . '" onclick="return confirm(\'Are you sure?\')">Delete</a></li>';
                    echo '<li><a class="dropdown-item" href="./?title=' . urlencode($novel_admin_row['title']) . '">View</a></li>';
                    echo '</ul>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
                break;

            case 'setting':
                if (isset($_GET['action'])) {
                    if ($_GET['action'] === 'export') {
                        $novels_result = $db->query("SELECT * FROM novels");
                        $novels_data = [];
                        while ($row = $novels_result->fetchArray(SQLITE3_ASSOC)) {
                            $novels_data[] = $row;
                        }

                        $chapters_result = $db->query("SELECT * FROM chapters");
                        $chapters_data = [];
                        while ($row = $chapters_result->fetchArray(SQLITE3_ASSOC)) {
                            $chapters_data[] = $row;
                        }

                        $export_data = ['novels' => $novels_data, 'chapters' => $chapters_data];
                        $filename = 'novel_data_' . date('YmdHis') . '.json';
                        header('Content-Type: application/json');
                        header('Content-Disposition: attachment; filename="' . $filename . '"');
                        echo json_encode($export_data, JSON_PRETTY_PRINT);
                        exit();
                    } elseif ($_GET['action'] === 'import') {
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['data_file']) && $_FILES['data_file']['error'] == 0) {
                            $file_tmp_path = $_FILES['data_file']['tmp_name'];
                            $file_content = file_get_contents($file_tmp_path);
                            $import_data = json_decode($file_content, true);

                            if (isset($import_data['novels']) && isset($import_data['chapters'])) {
                                try {
                                    $db->exec('BEGIN');
                                    $db->exec("DELETE FROM chapters");
                                    $db->exec("DELETE FROM novels");

                                    $novel_stmt = $db->prepare("INSERT INTO novels (id, title, created_at, updated_at) VALUES (:id, :title, :created_at, :updated_at)");
                                    foreach ($import_data['novels'] as $novel) {
                                        $novel_stmt->bindValue(':id', $novel['id'], SQLITE3_INTEGER);
                                        $novel_stmt->bindValue(':title', $novel['title'], SQLITE3_TEXT);
                                        $novel_stmt->bindValue(':created_at', $novel['created_at'], SQLITE3_TEXT);
                                        $novel_stmt->bindValue(':updated_at', $novel['updated_at'], SQLITE3_TEXT);
                                        $novel_stmt->execute();
                                    }

                                    $chapter_stmt = $db->prepare("INSERT INTO chapters (id, novel_id, chapter_number, title, content, created_at, updated_at) VALUES (:id, :novel_id, :chapter_number, :title, :content, :created_at, :updated_at)");
                                    foreach ($import_data['chapters'] as $chapter) {
                                        $chapter_stmt->bindValue(':id', $chapter['id'], SQLITE3_INTEGER);
                                        $chapter_stmt->bindValue(':novel_id', $chapter['novel_id'], SQLITE3_INTEGER);
                                        $chapter_stmt->bindValue(':chapter_number', $chapter['chapter_number'], SQLITE3_INTEGER);
                                        $chapter_stmt->bindValue(':title', $chapter['title'], SQLITE3_TEXT);
                                        $chapter_stmt->bindValue(':content', $chapter['content'], SQLITE3_TEXT);
                                        $chapter_stmt->bindValue(':created_at', $chapter['created_at'], SQLITE3_TEXT);
                                        $chapter_stmt->bindValue(':updated_at', $chapter['updated_at'], SQLITE3_TEXT);
                                        $chapter_stmt->execute();
                                    }

                                    $db->exec('COMMIT');
                                    echo '<div class="alert alert-success">Data imported!</div>';
                                } catch (Exception $e) {
                                    $db->exec('ROLLBACK');
                                    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-warning">Invalid file format.</div>';
                            }
                        }
                        ?>
                        <h2 class="fw-bold fs-1 mb-5 text-white">Import/Export</h2>
                        <button class="btn btn-primary rounded-pill w-100 mb-4" onclick="location.href='./?section=setting&action=export'">Export</button>
                        <form method="post" enctype="multipart/form-data" class="card">
                            <div class="mb-4">
                                <input class="form-control rounded-3" type="file" id="data_file" name="data_file" accept=".json" required>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill w-100">Import</button>
                            <a href="./?section=admin" class="btn btn-outline-light rounded-pill w-100 mt-3">Cancel</a>
                        </form>
                        <?php
                    }
                } else {
                    echo '<h2 class="fw-bold fs-1 mb-5 text-white">Import/Export</h2>';
                    echo '<button class="btn btn-primary rounded-pill w-100 mb-4" onclick="location.href=\'./?section=setting&action=export\'">Export</button>';
                    echo '<button class="btn btn-outline-light rounded-pill w-100" onclick="location.href=\'./?section=setting&action=import\'">Import</button>';
                }
                break;
        }
        break;

    case 'admin_setup':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            if (empty($password)) {
                echo '<div class="alert alert-danger">Password cannot be empty.</div>';
                setup_password_form();
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                set_setting($db, 'admin_password', $hashed_password);
                echo '<div class="alert alert-success">Password set! Please login.</div>';
                admin_login_form();
            }
        } else {
            setup_password_form();
        }
        break;

    case 'admin_login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            if (authenticate_owner($db, $password)) {
                header('Location: ./?section=admin');
                exit();
            } else {
                echo '<div class="alert alert-danger">Invalid password.</div>';
                admin_login_form();
            }
        } else {
            admin_login_form();
        }
        break;

    case 'logout':
        session_start();
        session_destroy();
        echo '<div class="alert alert-info">Logged out.</div>';
        break;

    default:
        echo '<h1 class="fw-bold fs-1 mb-5 text-white">Welcome!</h1>';
        echo '<p class="fs-4">Browse your novels above.</p>';
        if (!get_setting($db, 'admin_password')) {
            echo '<div class="alert alert-warning mt-4">No admin password set. <a href="./?section=admin_setup" class="text-white">Set it up</a> to manage novels.</div>';
        }
}

?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>