<?php
require_once 'includes/config.php';

// CollectionManager class to handle collection operations
class CollectionManager {
    private $pdo;
    private $collections = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->fetchCollections();
    }

    private function fetchCollections() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM collections ORDER BY id ASC");
            $this->collections = $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Error fetching collections: " . $e->getMessage());
        }
    }

    public function getCollections() {
        return $this->collections;
    }
}

// CollectionView class to handle rendering
class CollectionView {
    private $collections;

    public function __construct($collections) {
        $this->collections = $collections;
    }

    public function render() {
        ?>
        <div class="container">
            <h2>Collections</h2>
            <p>Manage your collection list here.</p>

            <div class="product-actions-bar">
                <a href="?page=collections&action=add_collection" class="add-new-product-button">Add New Collection</a>
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
                    <?php if (empty($this->collections)): ?>
                        <tr>
                            <td colspan="5">No collections found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($this->collections as $collection): ?>
                            <tr>
                                <td><?php echo $collection['id']; ?></td>
                                <td><?php echo htmlspecialchars($collection['title']); ?></td>
                                <td>
                                    <?php if (!empty($collection['image'])): ?>
                                        <img src="../assets/images/collections/<?php echo htmlspecialchars($collection['image']); ?>" alt="<?php echo htmlspecialchars($collection['title']); ?>" class="product-image">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($collection['created_at'])); ?></td>
                                <td>
                                    <a href="?page=collections&action=edit_collection&id=<?php echo $collection['id']; ?>">Edit</a> |
                                    <a href="?page=collections&action=delete_collection&id=<?php echo $collection['id']; ?>" onclick="return confirm('Are you sure you want to delete this collection?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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

$collectionManager = new CollectionManager($pdo);
$collections = $collectionManager->getCollections();

$collectionView = new CollectionView($collections);
$collectionView->render();