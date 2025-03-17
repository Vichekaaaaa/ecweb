<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering to capture any unintended output
ob_start();

// Set headers for JSON response
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Disable error display to prevent HTML output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/php_errors.log'); // Adjust to a valid path

// Debug: Check if config.php exists
if (!file_exists('../../includes/config.php')) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Config file not found at ../../includes/config.php']);
    exit;
}

require_once '../../includes/config.php';

// Initialize the database connection
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Failed to initialize database connection']);
    exit;
}

try {
    // Get and validate the order ID
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    if (!$id || $id <= 0) {
        throw new Exception('Invalid order ID.');
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Delete related order items
    $stmt = $pdo->prepare("DELETE FROM ecweb.order_items WHERE order_id = ?");
    $stmt->execute([$id]);

    // Delete the order
    $stmt = $pdo->prepare("DELETE FROM ecweb.orders WHERE order_id = ?");
    $stmt->execute([$id]);

    // Commit transaction
    $pdo->commit();

    // Clear output buffer and send success response
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Order deleted successfully.']);
    exit;
} catch (PDOException $e) {
    // Roll back transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error deleting order: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
?>