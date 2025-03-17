<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';

// UserManager class to handle user operations
class UserManager {
    private $pdo;
    private $user;
    private $successMessage = '';
    private $errorMessage = '';

    public function __construct($pdo, $id) {
        $this->pdo = $pdo;
        $this->fetchUser($id);
    }

    private function fetchUser($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $this->user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$this->user) {
                $this->errorMessage = "User not found.";
                header("Location: users.php");
                exit;
            }
        } catch (PDOException $e) {
            $this->errorMessage = "Error: " . htmlspecialchars($e->getMessage());
        }
    }

    public function updateUser($data, $id) {
        $full_name = htmlspecialchars(trim($data['full_name'] ?? ''));
        $username = htmlspecialchars(trim($data['username'] ?? ''));
        $email = htmlspecialchars(trim($data['email'] ?? ''));
        $phone_number = htmlspecialchars(trim($data['phone_number'] ?? ''));
        $birth_date = htmlspecialchars(trim($data['birth_date'] ?? ''));
        $gender = htmlspecialchars(trim($data['gender'] ?? ''));
        $password = trim($data['password'] ?? '');

        // Basic validation
        if (empty($full_name) || empty($username) || empty($email)) {
            $this->errorMessage = "Full name, username, and email are required.";
            return false;
        }

        // Hash the password if provided, otherwise keep the existing one
        $password_hash = $password ? password_hash($password, PASSWORD_DEFAULT) : $this->user['password_hash'];

        try {
            $stmt = $this->pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone_number = ?, birth_date = ?, gender = ?, password_hash = ? WHERE id = ?");
            $success = $stmt->execute([$full_name, $username, $email, $phone_number, $birth_date, $gender, $password_hash, $id]);

            if ($success) {
                $this->successMessage = "The user '{$full_name}' has been updated successfully.";
                return true;
            }
            $errorInfo = $stmt->errorInfo();
            $this->errorMessage = "Failed to update the user. Error: " . htmlspecialchars($errorInfo[2]);
            return false;
        } catch (PDOException $e) {
            $this->errorMessage = "Error: " . htmlspecialchars($e->getMessage());
            return false;
        }
    }

    public function getUser() {
        return $this->user;
    }

    public function getSuccessMessage() {
        return $this->successMessage;
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }
}

// UserView class to handle rendering
class UserView {
    private $user;
    private $successMessage;
    private $errorMessage;

    public function __construct($user, $successMessage = '', $errorMessage = '') {
        $this->user = $user;
        $this->successMessage = $successMessage;
        $this->errorMessage = $errorMessage;
    }

    public function render() {
        if (!$this->user) {
            echo "<div class='alert alert-danger'>User not found.</div>";
            exit;
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Edit User</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <style>
                .alert {
                    display: none;
                    position: fixed;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    z-index: 1050;
                }
                .back-button {
                    display: inline-block;
                    padding: 6px 12px;
                    background-color: #6c757d; /* Bootstrap secondary color */
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    margin-left: 10px;
                }
                .back-button:hover {
                    background-color: #5a6268;
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="container mt-5">
                <h2>Edit User</h2>
                <p>Edit the details of the user.</p>

                <!-- Success/Error Messages -->
                <?php if ($this->successMessage): ?>
                    <div id="successAlert" class="alert alert-success"><?php echo htmlspecialchars($this->successMessage); ?></div>
                <?php endif; ?>
                <?php if ($this->errorMessage): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($this->errorMessage); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($this->user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($this->user['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($this->user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($this->user['phone_number']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Birth Date</label>
                        <input type="date" name="birth_date" class="form-control" value="<?php echo htmlspecialchars($this->user['birth_date']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo $this->user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $this->user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $this->user['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                        <small class="form-text text-muted">Enter a new password to update it, otherwise leave blank.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="users.php" class="back-button">Back</a>
                </form>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const successAlert = document.getElementById("successAlert");
                    if (successAlert) {
                        successAlert.style.display = "block"; // Show the alert
                        setTimeout(function() {
                            window.location.href = "?page=users"; // Redirect after 0.1 seconds
                        }, 100); // 100 milliseconds = 0.1 seconds
                    }
                });
            </script>
        </body>
        </html>
        <?php
    }
}

// Main execution
$database = new Database(); // Assuming Database class is defined in config.php
$pdo = $database->getConnection();

if (!$pdo) {
    die("Failed to get PDO connection from Database class.");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: users.php");
    exit;
}

$userManager = new UserManager($pdo, $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userManager->updateUser($_POST, $id);
}

$userView = new UserView($userManager->getUser(), $userManager->getSuccessMessage(), $userManager->getErrorMessage());
$userView->render();