<?php
// pages/slides/slide.php
// No need for session_start() here since it's already started in index.php

// Include database connection
require_once 'includes/config.php';

// SlideManager class to handle slide operations
class SlideManager {
    private $pdo;
    private $slides = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->fetchSlides();
    }

    private function fetchSlides() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM slides ORDER BY id ASC");
            $this->slides = $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Error fetching slides: " . $e->getMessage());
        }
    }

    public function getSlides() {
        return $this->slides;
    }
}

// SlideView class to handle rendering
class SlideView {
    private $slides;

    public function __construct($slides) {
        $this->slides = $slides;
    }

    public function render() {
        ?>
        <h2>Slides</h2>
        <p>Manage your slide list here.</p>

        <!-- Add New Slide Bar (only Add New Slide button) -->
        <div class="table-container">
            <a href="?page=slides&action=add_slide" class="btn btn-primary">Add New Slide</a>
        </div>

        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Image</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($this->slides)): ?>
                    <tr>
                        <td colspan="5">No slides found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($this->slides as $slide): ?>
                        <tr>
                            <td><?php echo $slide['id']; ?></td>
                            <td><?php echo htmlspecialchars($slide['title']); ?></td>
                            <td>
                                <?php if (!empty($slide['image'])): ?>
                                    <img src="../assets/images/slides/<?php echo htmlspecialchars($slide['image']); ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>" class="product-image">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($slide['created_at'])); ?></td>
                            <td>
                                <a href="?page=slides&action=edit_slide&id=<?php echo $slide['id']; ?>">Edit</a> |
                                <a href="?page=slides&action=delete_slide&id=<?php echo $slide['id']; ?>" onclick="return confirm('Are you sure you want to delete this slide?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
$slides = $slideManager->getSlides();

$slideView = new SlideView($slides);
$slideView->render();