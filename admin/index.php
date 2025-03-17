<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <!-- Bootstrap Icons for sidebar icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom stylesheets -->
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link rel="stylesheet" href="/admin/assets/css/pages.css">
</head>
<body>
    <?php
    session_start(); // Kept for potential future use
    error_reporting(E_ALL);
    ini_set('display_errors', 1); // Disable in production

    include_once 'includes/header.php';
    include_once 'includes/sidebar.php';
    ?>

    <main>
        <?php
        $page = isset($_GET['page']) ? basename($_GET['page']) : 'dashboard'; // Default to dashboard
        $action = isset($_GET['action']) ? basename($_GET['action']) : '';

        echo "<!-- Debug: \$page = {$page}, \$action = {$action} -->";

        $default_actions = [
            'dashboard' => 'dashboard',
            'products' => 'product',
            'orders' => 'orders',
            'collections' => 'collection',
            'discounts' => isset($_GET['action']) && $_GET['action'] == 'edit' ? 'edit' : 'discount',
            'logo' => 'logo',
            'slides' => 'slide',
            'special_offer' => 'special_offer',
            'users' => 'users',
            'about_us' => 'about_us',
            'contact' => 'contact',
            'social_links' => 'social_link' // Added social_links
        ];

        $allowed_pages = array_keys($default_actions);
        $page = in_array($page, $allowed_pages) ? $page : 'dashboard'; // Default to dashboard
        echo "<!-- Debug: After validation, \$page = {$page} -->";

        if (empty($action)) {
            $action = $default_actions[$page];
        }
        echo "<!-- Debug: \$action set to {$action} -->";

        $file_path = "pages/{$page}/{$action}.php";
        echo "<!-- Debugging: Attempting to load {$file_path} -->";
        error_log("Attempting to load: {$file_path}");

        if (file_exists($file_path)) {
            echo "<!-- Debug: File {$file_path} exists, including... -->";
            include_once $file_path;
        } else {
            error_log("File not found: {$file_path}");
            echo "<!-- Error: File not found at {$file_path} -->";
            header("HTTP/1.1 404 Not Found");
            include 'pages/errors/404.php';
            exit;
        }
        ?>
    </main>

    <?php
    include_once 'includes/footer.php';
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>