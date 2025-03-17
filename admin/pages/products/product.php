<?php
require_once 'includes/config.php'; // Assumes this provides the Database class and $pdo connection

// ProductManager class to handle product operations
class ProductManager {
    private $pdo;
    private $search;
    private $successMessage = '';
    private $errorMessage = '';

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->search = isset($_GET['search']) ? trim($_GET['search']) : '';
    }

    public function handleDelete() {
        if (isset($_GET['action']) && $_GET['action'] === 'delete_product' && isset($_GET['id'])) {
            $id = $_GET['id'];
            try {
                $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $this->successMessage = "Product deleted successfully.";
                } else {
                    $this->errorMessage = "Failed to delete the product.";
                }
            } catch (PDOException $e) {
                $this->errorMessage = "Error: " . $e->getMessage();
            }
        }
    }

    public function fetchProducts() {
        try {
            if (!empty($this->search)) {
                $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ? OR title LIKE ? ORDER BY id ASC");
                $searchParam = is_numeric($this->search) ? (int)$this->search : "%$this->search%";
                $stmt->execute([$searchParam, $searchParam]);
            } else {
                $stmt = $this->pdo->query("SELECT * FROM products ORDER BY id ASC");
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Error fetching products: " . $e->getMessage());
        }
    }

    public function getSuccessMessage() {
        return $this->successMessage;
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }

    public function getSearch() {
        return $this->search;
    }
}

// ProductView class to handle rendering
class ProductView {
    private $products;
    private $successMessage;
    private $errorMessage;
    private $search;

    public function __construct($products, $successMessage, $errorMessage, $search) {
        $this->products = $products;
        $this->successMessage = $successMessage;
        $this->errorMessage = $errorMessage;
        $this->search = $search;
    }

