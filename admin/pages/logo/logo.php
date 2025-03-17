<?php
include_once 'includes/config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



class LogoManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getLatestLogo() {
        try {
            $stmt = $this->pdo->query("SELECT id, title, logo_value, created_at, updated_at FROM logo ORDER BY id DESC LIMIT 1");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error fetching logos: " . $e->getMessage());
        }
    }
}

// Initialize database and LogoManager
$database = new Database();
$pdo = $database->getConnection();
$logoManager = new LogoManager($pdo);
$logos = $logoManager->getLatestLogo();
?>

<div class="container">
    <h2>Logo</h2>
    <p>Manage your logo here.</p>

    <div>
        <?php if (empty($logos)): ?>
            <a href="?page=logo&action=edit_logo" class="add-new-product-button">Add New Logo</a>
        <?php endif; ?>
    </div>

    <table class="product-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Image</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logos)): ?>
                <tr>
                    <td colspan="6">No logos found. Add a logo to begin.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logos as $logo): ?>
                    <tr>
                        <td><?php echo $logo['id']; ?></td>
                        <td><?php echo htmlspecialchars($logo['title'] ?? 'No Title'); ?></td>
                        <td>
                            <?php if (!empty($logo['logo_value'])): ?>
                                <img src="../assets/images/logo/<?php echo htmlspecialchars($logo['logo_value']); ?>" alt="<?php echo htmlspecialchars($logo['title'] ?? 'Logo'); ?>" class="product-image" style="max-width: 136px; height: auto;">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($logo['created_at'])); ?></td>
                        <td><?php echo !empty($logo['updated_at']) ? date('Y-m-d H:i', strtotime($logo['updated_at'])) : 'N/A'; ?></td>
                        <td>
                            <a href="?page=logo&action=edit_logo&id=<?php echo $logo['id']; ?>" class="btn btn-warning">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>