<?php
require_once 'includes/config.php';

// SpecialOfferManager class to handle special offer operations
class SpecialOfferManager {
    private $pdo;
    private $offer;
    private $errorMessage = '';
    private $successMessage = '';

    public function __construct($pdo, $id) {
        $this->pdo = $pdo;
        $this->fetchOffer($id);
    }

    private function fetchOffer($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, special_key, special_value FROM special_offer WHERE id = ?");
            $stmt->execute([$id]);
            $this->offer = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$this->offer) {
                $this->errorMessage = "Special offer not found.";
            }
        } catch (PDOException $e) {
            $this->errorMessage = "Error: " . htmlspecialchars($e->getMessage());
        }
    }

    public function updateOffer($data) {
        if (!$this->offer) {
            return false;
        }

        $id = $this->offer['id'];
        $special_value = trim($data['special_value'] ?? '');

        // Basic validation
        if (empty($special_value)) {
            $this->errorMessage = "Value is required.";
            return false;
        }

        // Sanitize input
        $special_value = htmlspecialchars(strip_tags($special_value), ENT_QUOTES, 'UTF-8');

        try {
            $stmt = $this->pdo->prepare("UPDATE special_offer SET special_value = ? WHERE id = ?");
            $stmt->execute([$special_value, $id]);
            $_SESSION['success'] = "Special offer with ID {$id} updated successfully!";
            return true;
        } catch (PDOException $e) {
            $this->errorMessage = "Error updating special offer: " . htmlspecialchars($e->getMessage());
            return false;
        }
    }

    public function getOffer() {
        return $this->offer;
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }
}

// SpecialOfferView class to handle rendering
class SpecialOfferView {
    private $offer;
    private $errorMessage;

    public function __construct($offer, $errorMessage = '') {
        $this->offer = $offer;
        $this->errorMessage = $errorMessage;
    }

    public function render() {
        if (!$this->offer || $this->errorMessage === "Special offer not found.") {
            die($this->errorMessage ?: "Special offer not found.");
        }
        ?>
        <div class="edit-container">
            <h2>Edit Special Offer</h2>
            <?php if ($this->errorMessage): ?>
                <p style='color: red;'><?php echo $this->errorMessage; ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="special_value">Value:</label>
                    <textarea name="special_value" id="special_value" class="form-control" required><?php echo htmlspecialchars($this->offer['special_value'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <button type="submit" name="update" class="btn btn-primary">Update Special Offer</button>
                <a href="?page=special_offer" class="btn btn-secondary">Cancel</a>
            </form>
        </div>

        <style>
            .edit-container { max-width: 600px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 5px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
            .btn { padding: 10px 20px; margin-right: 10px; }
        </style>
        <?php
    }
}

// Main execution
$database = new Database(); // Assuming Database class is defined in config.php
$pdo = $database->getConnection();

if (!$pdo) {
    die("Failed to get PDO connection from Database class.");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$specialOfferManager = new SpecialOfferManager($pdo, $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    if ($specialOfferManager->updateOffer($_POST)) {
        header("Location: ?page=special_offer");
        exit;
    }
}

$specialOfferView = new SpecialOfferView($specialOfferManager->getOffer(), $specialOfferManager->getErrorMessage());
$specialOfferView->render();