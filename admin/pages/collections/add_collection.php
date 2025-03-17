<?php
require_once 'includes/config.php';

// CollectionManager class to handle collection operations
class CollectionManager {
    private $pdo;
    private $successMessage = '';
    private $errorMessage = '';
    private $targetDir = '../assets/images/collections/'; // Define target directory

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addCollection($data) {
        $title = trim($data['title'] ?? '');
        $image = $this->handleImageUpload($_FILES['image'] ?? null);

        // Basic validation
        if (empty($title)) {
            $this->errorMessage = "Please provide a collection title.";
            return false;
        }

        if ($image === false) {
            return false; // Image upload failed, error message already set
        }

        try {
            // Insert new collection into the database
            $stmt = $this->pdo->prepare("INSERT INTO collections (title, image, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$title, $image]);
            $this->successMessage = "<div class='success-message'>
                                     <h2>Collection Added</h2>
                                     <p>The collection '$title' has been added successfully.</p>
                                     <p><a href='?page=collections'>Back to Collections</a></p>
                                     </div>";
            return true;
        } catch (PDOException $e) {
            $this->errorMessage = "Error adding collection: " . $e->getMessage();
            return false;
        }
    }

    private function handleImageUpload($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->errorMessage = "No image uploaded or upload failed. Please try again.";
            return false;
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

// CollectionView class to handle rendering
class CollectionView {
    private $successMessage;
    private $errorMessage;

    public function __construct($successMessage = '', $errorMessage = '') {
        $this->successMessage = $successMessage;
        $this->errorMessage = $errorMessage;
    }

    public function render() {
        if ($this->successMessage) {
            echo $this->successMessage;
            return;
        }
        ?>
        <h2>Add Collection</h2>
        <p>Fill out the form to add a new collection.</p>

        <?php if ($this->errorMessage): ?>
            <div class="alert alert-danger"><?php echo $this->errorMessage; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="product-form" enctype="multipart/form-data">
            <label for="title">Collection Title:</label><br>
            <input type="text" id="title" name="title" value="" required><br><br>

            <label for="image">Collection Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*" required><br><br>

            <input type="submit" value="Add Collection">
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
    $collectionManager->addCollection($_POST);
}

$collectionView = new CollectionView($collectionManager->getSuccessMessage(), $collectionManager->getErrorMessage());
$collectionView->render();