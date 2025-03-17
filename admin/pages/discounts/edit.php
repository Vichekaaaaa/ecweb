<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and initialize PDO
require_once 'includes/config.php';

// Debug: Check if Database class is available
if (!class_exists('Database')) {
    die("<p style='color: red;'>Error: Database class not found in includes/config.php</p>");
}

$database = new Database();
$pdo = $database->getConnection();

// Debug: Verify $pdo
if (!$pdo instanceof PDO) {
    die("<p style='color: red;'>Error: PDO connection failed. Check Database::getConnection()</p>");
}

class DiscountManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getDiscountById($id) {
        $query = "SELECT title, discount_percentage, description, link_url FROM discounts WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateDiscount($id, $title, $discount_percentage, $description, $link_url) {
        $query = "UPDATE discounts SET title = ?, discount_percentage = ?, description = ?, link_url = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$title, $discount_percentage, $description, $link_url, $id]);
        return true;
    }

    public function renderEditForm($id) {
        $discount = $this->getDiscountById($id);
        if (!$discount) {
            echo "<p>Discount not found.</p>";
            return;
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? $discount['title'];
            $discount_percentage = $_POST['discount_percentage'] ?? $discount['discount_percentage'];
            $description = $_POST['description'] ?? $discount['description'];
            $link_url = $_POST['link_url'] ?? $discount['link_url'];

            try {
                $this->updateDiscount($id, $title, $discount_percentage, $description, $link_url);
                $_SESSION['success'] = "Discount updated successfully!";
                header("Location: ?page=discounts"); // Adjust if your index uses a different query param
                exit;
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }

        // Render form
        $title = htmlspecialchars($discount['title']);
        $percentage = htmlspecialchars($discount['discount_percentage']);
        $description = htmlspecialchars($discount['description']);
        $link = htmlspecialchars($discount['link_url'] ?? '');

        echo '<div class="main-content">';
        echo '<h1 class="dashboard-title">Edit Discount</h1>';
        echo '<div class="dashboard-panel">';
        echo '<form method="post">';
        echo "<input type='hidden' name='id' value='{$id}'>";
        echo '<label>Discount Name:</label><br>';
        echo "<input type='text' name='title' value='{$title}' required><br>";
        echo '<label>Percentage (e.g., 20% or Special Offer):</label><br>';
        echo "<input type='text' name='discount_percentage' value='{$percentage}' required><br>";
        echo '<label>Description:</label><br>';
        echo "<textarea name='description' required>{$description}</textarea><br>";
        echo '<label>Link/Email (e.g., mailto:example@email.com or URL):</label><br>';
        echo "<input type='text' name='link_url' value='{$link}' required><br>";
        echo '<button type="submit">Update Discount</button>';
        echo '</form>';
        echo '<a href="?page=discounts" class="btn">Back to Discounts</a>';
        echo '</div></div>';
    }
}

// Instantiate and run
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("<p style='color: red;'>Invalid or missing ID.</p>");
}

$discountManager = new DiscountManager($pdo);
$discountManager->renderEditForm($id);
?>

<style>
    .main-content {
        margin-left: 50px;
        padding: 20px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    .dashboard-title {
        font-size: 2em;
        margin: 20px 0;
        color: #2c3e50;
    }
    .dashboard-panel {
        padding: 20px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    label {
        display: block;
        margin: 10px 0 5px;
    }
    input[type="text"], textarea {
        width: 100%;
        max-width: 400px;
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    textarea {
        height: 100px;
    }
    button {
        padding: 8px 16px;
        background-color: #2ecc71;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn {
        display: inline-block;
        padding: 8px 16px;
        background-color: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-top: 10px;
    }
</style>