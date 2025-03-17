<?php
// pages/slides/delete_slide.php
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
        $this->deleteSlide(isset($_GET['id']) ? (int)$_GET['id'] : 0);
    }

    private function deleteSlide($id) {
        try {
            // Fetch the slide to display in the confirmation and handle image deletion
            $stmt = $this->pdo->prepare("SELECT title, image FROM slides WHERE id = ?");
            $stmt->execute([$id]);
            $this->slide = $stmt->fetch();

            if (!$this->slide) {
                $this->errorMessage = "Slide not found.";
                return;
            }

            // Delete the slide from the database
            $stmt = $this->pdo->prepare("DELETE FROM slides WHERE id = ?");
            $stmt->execute([$id]);

            // Automatically delete the image file if it exists
            $targetDir = '../assets/images/slides/';
            if (!empty($this->slide['image']) && file_exists($targetDir . $this->slide['image'])) {
                unlink($targetDir . $this->slide['image']);
            }

            $this->successMessage = "<div class='success-message'>
                                     <h2>Slide Deleted</h2>
                                     <p>The slide '{$this->slide['title']}' has been deleted.</p>
                                     <p><a href='?page=slides'>Back to Slides</a></p>
                                     </div>";
        } catch (PDOException $e) {
            $this->errorMessage = "Error deleting slide: " . $e->getMessage();
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
        if ($this->errorMessage) {
            echo "<h2>Slide Not Found</h2>";
            echo "<p><a href='?page=slides'>Back to Slides</a></p>";
            return;
        }

        if ($this->successMessage) {
            echo $this->successMessage;
            return;
        }
    }
}

// Main execution
$database = new Database(); // Assuming Database class is defined in config.php
$pdo = $database->getConnection();

if (!$pdo) {
    die("Failed to get PDO connection from Database class.");
}

$slideManager = new SlideManager($pdo);
$slideView = new SlideView($slideManager->getSlide(), $slideManager->getSuccessMessage(), $slideManager->getErrorMessage());
$slideView->render();
?>

<style>
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        margin: 20px auto;
        width: 50%;
        border: 1px solid #c3e6cb;
        border-radius: 4px;
    }
</style>