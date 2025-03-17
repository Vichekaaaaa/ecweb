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
        $this->deleteCollection(isset($_GET['id']) ? (int)$_GET['id'] : 0);
    }

    private function deleteCollection($id) {
        try {
            // Fetch the collection to display in the confirmation and handle image deletion
            $stmt = $this->pdo->prepare("SELECT title, image FROM collections WHERE id = ?");
            $stmt->execute([$id]);
            $this->collection = $stmt->fetch();

            if (!$this->collection) {
                $this->errorMessage = "Collection not found.";
                return;
            }

            // Delete the collection from the database
            $stmt = $this->pdo->prepare("DELETE FROM collections WHERE id = ?");
            $stmt->execute([$id]);

            // Automatically delete the image file if it exists
            if (!empty($this->collection['image']) && file_exists($this->targetDir . $this->collection['image'])) {
                unlink($this->targetDir . $this->collection['image']);
            }

            $this->successMessage = "<div class='success-message'>
                                     <h2>Collection Deleted</h2>
                                     <p>The collection '{$this->collection['title']}' has been deleted.</p>
                                     <p><a href='?page=collections'>Back to Collections</a></p>
                                     </div>";
        } catch (PDOException $e) {
            $this->errorMessage = "Error deleting collection: " . $e->getMessage();
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
        if ($this->errorMessage) {
            echo "<h2>Collection Not Found</h2>";
            echo "<p><a href='?page=collections'>Back to Collections</a></p>";
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

$collectionManager = new CollectionManager($pdo);
$collectionView = new CollectionView($collectionManager->getCollection(), $collectionManager->getSuccessMessage(), $collectionManager->getErrorMessage());
$collectionView->render();
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