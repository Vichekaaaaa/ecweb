<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';

// UserManager class to handle user operations
class UserManager {
    private $pdo;
    private $successMessage = '';
    private $errorMessage = '';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function deleteUser($id) {
        if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
            $this->errorMessage = "Invalid user ID.";
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $success = $stmt->execute([$id]);

            if ($success) {
                $this->successMessage = "User with ID {$id} has been deleted successfully.";
                return true;
            }
            $this->errorMessage = "Failed to delete the user.";
            return false;
        } catch (PDOException $e) {
            $this->errorMessage = "Error: " . htmlspecialchars($e->getMessage());
            return false;
        }
    }

    public function getSuccessMessage() {
        return $this->successMessage;
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }
}

// Main execution
$database = new Database(); // Assuming Database class is defined in config.php
$pdo = $database->getConnection();

if (!$pdo) {
    die("Failed to get PDO connection from Database class.");
}

$userManager = new UserManager($pdo);

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($userManager->deleteUser($id)) {
        $_SESSION['success_message'] = $userManager->getSuccessMessage();
    } else {
        $_SESSION['error_message'] = $userManager->getErrorMessage();
    }
} else {
    $_SESSION['error_message'] = "No user ID provided.";
}

// Redirect back to the user list page
header("Location: ?page=users");
exit;