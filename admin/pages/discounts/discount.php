<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and initialize PDO like dashboard.php
require_once 'includes/config.php';
$database = new Database();
$pdo = $database->getConnection();

class DiscountManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getDiscounts() {
        $query = "SELECT id, title, discount_percentage, link_url FROM discounts";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getDiscountById($id) {
        $query = "SELECT title, discount_percentage, description, link_url FROM discounts WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function processEditForm($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $discount_percentage = $_POST['discount_percentage'] ?? ''; // Save as-is
            $description = $_POST['description'] ?? '';
            $link_url = $_POST['link_url'] ?? '';

            $query = "UPDATE discounts SET title = ?, discount_percentage = ?, description = ?, link_url = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$title, $discount_percentage, $description, $link_url, $id]);
            
            $_SESSION['success'] = "Discount updated successfully!";
            header("Location: ?page=discounts");
            exit;
        }
    }

    public function render() {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action === 'edit' && $id > 0) {
            $this->renderEditForm($id);
        } else {
            $this->renderTable();
        }
    }

    private function renderTable() {
        $discounts = $this->getDiscounts();

        echo '<div class="main-content">';
        echo '<h1 class="dashboard-title">Manage Discounts</h1>';
        echo '<div class="dashboard-panel">';

        if (isset($_SESSION['success'])) {
            echo "<p style='color: green;'>" . htmlspecialchars($_SESSION['success']) . "</p>";
            unset($_SESSION['success']);
        }

        echo '<table>';
        echo '<thead><tr><th>ID</th><th>Discount Name</th><th>Percentage</th><th>Link</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        if (empty($discounts)) {
            echo "<tr><td colspan='5'>No discounts found.</td></tr>";
        } else {
            foreach ($discounts as $discount) {
                $id = htmlspecialchars($discount['id'] ?? 'N/A');
                $title = htmlspecialchars($discount['title'] ?? 'Unknown');
                $percentage = htmlspecialchars($discount['discount_percentage'] ?? 'N/A');
                $link = htmlspecialchars($discount['link_url'] ?? 'N/A');

                echo "<tr>
                    <td>{$id}</td>
                    <td>{$title}</td>
                    <td>{$percentage}</td>
                    <td>{$link}</td>
                    <td><a href='?page=discounts&action=edit&id={$id}' class='btn'>Edit</a></td>
                </tr>";
            }
        }

        echo '</tbody></table>';
        echo '</div></div>';
    }

    private function renderEditForm($id) {
        $discount = $this->getDiscountById($id);
        if (!$discount) {
            echo "<div class='main-content'><p>Discount not found.</p></div>";
            return;
        }

        $this->processEditForm($id);

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
$discountManager = new DiscountManager($pdo);
$discountManager->render();
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
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }
    th {
        background-color: #3498db;
        color: white;
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
</style>