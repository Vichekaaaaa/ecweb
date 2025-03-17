<?php
require_once 'includes/config.php';

// CollectionManager class to handle collection operations
class CollectionManager {
    private $pdo;
    private $collection;
    private $successMessage = '';
    private $errorMessage = '';
    private $targetDir = '../assets/images/collections/'; // Define target directory

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->fetchCollection(isset($_GET['id']) ? (int)$_GET['id'] : 0);
    }

    private function fetchCollection($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM collections WHERE id = ?");
            $stmt->execute([$id]);
            $this->collection = $stmt->fetch();
            if (!$this->collection) {
                $this->errorMessage = "Collection not found.";
            }
        } catch (PDOException $e) {
            $this->errorMessage = "Error fetching collection: " . $e->getMessage();
        }
    }

    public function updateCollection($data) {
        if (!$this->collection) {
            return false;
        }

        $id = $this->collection['id'];
        $title = trim($data['title'] ?? $this->collection['title']);
        $image = $this->collection['image']; // Default to existing image

        // Basic validation
        if (empty($title)) {
            $this->errorMessage = "Please provide a collection title.";
            return false;
        }

        // Handle image upload (optional update)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $this->handleImageUpload($_FILES['image'], $this->collection['image']);
            if ($image === false) {
                return false; // Image upload failed, error message already set
            }
        }

        try {
            // Update collection in the database
            $stmt = $this->pdo->prepare("UPDATE collections SET title = ?, image = ?, created_at = ? WHERE id = ?");
            $stmt->execute([$title, $image, $this->collection['created_at'], $id]);
            $this->successMessage = "<div class='success-message'>
                                     <h2>Collection Updated</h2>
                                     <p>The collection '$title' has been updated successfully.</p>
                                     <p><a href='?page=collections'>Back to Collections</a></p>
                                     </div>";
            return true;
        } catch (PDOException $e) {
            $this->errorMessage = "Error updating collection: " . $e->getMessage();
            return false;
        }
    }

    private function handleImageUpload($file, $oldImage) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $oldImage; // Return existing image if no new upload
        }

        $imageName = uniqid() . '_' . basename($file['name']);
        $targetFile = $this->targetDir . $imageName;

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
            if (!empty($oldImage) && file_exists($this->targetDir . $oldImage)) {
                unlink($this->targetDir . $oldImage);
            }
            return $imageName;
        } else {
            $this->errorMessage = "Error uploading image. Please check permissions and try again.";
            return false;
        }
    }

    public function getCollection() {
        return $this->collection;
    }

    public function getSuccessMessage() {
        return $this->successMessage;
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }
}

// CollectionView class to handle rendering
class CollectionView {
    private $collection;
    private $successMessage;
    private $errorMessage;

    public function __construct($collection, $successMessage = '', $errorMessage = '') {
        $this->collection = $collection;
        $this->successMessage = $successMessage;
        $this->errorMessage = $errorMessage;
    }

    public function render() {
        if ($this->successMessage) {
            echo $this->successMessage;
            return;
        }

        if (!$this->collection) {
            echo "<h2>Collection Not Found</h2>";
            echo "<p><a href='?page=collections'>Back to Collections</a></p>";
            return;
        }
        ?>
        <h2>Edit Collection - <?php echo htmlspecialchars($this->collection['title']); ?></h2>
        <p>Update the collection details below.</p>

        <?php if ($this->errorMessage): ?>
            <div class="alert alert-danger"><?php echo $this->errorMessage; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="product-form" enctype="multipart/form-data">
            <label for="title">Collection Title:</label><br>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($this->collection['title']); ?>" required><br><br>

            <label for="image">Collection Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*"><br>
            <?php if (!empty($this->collection['image'])): ?>
                <img src="../assets/images/collections/<?php echo htmlspecialchars($this->collection['image']); ?>" alt="<?php echo htmlspecialchars($this->collection['title']); ?>" class="product-image" style="max-width: 100px; height: auto;">
                <input type="hidden" name="image" value="<?php echo htmlspecialchars($this->collection['image']); ?>">
            <?php endif; ?><br><br>

            <input type="submit" value="Update Collection">
            <a href="?page=collections" class="back-button">Back</a>
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

$collectionManager = new CollectionManager($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collectionManager->updateCollection($_POST);
}

$collectionView = new CollectionView($collectionManager->getCollection(), $collectionManager->getSuccessMessage(), $collectionManager->getErrorMessage());
$collectionView->render();