    public function render() {
        ?>
        <style>
            /* Removed body styles to avoid conflicts with index.php and custom stylesheets */
            .container {
                max-width: 100%;
                margin: 40px auto;
                padding: 0 20px;
                margin-left: -20px;

            }

            /* Header Styling */
            h2 {
                font-size: 32px;
                font-weight: 700;
                color: #ffffff;
                margin-bottom: 15px;
                text-align: center;
                background: linear-gradient(135deg, #2c3e50, #3498db);
                padding: 20px;
                border-radius: 10px 10px 0 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            p {
                font-size: 16px;
                color: #7f8c8d;
                text-align: center;
                margin-bottom: 30px;
            }

            /* Action Bar Styling (Adjusted to match screenshot with Bootstrap Icons) */
            .action-bar {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                background-color: #fff;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
                border-radius: 20px 0 0 20px;
                outline: none;
                width: 100%;
                box-sizing: border-box;
            }
            .search-form button {
                padding: 8px 12px;
                font-size: 14px;
                background-color: #fff;
                border: 1px solid #ced4da;
                border-left: none;
                border-radius: 0 20px 20px 0;
                cursor: pointer;
                color: #007bff;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .search-form button:hover {
                background-color: #e9ecef;
            }
            .search-form button i {
                font-size: 14px; /* Ensure icon size matches the input field */
            }
            .back-button {
                display: inline-block;
                padding: 6px 12px;
                background-color: #6c757d;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-left: 10px;
                cursor: pointer;
                font-size: 14px;
            }
            .back-button:hover {
                background-color: #5a6268;
                color: white;
            }
            .btn-primary {
                display: inline-block;
                padding: 6px 12px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
            }
            .btn-primary:hover {
                background-color: #0056b3;
            }

            /* Table Styling (Unchanged) */
            .table-responsive {
                overflow-x: auto;
                background: #ffffff;
                border-radius: 10px;
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
                margin-bottom: 20px;
            }

            table {
                width: 100%;
                min-width: 800px;
                border-collapse: collapse;
                border-spacing: 0;
            }

            table thead th {
                background-color: #3498db;
                color: #ffffff;
                padding: 12px 10px;
                text-align: left;
                font-size: 14px;
                font-weight: 600;
                text-transform: uppercase;
                border-bottom: 3px solid #2980b9;
                border-top: none;
                white-space: nowrap;
                position: sticky;
                top: 0;
                z-index: 1;
            }

            table thead th:first-child {
                border-top-left-radius: 10px;
            }

            table thead th:last-child {
                border-top-right-radius: 10px;
            }

            table tbody td {
                padding: 10px 8px;
                border-bottom: 1px solid #ecf0f1;
                font-size: 14px;
                color: #34495e;
                vertical-align: middle;
                white-space: nowrap;
                border-right: 1px solid #ecf0f1;
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Specific column width adjustments */
            table tbody td:nth-child(5), /* Discount(%) column */
            table thead th:nth-child(5) {
                max-width: 60px; /* Narrower width for Discount column */
                padding: 10px 4px; /* Reduced padding */
            }

            table tbody td:nth-child(11), /* Created At column */
            table thead th:nth-child(11),
            table tbody td:nth-child(12), /* Updated At column */
            table thead th:nth-child(12) {
                max-width: 80px; /* Narrower width for date columns */
                padding: 10px 4px; /* Reduced padding */
            }

            table tbody tr:last-child td {
                border-bottom: none;
            }

            table tbody tr:nth-child(even) {
                background-color: #f9fbfd;
            }

            table tbody tr:hover {
                background-color: #ecf0f1;
                transition: background-color 0.3s ease;
            }

            .product-image {
                max-width: 50px;
                max-height: 50px;
                border-radius: 8px;
                border: 2px solid #ecf0f1;
                object-fit: cover;
                transition: transform 0.3s ease;
                vertical-align: middle;
            }

            .product-image:hover {
                transform: scale(1.1);
            }

            /* Button Styling (Unchanged) */
            .btn {
                display: inline-block;
                padding: 8px 12px;
                font-size: 12px;
                text-decoration: none;
                border-radius: 6px;
                cursor: pointer;
                transition: background-color 0.3s ease, transform 0.2s ease;
                margin-right: 3px;
            }

            .btn-warning {
                background-color: #f1c40f;
                color: #ffffff;
            }

            .btn-warning:hover {
                background-color: #e67e22;
                transform: translateY(-2px);
            }

            .btn-custom {
                background-color: #e74c3c;
                color: #ffffff;
            }

            .btn-custom:hover {
                background-color: #c0392b;
                transform: translateY(-2px);
            }

            /* Alert Styling (Unchanged) */
            .alert {
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 1050;
                padding: 15px 30px;
                border-radius: 8px;
                color: #ffffff;
                font-size: 15px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: fadeIn 0.5s ease-in-out;
            }

            .alert-success {
                background-color: #2ecc71;
            }

            .alert-danger {
                background-color: #e74c3c;
            }

            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            /* Responsive Design (Unchanged) */
            @media (max-width: 768px) {
                .action-bar {
                    flex-direction: column;
                    gap: 15px;
                }
                .search-form {
                    width: 100%;
                }
                table thead th,
                table tbody td {
                    font-size: 12px;
                    padding: 8px 6px;
                }
                .product-image {
                    max-width: 40px;
                    max-height: 40px;
                }
                .table-responsive {
                    min-width: 600px;
                }
                table tbody td:nth-child(5),
                table thead th:nth-child(5) {
                    max-width: 50px;
                }
                table tbody td:nth-child(11),
                table thead th:nth-child(11),
                table tbody td:nth-child(12),
                table thead th:nth-child(12) {
                    max-width: 70px;
                }
            }
        </style>

        <div class="container">
            <h2>Products</h2>
            <p>Manage your product list here.</p>

            <!-- Success/Error Messages -->
            <?php if ($this->successMessage): ?>
                <div id="successAlert" class="alert alert-success"><?php echo $this->successMessage; ?></div>
            <?php endif; ?>
            <?php if ($this->errorMessage): ?>
                <div class="alert alert-danger"><?php echo $this->errorMessage; ?></div>
            <?php endif; ?>

            <div class="action-bar">
                <div class="search-form">
                    <form method="GET" action="" style="display: flex; align-items: center; width: 100%;">
                        <input type="hidden" name="page" value="products">
                        <input type="text" name="search" placeholder="Search by ID or Name" value="<?php echo htmlspecialchars($this->search); ?>" aria-label="Search">
                        <button type="submit"><i class="bi bi-search"></i></button>
                        <a href="?page=products" class="back-button">Clear</a>
                    </form>
                </div>
                <a href="?page=products&action=add_product" class="btn-primary">Add New Product</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Discount(%)</th>
                            <th>Category</th>
                            <th>Image</th>
                            <th>Sizes</th>
                            <th>Colors</th>
                            <th>Stock</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->products)): ?>
                            <tr>
                                <td colspan="13" style="text-align: center; padding: 20px; color: #718096;">No products found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($this->products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($product['title'], 0, 15)) . (strlen($product['title']) > 15 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars(substr($product['description'], 0, 20)) . (strlen($product['description']) > 20 ? '...' : ''); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['discount']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($product['category'], 0, 10)) . (strlen($product['category']) > 10 ? '...' : ''); ?></td>
                                    <td>
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="product-image">
                                        <?php else: ?>
                                            <span style="color: #718096;">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo implode(', ', explode(',', substr($product['sizes'], 0, 10))) . (strlen($product['sizes']) > 10 ? '...' : ''); ?></td>
                                    <td><?php echo implode(', ', explode(',', substr($product['colors'], 0, 10))) . (strlen($product['colors']) > 10 ? '...' : ''); ?></td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td><?php echo substr(date('Y-m-d', strtotime($product['created_at'])), 0, 7); ?></td> <!-- Shortened to YYYY-MM -->
                                    <td><?php echo substr(date('Y-m-d', strtotime($product['updated_at'])), 0, 7); ?></td> <!-- Shortened to YYYY-MM -->
                                    <td>
                                        <a href="?page=products&action=edit_product&id=<?php echo $product['id']; ?>" class="btn btn-warning">Edit</a>
                                        <a href="?page=products&action=delete_product&id=<?php echo $product['id']; ?>" class="btn btn-custom" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const successAlert = document.getElementById("successAlert");
                if (successAlert) {
                    successAlert.style.display = "block";
                    setTimeout(function() {
                        window.location.href = "?page=products";
                    }, 100);
                }
            });
        </script>
        <?php
    }
}

// Main execution
$database = new Database(); // Assuming Database class is defined in config.php
$pdo = $database->getConnection();

if (!$pdo) {
    die("Failed to get PDO connection from Database class.");
}

$productManager = new ProductManager($pdo);
$productManager->handleDelete();
$products = $productManager->fetchProducts();

$productView = new ProductView(
    $products,
    $productManager->getSuccessMessage(),
    $productManager->getErrorMessage(),
    $productManager->getSearch()
);
$productView->render();