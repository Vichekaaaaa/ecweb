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

    public function addUser($data) {
        $full_name = htmlspecialchars(trim($data['full_name'] ?? ''));
        $username = htmlspecialchars(trim($data['username'] ?? ''));
        $email = htmlspecialchars(trim($data['email'] ?? ''));
        $phone_number = htmlspecialchars(trim($data['phone_number'] ?? ''));
        $birth_date = htmlspecialchars(trim($data['birth_date'] ?? ''));
        $gender = htmlspecialchars(trim($data['gender'] ?? ''));
        $password = trim($data['password'] ?? '');

        // Basic validation
        if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
            $this->errorMessage = "Full name, username, email, and password are required.";
            return false;
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (full_name, username, email, phone_number, birth_date, gender, password_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $success = $stmt->execute([$full_name, $username, $email, $phone_number, $birth_date, $gender, $password_hash]);

            if ($success) {
                $this->successMessage = "The user '{$full_name}' has been added successfully.";
                return true;
            }
            $errorInfo = $stmt->errorInfo();
            $this->errorMessage = "Failed to add the user. Error: " . htmlspecialchars($errorInfo[2]);
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

// UserView class to handle rendering
class UserView {
    private $successMessage;
    private $errorMessage;

    public function __construct($successMessage = '', $errorMessage = '') {
        $this->successMessage = $successMessage;
        $this->errorMessage = $errorMessage;
    }

    public function render() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Add New User</title>
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
                <h2>Add New User</h2>
                <p>Add a new user to the system.</p>

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
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Birth Date</label>
                        <input type="date" name="birth_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add User</button>
                    <a href="?page=users" class="back-button">Back</a>
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

$userManager = new UserManager($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userManager->addUser($_POST);
}

$userView = new UserView($userManager->getSuccessMessage(), $userManager->getErrorMessage());
$userView->render();