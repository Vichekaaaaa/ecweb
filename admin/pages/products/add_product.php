<?php
require_once 'includes/config.php'; // Assumes this provides the Database class and $pdo connection

// ProductManager class to handle product operations
class ProductManager {
    private $pdo;
    private $successMessage = '';
    private $errorMessage = '';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addProduct($data) {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $price = floatval($data['price'] ?? 0);
        $discount = intval($data['discount'] ?? 0);
        $category = trim($data['category'] ?? '');
        $sizes = isset($data['sizes']) ? implode(',', array_filter(explode(',', str_replace(' ', '', $data['sizes'])))) : '';
        $colors = isset($data['colors']) ? implode(',', array_filter(explode(',', str_replace(' ', '', $data['colors'])))) : '';
        $stock = intval($data['stock'] ?? 0);
        $image = $this->handleImageUpload($_FILES['image'] ?? null);

        // Basic validation
        if (empty($title) || empty($description) || empty($category) || empty($sizes) || empty($colors)) {
            $this->errorMessage = "Please fill in all required fields.";
            return false;
        }

        if ($image === false) {
            return false; // Image upload failed, error message already set
        }

        try {
            // Insert new product into the database
            $stmt = $this->pdo->prepare("INSERT INTO products (title, description, price, discount, category, image, sizes, colors, stock, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$title, $description, $price, $discount, $category, $image, $sizes, $colors, $stock]);
            return true;
        } catch (PDOException $e) {
            $this->errorMessage = "Error adding product: " . $e->getMessage();
            return false;
        }
    }

    private function handleImageUpload($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        $imageName = uniqid() . '_' . basename($file['name']);
        $targetDir = '../assets/images/products/';
        $targetFile = $targetDir . $imageName;

        // Actual upload
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Validation for file type and size
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file['type'], $allowedTypes)) {
                $this->errorMessage = "Invalid file type. Please upload an image (JPG, PNG, JPEG).";
                unlink($targetFile);
                return false;
            }
            if ($file['size'] > 2000000) { // 2MB limit
                $this->errorMessage = "File too large. Maximum size is 2MB.";
                unlink($targetFile);
                return false;
            }
            return $imageName;
        } else {
            $this->errorMessage = "Error uploading image. Please check permissions and try again.";
            return false;
        }
    }

    public function getSuccessMessage() {
        return $this->successMessage;
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }
}

// ProductView class to handle rendering
class ProductView {
    private $errorMessage;

    public function __construct($errorMessage = '') {
        $this->errorMessage = $errorMessage;
    }

    public function render() {
        ?>
        <h2>Add Product</h2>
        <p>Fill out the form to add a new product.</p>

        <?php if ($this->errorMessage): ?>
            <div class="alert alert-danger"><?php echo $this->errorMessage; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="product-form" enctype="multipart/form-data">
            <label for="title">Product Title:</label><br>
            <input type="text" id="title" name="title" value="" required><br><br>

            <label for="description">Description:</label><br>
            <textarea id="description" name="description" rows="4" cols="50" required></textarea><br><br>

            <label for="price">Price ($):</label><br>
            <input type="number" id="price" name="price" step="0.01" min="0" value="0" required><br><br>

            <label for="discount">Discount (%):</label><br>
            <input type="number" id="discount" name="discount" min="0" max="100" value="0" required><br><br>

            <label for="category">Category:</label><br>
            <select id="category" name="category" required>
                <option value="">Select Category</option>
                <option value="Men">Men</option>
                <option value="Women">Women</option>
            </select><br><br>

            <label for="image">Product Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*" required><br><br>

            <label for="sizes">Sizes (comma-separated, e.g., S,M,L):</label><br>
            <input type="text" id="sizes" name="sizes" value="" required><br><br>

            <label for="colors">Colors (comma-separated, e.g., White,Black):</label><br>
            <input type="text" id="colors" name="colors" value="" required><br><br>

            <label for="stock">Stock:</label><br>
            <input type="number" id="stock" name="stock" min="0" value="0" required><br><br>

            <input type="submit" value="Add Product">
            <a href="?page=products" class="back-button">Back</a>
        </form>
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($productManager->addProduct($_POST)) {
        header("Location: ?page=products");
        exit;
    }
}

$productView = new ProductView($productManager->getErrorMessage());
$productView->render();