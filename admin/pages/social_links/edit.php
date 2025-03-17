<?php
// edit.php
require_once 'includes/config.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

class SocialLinksManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSocialLink($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, platform, link, icon_name, status FROM social_links WHERE id = ?");
            $stmt->execute([$id]);
            $link = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$link) {
                throw new Exception("Social link not found.");
            }

            return $link;
        } catch (Exception $e) {
            die("Error: " . htmlspecialchars($e->getMessage()));
        }
    }

    public function updateSocialLink($id, $platform, $linkValue, $iconName, $status) {
        try {
            $platform = htmlspecialchars(strip_tags($platform), ENT_QUOTES, 'UTF-8');
            $linkValue = htmlspecialchars(strip_tags($linkValue), ENT_QUOTES, 'UTF-8');
            $iconName = htmlspecialchars(strip_tags($iconName), ENT_QUOTES, 'UTF-8');

            if (empty($platform) || empty($linkValue) || empty($iconName)) {
                return "All fields are required.";
            } elseif (!filter_var($linkValue, FILTER_VALIDATE_URL)) {
                return "Invalid URL format.";
            } else {
                $stmt = $this->pdo->prepare("UPDATE social_links SET platform = ?, link = ?, icon_name = ?, status = ? WHERE id = ?");
                $stmt->execute([$platform, $linkValue, $iconName, $status, $id]);
                $_SESSION['success'] = "Social link with ID {$id} updated successfully!";
                header("Location: ?page=social_links");
                exit;
            }
        } catch (PDOException $e) {
            die("Error: " . htmlspecialchars($e->getMessage()));
        }
    }

    public function renderEditForm($id) {
        $link = $this->getSocialLink($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
            $platform = $_POST['platform'] ?? '';
            $linkValue = $_POST['link'] ?? '';
            $iconName = $_POST['icon_name'] ?? '';
            $status = isset($_POST['status']) ? 1 : 0;

            $error = $this->updateSocialLink($id, $platform, $linkValue, $iconName, $status);
        }
        ?>
        <div class="edit-container">
            <h2>Edit Social Link</h2>
            <?php if (isset($error)) echo "<p style='color: red;'>{$error}</p>"; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="platform">Platform:</label>
                    <input type="text" name="platform" id="platform" value="<?php echo htmlspecialchars($link['platform'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="link">Link:</label>
                    <input type="url" name="link" id="link" value="<?php echo htmlspecialchars($link['link'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="icon_name">Icon Name:</label>
                    <input type="text" name="icon_name" id="icon_name" value="<?php echo htmlspecialchars($link['icon_name'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <input type="checkbox" name="status" id="status" value="1" <?php echo $link['status'] ? 'checked' : ''; ?>> Active
                </div>
                <button type="submit" name="update" class="btn btn-primary">Update Social Link</button>
                <a href="?page=social_links" class="btn btn-secondary">Cancel</a>
            </form>
        </div>

        <style>
            .edit-container { max-width: 600px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 5px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
            .btn { padding: 10px 20px; margin-right: 10px; }
            .btn-primary { background-color: #007bff; color: #fff; text-decoration: none; }
            .btn-secondary { background-color: #6c757d; color: #fff; text-decoration: none; }
            .btn:hover { opacity: 0.9; }
        </style>
        <?php
    }
}

// Main execution
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("Invalid ID.");
}

$socialLinksManager = new SocialLinksManager($pdo);
$socialLinksManager->renderEditForm($id);