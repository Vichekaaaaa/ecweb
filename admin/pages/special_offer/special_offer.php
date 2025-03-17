<?php
require_once 'includes/config.php';

// SpecialOfferManager class to handle special offer operations
class SpecialOfferManager {
    private $pdo;
    private $offers = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->fetchOffers();
    }

    private function fetchOffers() {
        try {
            $stmt = $this->pdo->query("SELECT id, special_key, special_value FROM special_offer");
            $this->offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->offers = [['error' => htmlspecialchars($e->getMessage())]];
        }
    }

    public function getOffers() {
        return $this->offers;
    }
}

// SpecialOfferView class to handle rendering
class SpecialOfferView {
    private $offers;
    private $action;
    private $id;

    public function __construct($offers, $action = '', $id = 0) {
        $this->offers = $offers;
        $this->action = $action;
        $this->id = $id;
    }

    public function render() {
        if ($this->action === 'edit' && $this->id > 0) {
            include 'edit.php';
            return;
        }
        if ($this->action === 'delete' && $this->id > 0) {
            include 'delete.php';
            return;
        }

        // Handle success message from session
        if (isset($_SESSION['success'])) {
            echo "<p style='color: green;'>" . htmlspecialchars($_SESSION['success']) . "</p>";
            unset($_SESSION['success']);
        }
        ?>
        <div class="table-container">
            <h2>Manage Special Offers</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Key</th>
                        <th>Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($this->offers) || (isset($this->offers[0]['error']))) {
                        echo "<tr><td colspan='4'>" . (isset($this->offers[0]['error']) ? $this->offers[0]['error'] : "No special offers found.") . "</td></tr>";
                    } else {
                        foreach ($this->offers as $offer) {
                            $id = isset($offer['id']) ? intval($offer['id']) : 'N/A';
                            $key = isset($offer['special_key']) ? htmlspecialchars($offer['special_key'], ENT_QUOTES, 'UTF-8') : 'N/A';
                            $value = isset($offer['special_value']) ? htmlspecialchars($offer['special_value'], ENT_QUOTES, 'UTF-8') : 'N/A';

                            echo "<tr>
                                <td>{$id}</td>
                                <td>{$key}</td>
                                <td>{$value}</td>
                                <td>
                                    <a href='?page=special_offer&action=edit&id={$id}' class='btn btn-primary'>Edit</a>
                                    <a href='?page=special_offer&action=delete&id={$id}' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to delete this special offer?\")'>Delete</a>
                                </td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Main execution
$database = new Database(); // Assuming Database class is defined in config.php
$pdo = $database->getConnection();

if (!$pdo) {
    die("Failed to get PDO connection from Database class.");
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$specialOfferManager = new SpecialOfferManager($pdo);
$offers = $specialOfferManager->getOffers();

$specialOfferView = new SpecialOfferView($offers, $action, $id);
$specialOfferView->render();