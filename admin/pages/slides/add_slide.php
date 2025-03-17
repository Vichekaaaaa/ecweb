<?php
// pages/slides/add_slide.php
// No need for session_start() here since it's already started in index.php

// Include database connection
require_once 'includes/config.php';

// SlideManager class to handle slide operations
class SlideManager {
    private $pdo;
    private $successMessage = '';
    private $errorMessage = '';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addSlide($data) {
        $title = trim($data['title'] ?? '');
        $image = $this->handleImageUpload($_FILES['image'] ?? null);

        // Basic validation
        if (empty($title)) {
            $this->errorMessage = "Please provide a slide title.";
            return false;
        }

        if ($image === false) {
            return false; // Image upload failed, error message already set
        }

        try {
            // Insert new slide into the database
            $stmt = $this->pdo->prepare("INSERT INTO slides (title, image, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$title, $image]);
            $this->successMessage = "<div class='success-message'>
                                     <h2>Slide Added</h2>
                                     <p>The slide '$title' has been added successfully.</p>
                                     <p><a href='?page=slides'>Back to Slides</a></p>
                                     </div>";
            return true;
        } catch (PDOException $e) {
            $this->errorMessage = "Error adding slide: " . $e->getMessage();
            return false;
        }
    }

    private function handleImageUpload($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->errorMessage = "No image uploaded or upload failed. Please try again.";
            return false;
        }

        $imageName = uniqid() . '_' . basename($file['name']);
        $targetDir = '../assets/images/slides/';
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

// SlideView class to handle rendering
class SlideView {
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
        <h2>Add Slide</h2>
        <p>Fill out the form to add a new slide.</p>

        <?php if ($this->errorMessage): ?>
            <div class="alert alert-danger"><?php echo $this->errorMessage; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="product-form" enctype="multipart/form-data">
            <label for="title">Slide Title:</label><br>
            <input type="text" id="title" name="title" value="" required><br><br>

            <label for="image">Slide Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*" required><br><br>

            <input type="submit" value="Add Slide">
            <a href="?page=slides" class="back-button">Back</a>
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

$slideManager = new SlideManager($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slideManager->addSlide($_POST);
}

$slideView = new SlideView($slideManager->getSuccessMessage(), $slideManager->getErrorMessage());
$slideView->render();