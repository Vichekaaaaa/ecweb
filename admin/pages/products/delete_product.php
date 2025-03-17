<?php
// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration and initialization
ob_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/php_errors.log');

try {
    // Load configuration
    $configPath = 'includes/config.php';
    if (!file_exists($configPath)) {
        throw new Exception('Configuration file not found');
    }
    require_once $configPath;

    // Database connection
    $database = new Database();
    $pdo = $database->getConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Get and validate product ID
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    if ($id === false || $id === null) {
        throw new Exception('Invalid product ID');
    }

    // Fetch product details
    $stmt = $pdo->prepare("SELECT title, image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new Exception('Product not found');
    }

    // Delete product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    // Delete associated image
    $targetDir = '../assets/images/products/';
    if (!empty($product['image']) && file_exists($targetDir . $product['image'])) {
        unlink($targetDir . $product['image']);
    }

    // Success response
    renderResponse('success', 'Product Deleted', 
        "The product '{$product['title']}' has been successfully deleted.");

} catch (Exception $e) {
    // Error handling
    renderResponse('error', 'Error', $e->getMessage());
}

/**
 * Render response with consistent styling
 */
function renderResponse($type, $title, $message) {
    ob_clean();
    $class = $type === 'success' ? 'alert-success' : 'alert-error';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <style>
            :root {
                --success-bg: #e6ffe6;
                --success-border: #00cc00;
                --success-text: #006600;
                --error-bg: #ffe6e6;
                --error-border: #ff3333;
                --error-text: #cc0000;
            }

            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                background: #f5f5f5;
            }

            .alert {
                padding: 20px 30px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                width: 90%;
                max-width: 500px;
                animation: fadeIn 0.3s ease-in;
            }

            .alert-success {
                background: var(--success-bg);
                border: 2px solid var(--success-border);
                color: var(--success-text);
            }

            .alert-error {
                background: var(--error-bg);
                border: 2px solid var(--error-border);
                color: var(--error-text);
            }

            h2 {
                margin: 0 0 15px;
                font-size: 24px;
            }

            p {
                margin: 0 0 20px;
                line-height: 1.5;
            }

            a {
                color: inherit;
                text-decoration: none;
                font-weight: 600;
                padding: 8px 16px;
                border-radius: 4px;
                background: rgba(255,255,255,0.8);
                transition: background 0.3s;
            }

            a:hover {
                background: rgba(255,255,255,1);
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body>
        <div class="alert <?php echo $class; ?>">
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <p><?php echo htmlspecialchars($message); ?></p>
            <p><a href="?page=products">Back to Products</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>