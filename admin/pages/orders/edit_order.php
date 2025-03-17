<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the database configuration (corrected path)
require_once 'includes/config.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to capture any unexpected output
ob_start();

// Initialize the Database class to get the PDO connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
    if (!$pdo) {
        throw new Exception("Failed to get PDO connection from Database class.");
    }
} catch (Exception $e) {
    // Return JSON error response for POST requests
    $response = ['success' => false, 'message' => 'Error initializing database connection: ' . $e->getMessage()];
    ob_end_clean(); // Clean any output before sending JSON
    echo json_encode($response);
    exit;
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Fetch the order ID from the URL
$order_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$order_id || $order_id <= 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = ['success' => false, 'message' => 'Invalid order ID.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    header("Location: /admin/index.php?page=orders");
    exit;
}

// Fetch the existing order data
try {
    $stmt = $pdo->prepare("SELECT * FROM ecweb.orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $response = ['success' => false, 'message' => 'Order not found.'];
            ob_end_clean();
            echo json_encode($response);
            exit;
        }
        header("Location: /admin/index.php?page=orders");
        exit;
    }
} catch (PDOException $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = ['success' => false, 'message' => 'Error fetching order: ' . $e->getMessage()];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    $errorMessage = "Error fetching order: " . $e->getMessage();
    header("Location: /admin/index.php?page=orders&error=" . urlencode($errorMessage));
    exit;
}

// Handle form submission for editing the order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $total_amount = filter_input(INPUT_POST, 'total_amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $order_date = filter_input(INPUT_POST, 'order_date', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $shipping_address = filter_input(INPUT_POST, 'shipping_address', FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);

    // Server-side validation
    if (!$user_id || $user_id <= 0) {
        $response = ['success' => false, 'message' => 'Invalid User ID.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    if (!$total_amount || $total_amount <= 0) {
        $response = ['success' => false, 'message' => 'Total Amount must be greater than 0.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    if (!$order_date) {
        $response = ['success' => false, 'message' => 'Order Date is required.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    if (!$status || !in_array($status, ['pending', 'shipped', 'delivered'])) {
        $response = ['success' => false, 'message' => 'Invalid status.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    if (!$shipping_address) {
        $response = ['success' => false, 'message' => 'Shipping Address is required.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    if (!$username) {
        $response = ['success' => false, 'message' => 'Username is required.'];
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE ecweb.orders SET user_id = ?, total_amount = ?, order_date = ?, status = ?, shipping_address = ?, username = ? WHERE order_id = ?");
        $result = $stmt->execute([$user_id, $total_amount, $order_date, $status, $shipping_address, $username, $order_id]);

        if ($result) {
            $response = ['success' => true, 'message' => 'Order updated successfully.'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to update the order.'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()];
    }

    ob_end_clean(); // Clean any output before sending JSON
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Include SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
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
        .add-new-product-button {
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
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
        .alert-danger {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Orders</h2>
    <p>Edit the order details below.</p>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" id="errorAlert"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <h3>Edit Order</h3>
    <form method="POST" id="editOrderForm">
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
        <a href="/admin/index.php?page=orders" class="back-button">Back</a>
    </form>
</div>

<!-- Include SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handle error alerts
    const errorAlert = document.querySelector('#errorAlert');
    if (errorAlert) {
        errorAlert.style.display = "block";
        setTimeout(() => errorAlert.style.display = "none", 5000);
    }

    // Handle form submission with SweetAlert
    const editOrderForm = document.querySelector('#editOrderForm');
    editOrderForm.addEventListener('submit', async function(event) {
        event.preventDefault(); // Prevent default form submission

        // Show SweetAlert confirmation dialog
        const result = await Swal.fire({
            title: 'Are you sure, babe?',
            text: `Do you want to update Order #<?php echo htmlspecialchars($order_id); ?>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, update it!',
            cancelButtonText: 'Nope, cancel'
        });

        if (!result.isConfirmed) {
            return; // User canceled the update
        }

        // Create FormData object to send form data
        const formData = new FormData(editOrderForm);

        try {
            const response = await fetch('edit_order.php?id=<?php echo htmlspecialchars($order_id); ?>', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }

            const text = await response.text();
            console.log('Raw response:', text); // Log the raw response for debugging
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', text);
                throw new Error('Invalid response from server: Response is not valid JSON');
            }

            if (data.success) {
                await Swal.fire({
                    title: 'Updated!',
                    text: 'Order updated successfully, babe! 🎉',
                    icon: 'success',
                    confirmButtonColor: '#007bff'
                });
                window.location.href = '/admin/index.php?page=orders';
            } else {
                await Swal.fire({
                    title: 'Oops...',
                    text: 'Something went wrong: ' + data.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        } catch (error) {
            console.error('Fetch error:', error);
            await Swal.fire({
                title: 'Oops...',
                text: 'An error occurred: ' + error.message,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    });
});
</script>
</body>
</html>