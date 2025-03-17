<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php'; // Assumes this provides Database class

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Order {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (!$this->pdo) {
            throw new Exception("PDO connection is not initialized.");
        }
    }

    public function addOrder($data) {
        $user_id = filter_var($data['user_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        $total_amount = filter_var($data['total_amount'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $order_date = filter_var($data['order_date'] ?? '', FILTER_SANITIZE_STRING);
        $status = filter_var($data['status'] ?? '', FILTER_SANITIZE_STRING);
        $shipping_address = filter_var($data['shipping_address'] ?? '', FILTER_SANITIZE_STRING);
        $username = filter_var($data['username'] ?? '', FILTER_SANITIZE_STRING);

        if (!$user_id || $user_id <= 0) return ['success' => false, 'message' => 'Invalid User ID.'];
        if (!$total_amount || $total_amount <= 0) return ['success' => false, 'message' => 'Total Amount must be greater than 0.'];
        if (!$order_date) return ['success' => false, 'message' => 'Order Date is required.'];
        if (!$status || !in_array($status, ['pending', 'shipped', 'delivered'])) return ['success' => false, 'message' => 'Invalid status.'];
        if (!$shipping_address) return ['success' => false, 'message' => 'Shipping Address is required.'];
        if (!$username) return ['success' => false, 'message' => 'Username is required.'];

        try {
            $stmt = $this->pdo->prepare("INSERT INTO ecweb.orders (user_id, total_amount, order_date, status, shipping_address, username) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $total_amount, $order_date, $status, $shipping_address, $username]);
            return ['success' => true, 'message' => 'Order added successfully.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function updateOrder($id, $data) {
        $user_id = filter_var($data['user_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        $total_amount = filter_var($data['total_amount'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $order_date = filter_var($data['order_date'] ?? '', FILTER_SANITIZE_STRING);
        $status = filter_var($data['status'] ?? '', FILTER_SANITIZE_STRING);
        $shipping_address = filter_var($data['shipping_address'] ?? '', FILTER_SANITIZE_STRING);
        $username = filter_var($data['username'] ?? '', FILTER_SANITIZE_STRING);

        if (!$user_id || $user_id <= 0) return ['success' => false, 'message' => 'Invalid User ID.'];
        if (!$total_amount || $total_amount <= 0) return ['success' => false, 'message' => 'Total Amount must be greater than 0.'];
        if (!$order_date) return ['success' => false, 'message' => 'Order Date is required.'];
        if (!$status || !in_array($status, ['pending', 'shipped', 'delivered'])) return ['success' => false, 'message' => 'Invalid status.'];
        if (!$shipping_address) return ['success' => false, 'message' => 'Shipping Address is required.'];
        if (!$username) return ['success' => false, 'message' => 'Username is required.'];

        try {
            $stmt = $this->pdo->prepare("UPDATE ecweb.orders SET user_id = ?, total_amount = ?, order_date = ?, status = ?, shipping_address = ?, username = ? WHERE order_id = ?");
            $stmt->execute([$user_id, $total_amount, $order_date, $status, $shipping_address, $username, $id]);
            return ['success' => true, 'message' => 'Order updated successfully.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function deleteOrder($id) {
        if (!ctype_digit((string)$id) || $id <= 0) return ['success' => false, 'message' => 'Invalid order ID.'];

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("DELETE FROM ecweb.order_items WHERE order_id = ?");
            $stmt->execute([$id]);
            $stmt = $this->pdo->prepare("DELETE FROM ecweb.orders WHERE order_id = ?");
            $stmt->execute([$id]);
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Order deleted successfully.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error deleting order: ' . $e->getMessage()];
        }
    }

    public function getOrders($searchTerm = '') {
        try {
            if ($searchTerm) {
                $stmt = $this->pdo->prepare("SELECT order_id, user_id, total_amount, order_date, status, shipping_address, username FROM ecweb.orders WHERE order_id = ? OR user_id = ? OR status LIKE ? OR shipping_address LIKE ? OR username LIKE ?");
                $stmt->execute([$searchTerm, $searchTerm, "%$searchTerm%", "%$searchTerm%", "%$searchTerm%"]);
            } else {
                $stmt = $this->pdo->query("SELECT order_id, user_id, total_amount, order_date, status, shipping_address, username FROM ecweb.orders");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => 'Error fetching orders: ' . $e->getMessage()];
        }
    }

    public function getOrderById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM ecweb.orders WHERE order_id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return ['error' => 'Error fetching order: ' . $e->getMessage()];
        }
    }
    
}


// Initialize the Database and Order classes
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Failed to get PDO connection from Database class.");
}

try {
    $orderManager = new Order($pdo);
} catch (Exception $e) {
    die("Error initializing Order class: " . $e->getMessage());
}

$successMessage = '';
$errorMessage = '';
$action = $_GET['action'] ?? 'view';
$id = $_GET['id'] ?? null;

// Handle order addition or editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add_order' || $action === 'edit_order')) {
    $data = [
        'user_id' => $_POST['user_id'] ?? '',
        'total_amount' => $_POST['total_amount'] ?? '',
        'order_date' => $_POST['order_date'] ?? '',
        'status' => $_POST['status'] ?? '',
        'shipping_address' => $_POST['shipping_address'] ?? '',
        'username' => $_POST['username'] ?? ''
    ];

    if ($action === 'add_order') {
        $result = $orderManager->addOrder($data);
    } elseif ($action === 'edit_order' && $id) {
        $result = $orderManager->updateOrder($id, $data);
    }

    if (isset($result)) {
        if ($result['success']) {
            $successMessage = $result['message'];
            header("Location: ../../index.php?page=orders&success=1");
            exit;
        } else {
            $errorMessage = $result['message'];
        }
    } else {
        $errorMessage = "Failed to process the request.";
    }
}

// Handle search functionality
$searchTerm = $_GET['search'] ?? '';
$orders = $orderManager->getOrders($searchTerm);

// Fetch order data for editing
$order = null;
if ($action === 'edit_order' && $id) {
    $order = $orderManager->getOrderById($id);
    if (!$order) {
        header("Location: ../../index.php?page=orders");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Same styles as before */
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        h2 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            text-align: center;
            background-color: #2c3e50;
            padding: 15px;
            border-radius: 0;
        }
        h3 {
            font-size: 20px;
            font-weight: 500;
            color: #2c3e50;
            margin: 10px 0;
            text-align: center;
            background: linear-gradient(90deg, #2c3e50, #3498db);
            padding: 10px;
            color: white;
        }
        p {
            font-size: 14px;
            color: #6c757d;
            text-align: center;
            margin-bottom: 20px;
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 0;
            overflow: hidden;
        }
        .product-table thead th {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
        }
        .product-table tbody td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
            font-size: 14px;
        }
        .product-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .product-table th:nth-child(1),
        .product-table td:nth-child(1) {
            width: 5%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-table th:nth-child(2),
        .product-table td:nth-child(2) {
            width: 8%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-table th:nth-child(3),
        .product-table td:nth-child(3) {
            width: 12%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-table th:nth-child(4),
        .product-table td:nth-child(4) {
            width: 10%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-table th:nth-child(5),
        .product-table td:nth-child(5) {
            width: 15%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-table th:nth-child(6),
        .product-table td:nth-child(6) {
            width: 20%;
            white-space: normal;
            overflow-wrap: break-word;
        }
        .product-table th:nth-child(7),
        .product-table td:nth-child(7) {
            width: 20%;
        }
        .product-table th:nth-child(8),
        .product-table td:nth-child(8) {
            width: 10%;
        }
        .action-bar {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            background-color: #fff;
            padding: 10px;
            border-radius: 0;
        }
        .search-form {
            display: flex;
            align-items: center;
            flex: 1;
            margin-right: 10px;
        }
        .search-form input[type="text"] {
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #ced4da;
            border-right: none;
            border-radius: 0;
            outline: none;
            width: 100%;
            box-sizing: border-box;
        }
        .search-form button {
            padding: 8px 12px;
            font-size: 14px;
            background-color: #007bff;
            border: 1px solid #007bff;
            border-left: none;
            border-radius: 0;
            cursor: pointer;
            color: white;
        }
        .add-new-product-button {
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        .btn {
            display: inline-block;
            padding: 5px 10px;
            font-size: 12px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            line-height: normal;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
            border: none;
        }
        .btn-custom {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        .back-button {
            padding: 8px 12px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 0;
            margin-left: 10px;
            cursor: pointer;
        }
        .status-select {
            padding: 4px 20px 4px 8px;
            border-radius: 0;
            border: 1px solid #ced4da;
            background-color: #fff;
            cursor: pointer;
            font-size: 14px;
            height: 28px;
            line-height: 1;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="%23333" viewBox="0 0 16 16"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 10px;
            width: 100px;
            display: inline-block;
            vertical-align: middle;
        }
        .update-btn {
            padding: 4px 8px;
            color: white;
            border: none;
            border-radius: 0;
            cursor: pointer;
            font-size: 14px;
            height: 28px;
            line-height: 1;
            background-color: #007bff;
            display: inline-block;
            vertical-align: middle;
            margin-left: 5px;
        }
        .update-btn.loading::after {
            content: "\f110";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            display: inline-block;
            margin-left: 6px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .action-buttons {
            white-space: nowrap;
        }
        .alert {
            display: none;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            padding: 10px 20px;
            border-radius: 0;
            color: white;
        }
        .alert-success {
            background-color: #28a745;
        }
        .alert-danger {
            background-color: #dc3545;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .status-select option {
            color: black;
            padding: 8px;
        }
        .status-select option[value="pending"] {
            color: #007bff !important;
        }
        .status-select option[value="shipped"] {
            color: #28a745 !important;
        }
        .status-select option[value="delivered"] {
            color: #ffc107 !important;
        }
        .status-select:invalid {
            color: #888;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 0;
            box-sizing: border-box;
            font-size: 14px;
        }
        .form-control.status-select {
            padding: 4px 20px 4px 8px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="%23333" viewBox="0 0 16 16"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 10px;
        }
        .mb-3 {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        @media (max-width: 768px) {
            .product-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .product-table th:nth-child(7),
            .product-table td:nth-child(7) {
                min-width: 180px;
            }
            .status-select {
                width: 100%;
                margin-bottom: 5px;
            }
            .update-btn {
                width: 100%;
                margin-left: 0;
            }
            .product-table th:nth-child(8),
            .product-table td:nth-child(8) {
                min-width: 120px;
            }
            .action-buttons {
                white-space: normal;
            }
            .action-buttons .btn {
                display: block;
                margin-bottom: 5px;
                width: 100%;
            }
            .action-bar {
                flex-direction: column;
                gap: 10px;
            }
            .search-form {
                margin-right: 0;
                width: 100%;
            }
            .add-new-product-button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Orders</h2>
    <p>Manage your order list here.</p>

    <?php if (isset($_GET['success']) && $_GET['success']): ?>
        <div class="alert alert-success" id="successAlert"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" id="errorAlert"><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" id="errorAlert"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($action === 'view'): ?>
        <div class="action-bar">
            <div class="search-form">
                <form method="GET" action="../../index.php" style="display: flex; align-items: center; width: 100%;">
                    <input type="hidden" name="page" value="orders">
                    <input type="text" name="search" placeholder="Search by ID, User ID, Status, Address, or Username" value="<?php echo htmlspecialchars($searchTerm); ?>" aria-label="Search orders">
                    <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
                    <a href="../../index.php?page=orders" class="back-button" aria-label="Clear search">Clear</a>
                </form>
            </div>
            <a href="../../index.php?page=orders&action=add_order" class="add-new-product-button" aria-label="Add new order">Add New Order</a>
        </div>

        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Total Amount</th>
                    <th>Order Date</th>
                    <th>Shipping Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders) || isset($orders['error'])): ?>
                    <tr>
                        <td colspan="8"><?php echo isset($orders['error']) ? htmlspecialchars($orders['error']) : 'No orders found.'; ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            <td><?php echo htmlspecialchars($order['shipping_address'] ?? 'Not provided'); ?></td>
                            <td>
                                <select class="status-select" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>" aria-label="Order status for order <?php echo htmlspecialchars($order['order_id']); ?>">
                                    <option value="pending" <?php echo ($order['status'] === 'pending' || !$order['status']) ? 'selected' : ''; ?>>Pending</option>
                                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                </select>
                                <button class="update-btn" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>" data-current-status="<?php echo htmlspecialchars($order['status'] ?? 'pending'); ?>" aria-label="Update status for order <?php echo htmlspecialchars($order['order_id']); ?>">Update</button>
                            </td>
                            <td class="action-buttons">
                                <a href="index.php?page=orders&action=edit_order&id=<?php echo $order['order_id']; ?>" class="btn btn-warning" aria-label="Edit order <?php echo htmlspecialchars($order['order_id']); ?>">Edit</a>
                                <button class="btn btn-custom delete-btn" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>" aria-label="Delete order <?php echo htmlspecialchars($order['order_id']); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php elseif ($action === 'add_order'): ?>
        <h3>Add New Order</h3>
        <form method="POST" action="../../index.php?page=orders&action=add_order">
            <div class="mb-3">
                <label for="user_id" class="form-label">User ID</label>
                <input type="number" id="user_id" name="user_id" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="total_amount" class="form-label">Total Amount</label>
                <input type="number" step="0.01" id="total_amount" name="total_amount" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="order_date" class="form-label">Order Date</label>
                <input type="datetime-local" id="order_date" name="order_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
            </div>
            <div class="mb-3">
                <label for="shipping_address" class="form-label">Shipping Address</label>
                <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" placeholder="Enter shipping address" required></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control status-select" aria-label="Order status">
                    <option value="pending">Pending</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                </select>
            </div>
            <button type="submit" class="add-new-product-button">Add Order</button>
            <a href="../../index.php?page=orders" class="back-button">Back</a>
        </form>
    <?php elseif ($action === 'edit_order' && $order): ?>
        <h3>Edit Order</h3>
        <form method="POST" action="../../index.php?page=orders&action=edit_order&id=<?php echo $order['order_id']; ?>">
            <div class="mb-3">
                <label for="order_id" class="form-label">Order ID</label>
                <input type="text" id="order_id" name="order_id" class="form-control" value="<?php echo htmlspecialchars($order['order_id']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="user_id" class="form-label">User ID</label>
                <input type="number" id="user_id" name="user_id" class="form-control" value="<?php echo htmlspecialchars($order['user_id']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($order['username'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="total_amount" class="form-label">Total Amount</label>
                <input type="number" step="0.01" id="total_amount" name="total_amount" class="form-control" value="<?php echo htmlspecialchars($order['total_amount']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="order_date" class="form-label">Order Date</label>
                <input type="datetime-local" id="order_date" name="order_date" class="form-control" value="<?php echo str_replace(' ', 'T', htmlspecialchars($order['order_date'])); ?>" required>
            </div>
            <div class="mb-3">
                <label for="shipping_address" class="form-label">Shipping Address</label>
                <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" required><?php echo htmlspecialchars($order['shipping_address'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control status-select" aria-label="Order status">
                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                </select>
            </div>
            <button type="submit" class="add-new-product-button">Update Order</button>
            <a href="../../index.php?page=orders" class="back-button">Back</a>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const successAlert = document.querySelector('#successAlert');
    if (successAlert) {
        successAlert.style.display = "block";
        setTimeout(() => successAlert.style.display = "none", 3000);
    }
    const errorAlert = document.querySelector('#errorAlert');
    if (errorAlert) {
        errorAlert.style.display = "block";
        setTimeout(() => errorAlert.style.display = "none", 5000);
    }

    const updateButtons = document.querySelectorAll('.update-btn');
    updateButtons.forEach(button => {
        const statusSelect = button.parentElement.querySelector('.status-select');
        const currentStatus = button.getAttribute('data-current-status');

        statusSelect.addEventListener('change', function() {
            const newStatus = this.value;
            button.setAttribute('data-current-status', newStatus);
            statusSelect.style.color = getStatusColor(newStatus);
        });

        statusSelect.style.color = getStatusColor(currentStatus);

        button.addEventListener('click', async function() {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = statusSelect.value;

            const result = await Swal.fire({
                title: 'Are you sure, babe?',
                text: `Do you want to update the status of Order #${orderId} to "${newStatus}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Yes, update it!',
                cancelButtonText: 'Nope, cancel'
            });

            if (!result.isConfirmed) {
                return;
            }

            button.classList.add('loading');
            button.disabled = true;
            

            try {
                const response = await fetch('pages/orders/update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${encodeURIComponent(orderId)}&status=${encodeURIComponent(newStatus)}`
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', text);
                    throw new Error('Invalid response from server');
                }

                button.classList.remove('loading');
                button.disabled = false;

                if (data.success) {
                    await Swal.fire({
                        title: 'Updated!',
                        text: 'Status updated successfully, babe! 🎉',
                        icon: 'success',
                        confirmButtonColor: '#007bff'
                    });
                    button.setAttribute('data-current-status', newStatus);
                } else {
                    await Swal.fire({
                        title: 'Oops...',
                        text: 'Something went wrong: ' + data.message,
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                    statusSelect.value = currentStatus;
                    statusSelect.style.color = getStatusColor(currentStatus);
                }
            } catch (error) {
                button.classList.remove('loading');
                button.disabled = false;
                await Swal.fire({
                    title: 'Oops...',
                    text: 'An error occurred: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
                statusSelect.value = currentStatus;
                statusSelect.style.color = getStatusColor(currentStatus);
            }
        });
    });

    // Add delete functionality with SweetAlert2
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
    button.addEventListener('click', async function() {
        const orderId = this.getAttribute('data-order-id');

        const result = await Swal.fire({
            title: 'Are you sure, babe?',
            text: `Do you want to delete Order #${orderId}? This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, keep it'
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

            const response = await fetch(`/admin/pages/orders/delete_order.php?id=${encodeURIComponent(orderId)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const text = await response.text();
            console.log('Raw response:', text); // Log the raw response

            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
            }

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', text);
                throw new Error('Invalid response from server: ' + e.message);
            }

            if (data.success) {
                await Swal.fire({
                    title: 'Deleted!',
                    text: 'The order has been deleted successfully, babe! 🎉',
                    icon: 'success',
                    confirmButtonColor: '#007bff'
                });
                
                button.closest('tr').remove();
                
                if (document.querySelectorAll('.product-table tbody tr').length === 0) {
                    document.querySelector('.product-table tbody').innerHTML = 
                        '<tr><td colspan="8">No orders found.</td></tr>';
                }
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            await Swal.fire({
                title: 'Oops...',
                text: 'Failed to delete the order: ' + error.message,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        } finally {
            button.disabled = false;
            button.innerHTML = 'Delete';
        }
    });
});
    function getStatusColor(status) {
        switch (status.toLowerCase()) {
            case 'pending': return '#007bff';
            case 'shipped': return '#28a745';
            case 'delivered': return '#ffc107';
            default: return '#888';
        }
    }
});
</script>
</body>
</html>