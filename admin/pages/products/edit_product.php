<?php
require_once 'includes/config.php'; // Assumes this provides the Database class and $pdo connection

// ProductManager class to handle product operations
class ProductManager {
    private $pdo;
    private $product;
    private $successMessage = '';
    private $errorMessage = '';

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->fetchProduct(isset($_GET['id']) ? (int)$_GET['id'] : 0);
    }

    private function fetchProduct($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $this->product = $stmt->fetch();
            if (!$this->product) {
                $this->errorMessage = "Product not found.";
            }
        } catch (PDOException $e) {
            $this->errorMessage = "Error fetching product: " . $e->getMessage();
        }
    }

    public function updateProduct($data) {
        if (!$this->product) {
            return false;
        }

        $id = $this->product['id'];
        $title = trim($data['title'] ?? $this->product['title']);
        $description = trim($data['description'] ?? $this->product['description']);
        $price = floatval($data['price'] ?? $this->product['price']);
        $discount = intval($data['discount'] ?? $this->product['discount']);
        $category = trim($data['category'] ?? $this->product['category']);
        $sizes = isset($data['sizes']) ? implode(',', array_filter(explode(',', str_replace(' ', '', $data['sizes'])))) : $this->product['sizes'];
        $colors = isset($data['colors']) ? implode(',', array_filter(explode(',', str_replace(' ', '', $data['colors'])))) : $this->product['colors'];
        $stock = intval($data['stock'] ?? $this->product['stock']);

        // Basic validation
        if (empty($title) || empty($description) || empty($category) || empty($sizes) || empty($colors)) {
            $this->errorMessage = "Please fill in all required fields.";
            return false;
        }

        // Handle image upload (optional update)
        $image = $this->product['image']; // Default to existing image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $this->handleImageUpload($_FILES['image'], $this->product['image']);
            if ($image === false) {
                return false; // Image upload failed, error message already set
            }
        }

        try {
            // Update product in the database
            $stmt = $this->pdo->prepare("UPDATE products SET title = ?, description = ?, price = ?, discount = ?, category = ?, image = ?, sizes = ?, colors = ?, stock = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $description, $price, $discount, $category, $image, $sizes, $colors, $stock, $id]);
            return true;
        } catch (PDOException $e) {
            $this->errorMessage = "Error updating product: " . $e->getMessage();
            return false;
        }
    }

    private function handleImageUpload($file, $oldImage) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $oldImage; // Return existing image if no new upload
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
            // Optionally delete the old image if it exists
            if (!empty($oldImage) && file_exists($targetDir . $oldImage)) {
                unlink($targetDir . $oldImage);
            }
            return $imageName;
        } else {
            $this->errorMessage = "Error uploading image. Please check permissions and try again.";
            return false;
        }
    }

    public function getProduct() {
        return $this->product;
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }
}

// ProductView class to handle rendering
class ProductView {
    private $product;
    private $errorMessage;

    public function __construct($product, $errorMessage = '') {
        $this->product = $product;
        $this->errorMessage = $errorMessage;
    }

    public function render() {
        if (!$this->product) {
            echo "<h2>Product Not Found</h2>";
            echo "<p><a href='?page=products'>Back to Products</a></p>";
            return;
        }
        ?>
        <h2>Edit Product - <?php echo htmlspecialchars($this->product['title']); ?></h2>
        <p>Update the product details below.</p>

        <?php if ($this->errorMessage): ?>
            <div class="alert alert-danger"><?php echo $this->errorMessage; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="product-form" enctype="multipart/form-data">
            <label for="title">Product Title:</label><br>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($this->product['title']); ?>" required><br><br>

            <label for="description">Description:</label><br>
            <textarea id="description" name="description" rows="4" cols="50" required><?php echo htmlspecialchars($this->product['description']); ?></textarea><br><br>

            <label for="price">Price ($):</label><br>
            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $this->product['price']; ?>" required><br><br>

            <label for="discount">Discount (%):</label><br>
            <input type="number" id="discount" name="discount" min="0" max="100" value="<?php echo $this->product['discount']; ?>" required><br><br>

            <label for="category">Category:</label><br>
            <select id="category" name="category" required>
                <option value="">Select Category</option>
                <option value="Men" <?php echo $this->product['category'] === 'Men' ? 'selected' : ''; ?>>Men</option>
                <option value="Women" <?php echo $this->product['category'] === 'Women' ? 'selected' : ''; ?>>Women</option>
            </select><br><br>

            <label for="image">Product Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*"><br>
            <?php if (!empty($this->product['image'])): ?>
                <img src="../assets/images/products/<?php echo htmlspecialchars($this->product['image']); ?>" alt="<?php echo htmlspecialchars($this->product['title']); ?>" class="product-image" style="max-width: 100px; height: auto;">
                <input type="hidden" name="image" value="<?php echo htmlspecialchars($this->product['image']); ?>">
            <?php endif; ?><br><br>

            <label for="sizes">Sizes (comma-separated, e.g., S,M,L):</label><br>
            <input type="text" id="sizes" name="sizes" value="<?php echo implode(',', explode(',', $this->product['sizes'])); ?>" required><br><br>

            <label for="colors">Colors (comma-separated, e.g., White,Black):</label><br>
            <input type="text" id="colors" name="colors" value="<?php echo implode(',', explode(',', $this->product['colors'])); ?>" required><br><br>

            <label for="stock">Stock:</label><br>
            <input type="number" id="stock" name="stock" min="0" value="<?php echo $this->product['stock']; ?>" required><br><br>

            <input type="submit" value="Update Product">
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
    if ($productManager->updateProduct($_POST)) {
        header("Location: ?page=products");
        exit;
    }
}

$productView = new ProductView($productManager->getProduct(), $productManager->getErrorMessage());
$productView->render();