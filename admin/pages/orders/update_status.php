<?php
// Start output buffering to prevent stray output
ob_start();

// Include the config file (fixed path)
require_once '../../includes/config.php';

// Disable displaying errors to the browser
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Enable error logging to a file
ini_set('log_errors', 1);
ini_set('error_log', 'C:/Users/ASUS/Desktop/ecweb/ecweb/admin/pages/orders/error.log');

// Set the content type to JSON
header('Content-Type: application/json; charset=UTF-8');

// Clear any buffered output before sending JSON
ob_end_clean();

// Instantiate the Database class to get the PDO connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
    if (!$pdo) {
        throw new Exception('Failed to establish database connection');
    }
} catch (Exception $e) {
    error_log('Database Initialization Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if the request is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    // Validate inputs
    if (!is_numeric($order_id) || !in_array(strtolower($status), ['pending', 'shipped', 'delivered'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid order_id or status']);
        exit;
    }

    try {
        // Prepare and execute the update query
        $stmt = $pdo->prepare("UPDATE ecweb.orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$status, $order_id]);

        // Check if any rows were affected
        $rowCount = $stmt->rowCount();
        if ($rowCount === 0) {
            echo json_encode(['success' => false, 'message' => 'No rows updated. Order ID may not exist.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        }
    } catch (PDOException $e) {
        error_log('PDO Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log('General Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

// Ensure no further output is sent
exit;
?>