<?php
// pages/slides/edit_slide.php
// No need for session_start() here since it's already started in index.php

// Include database connection
require_once 'includes/config.php';

// SlideManager class to handle slide operations
class SlideManager {
    private $pdo;
    private $slide;
    private $successMessage = '';
    private $errorMessage = '';

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->fetchSlide(isset($_GET['id']) ? (int)$_GET['id'] : 0);
    }

    private function fetchSlide($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM slides WHERE id = ?");
            $stmt->execute([$id]);
            $this->slide = $stmt->fetch();
            if (!$this->slide) {
                $this->errorMessage = "Slide not found.";
            }
        } catch (PDOException $e) {
            $this->errorMessage = "Error fetching slide: " . $e->getMessage();
        }
    }

    public function updateSlide($data) {
        if (!$this->slide) {
            return false;
        }

        $id = $this->slide['id'];
        $title = trim($data['title'] ?? $this->slide['title']);
        $image = $this->slide['image']; // Default to existing image

        // Basic validation
        if (empty($title)) {
            $this->errorMessage = "Please provide a slide title.";
            return false;
        }

        // Handle image upload (optional update)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = $this->handleImageUpload($_FILES['image'], $this->slide['image']);
            if ($image === false) {
                return false; // Image upload failed, error message already set
            }
        }

        try {
            // Update slide in the database
            $stmt = $this->pdo->prepare("UPDATE slides SET title = ?, image = ?, created_at = ? WHERE id = ?");
            $stmt->execute([$title, $image, $this->slide['created_at'], $id]);
            $this->successMessage = "<div class='success-message'>
                                     <h2>Slide Updated</h2>
                                     <p>The slide '$title' has been updated successfully.</p>
                                     <p><a href='?page=slides'>Back to Slides</a></p>
                                     </div>";
            return true;
        } catch (PDOException $e) {
            $this->errorMessage = "Error updating slide: " . $e->getMessage();
            return false;
        }
    }

    private function handleImageUpload($file, $oldImage) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $oldImage; // Return existing image if no new upload
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

    public function getSlide() {
        return $this->slide;
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
    private $slide;
    private $successMessage;
    private $errorMessage;

    public function __construct($slide, $successMessage = '', $errorMessage = '') {
        $this->slide = $slide;
        $this->successMessage = $successMessage;
        $this->errorMessage = $errorMessage;
    }

    public function render() {
        if ($this->successMessage) {
            echo $this->successMessage;
            return;
        }

        if (!$this->slide) {
            echo "<h2>Slide Not Found</h2>";
            echo "<p><a href='?page=slides'>Back to Slides</a></p>";
            return;
        }
        ?>
        <h2>Edit Slide - <?php echo htmlspecialchars($this->slide['title']); ?></h2>
        <p>Update the slide details below.</p>

        <?php if ($this->errorMessage): ?>
            <div class="alert alert-danger"><?php echo $this->errorMessage; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="product-form" enctype="multipart/form-data">
            <label for="title">Slide Title:</label><br>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($this->slide['title']); ?>" required><br><br>

            <label for="image">Slide Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*"><br>
            <?php if (!empty($this->slide['image'])): ?>
                <img src="../assets/images/slides/<?php echo htmlspecialchars($this->slide['image']); ?>" alt="<?php echo htmlspecialchars($this->slide['title']); ?>" class="product-image" style="max-width: 100px; height: auto;">
                <input type="hidden" name="image" value="<?php echo htmlspecialchars($this->slide['image']); ?>">
            <?php endif; ?><br><br>

            <input type="submit" value="Update Slide">
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
    $slideManager->updateSlide($_POST);
}

$slideView = new SlideView($slideManager->getSlide(), $slideManager->getSuccessMessage(), $slideManager->getErrorMessage());
$slideView->render();