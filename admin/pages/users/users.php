<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';

// UserManager class to handle user operations
class UserManager {
    private $pdo;
    private $users = [];
    private $user = null;
    private $successMessage = '';
    private $errorMessage = '';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function fetchUsers($searchTerm = '') {
        try {
            if ($searchTerm) {
                $stmt = $this->pdo->prepare("SELECT id, full_name, username, email, phone_number, birth_date, gender, created_at FROM users WHERE id = ? OR full_name LIKE ?");
                $stmt->execute([$searchTerm, "%$searchTerm%"]);
            } else {
                $stmt = $this->pdo->query("SELECT id, full_name, username, email, phone_number, birth_date, gender, created_at FROM users");
            }
            $this->users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->errorMessage = "Error fetching users: " . htmlspecialchars($e->getMessage());
        }
    }

    public function fetchUser($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $this->user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$this->user) {
                $this->errorMessage = "User not found.";
            }
        } catch (PDOException $e) {
            $this->errorMessage = "Error fetching user: " . htmlspecialchars($e->getMessage());
        }
    }

    public function addUser($data) {
        $full_name = trim($data['full_name'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone_number = trim($data['phone_number'] ?? '');
        $birth_date = trim($data['birth_date'] ?? '');
        $gender = trim($data['gender'] ?? '');

        // Basic validation
        if (empty($full_name) || empty($username) || empty($email)) {
            $this->errorMessage = "Full name, username, and email are required.";
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (full_name, username, email, phone_number, birth_date, gender) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $username, $email, $phone_number, $birth_date, $gender])) {
                $this->successMessage = "The user '{$full_name}' has been added successfully.";
                return true;
            }
            $this->errorMessage = "Failed to add the user. Please try again.";
            return false;
        } catch (PDOException $e) {
            $this->errorMessage = "Error: " . htmlspecialchars($e->getMessage());
            return false;
        }
    }

    public function updateUser($data, $id) {
        $full_name = trim($data['full_name'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone_number = trim($data['phone_number'] ?? '');
        $birth_date = trim($data['birth_date'] ?? '');
        $gender = trim($data['gender'] ?? '');

        // Basic validation
        if (empty($full_name) || empty($username) || empty($email)) {
            $this->errorMessage = "Full name, username, and email are required.";
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone_number = ?, birth_date = ?, gender = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $username, $email, $phone_number, $birth_date, $gender, $id])) {
                $this->successMessage = "The user '{$full_name}' has been updated successfully.";
                return true;
            }
            $this->errorMessage = "Failed to update the user. Please try again.";
            return false;
        } catch (PDOException $e) {
            $this->errorMessage = "Error: " . htmlspecialchars($e->getMessage());
            return false;
        }
    }

    public function deleteUser($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                $this->successMessage = "User deleted successfully.";
                return true;
            }
            $this->errorMessage = "Failed to delete the user.";
            return false;
        } catch (PDOException $e) {
            $this->errorMessage = "Error: " . htmlspecialchars($e->getMessage());
            return false;
        }
    }

    public function getUsers() {
        return $this->users;
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
    private $users;
    private $user;
    private $action;
    private $searchTerm;
    private $successMessage;
    private $errorMessage;

    public function __construct($users, $user, $action, $searchTerm, $successMessage = '', $errorMessage = '') {
        $this->users = $users;
        $this->user = $user;
        $this->action = $action;
        $this->searchTerm = $searchTerm;
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
            <title>Users</title>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
            <style>
                body {
                    background-color: #f4f6f9;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 1400px;
                    margin: 40px auto;
                    padding: 0 20px;
                }
                h2 {
                    font-size: 32px;
                    font-weight: 700;
                    color: #ffffff;
                    margin-bottom: 15px;
                    text-align: center;
                    background: linear-gradient(135deg, #2c3e50, #3498db);
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }
                p {
                    font-size: 16px;
                    color: #7f8c8d;
                    text-align: center;
                    margin-bottom: 30px;
                }
                .action-bar {
                    display: flex;
                    align-items: center;
                    margin-bottom: 20px;
                    background-color: #fff;
                    padding: 15px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }
                .search-form {
                    display: flex;
                    align-items: center;
                    flex: 1;
                    margin-right: 10px;
                }
                .search-form input[type="text"] {
                    padding: 8px 12px;
                    font-size: 14px;
                    border: 1px solid #ced4da;
                    border-right: none;
                    border-radius: 20px 0 0 20px;
                    outline: none;
                    width: 100%;
                    box-sizing: border-box;
                }
                .search-form button {
                    padding: 8px 12px;
                    font-size: 14px;
                    background-color: #fff;
                    border: 1px solid #ced4da;
                    border-left: none;
                    border-radius: 0 20px 20px 0;
                    cursor: pointer;
                    color: #007bff;
                }
                .search-form button:hover {
                    background-color: #e9ecef;
                }
                .back-button {
                    display: inline-block;
                    padding: 6px 12px;
                    background-color: #6c757d;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    margin-left: 10px;
                    cursor: pointer;
                }
                .back-button:hover {
                    background-color: #5a6268;
                    color: white;
                }
                .btn-primary {
                    display: inline-block;
                    padding: 6px 12px;
                    background-color: #007bff;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    cursor: pointer;
                }
                .btn-primary:hover {
                    background-color: #0056b3;
                }
                .table-responsive {
                    margin-top: 20px;
                    overflow-x: auto;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    background-color: #fff;
                }
                table thead th {
                    background-color: #007bff;
                    color: white;
                    padding: 10px;
                    text-align: left;
                    border-bottom: 2px solid #0056b3;
                }
                table tbody td {
                    padding: 10px;
                    border-bottom: 1px solid #dee2e6;
                }
                table tbody tr:hover {
                    background-color: #f1f1f1;
                }
                .btn {
                    display: inline-block;
                    padding: 5px 10px;
                    font-size: 12px;
                    text-decoration: none;
                    border-radius: 4px;
                    cursor: pointer;
                }
                .btn-warning {
                    background-color: #ffc107;
                    color: #212529;
                }
                .btn-warning:hover {
                    background-color: #e0a800;
                }
                .btn-custom {
                    background-color: #dc3545;
                    color: white;
                    border: none;
                }
                .btn-custom:hover {
                    background-color: #c82333;
                    color: white;
                }
                .alert {
                    display: none;
                    position: fixed;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    z-index: 1050;
                    padding: 10px 20px;
                    border-radius: 4px;
                    color: white;
                }
                .alert-success {
                    background-color: #28a745;
                }
                .alert-danger {
                    background-color: #dc3545;
                }
                .mb-3 {
                    margin-bottom: 15px;
                }
                .form-label {
                    display: block;
                    margin-bottom: 5px;
                }
                .form-control {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                }
            </style>
        </head>
        <body>
        <div class="container">
            <h2>Users</h2>
            <p>Manage your user list here.</p>

            <!-- Success/Error Messages -->
            <?php if ($this->successMessage): ?>
                <div id="successAlert" class="alert alert-success"><?php echo htmlspecialchars($this->successMessage); ?></div>
            <?php endif; ?>
            <?php if ($this->errorMessage): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($this->errorMessage); ?></div>
            <?php endif; ?>

            <?php if ($this->action === 'view'): ?>
                <div class="action-bar">
                    <div class="search-form">
                        <form method="GET" action="users.php" style="display: flex; align-items: center; width: 100%;">
                            <input type="hidden" name="page" value="users">
                            <input type="text" name="search" placeholder="Search by ID or Name" value="<?php echo htmlspecialchars($this->searchTerm); ?>" aria-label="Search">
                            <button type="submit"><i class="fas fa-search"></i></button>
                            <a href="users.php?page=users" class="back-button">Clear</a>
                        </form>
                    </div>
                    <a href="index.php?page=users&action=add_user" class="btn-primary">Add New User</a>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Birth Date</th>
                                <th>Gender</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($this->users)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center;">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($this->users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($user['birth_date']); ?></td>
                                        <td><?php echo htmlspecialchars($user['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                        <td>
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">Edit</a>
                                            <a href="?page=users&action=delete_user&id=<?php echo $user['id']; ?>" class="btn btn-custom" onclick="return confirm('Are you sure?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($this->action === 'add_user'): ?>
                <h2>Add New User</h2>
                <p>Add a new user to the system.</p>
                <form method="POST">
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
                    <button type="submit" class="btn btn-primary">Add User</button>
                    <a href="users.php" class="back-button">Back</a>
                </form>
            <?php elseif ($this->action === 'edit_user' && $this->user): ?>
                <h2>Edit User</h2>
                <form method="POST">
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
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="users.php" class="back-button">Back</a>
                </form>
            <?php endif; ?>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const successAlert = document.getElementById("successAlert");
                if (successAlert) {
                    successAlert.style.display = "block";
                    setTimeout(function() {
                        window.location.href = "users.php";
                    }, 100);
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

$action = $_GET['action'] ?? 'view';
$id = $_GET['id'] ?? null;
$searchTerm = $_GET['search'] ?? '';

$userManager = new UserManager($pdo);

// Handle user addition or editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add_user' || $action === 'edit_user')) {
    if ($action === 'add_user') {
        if ($userManager->addUser($_POST)) {
            header("Location: users.php");
            exit;
        }
    } elseif ($action === 'edit_user' && $id) {
        if ($userManager->updateUser($_POST, $id)) {
            header("Location: users.php");
            exit;
        }
    }
}

// Handle user deletion
if ($action === 'delete_user' && $id) {
    $userManager->deleteUser($id);
}

// Fetch users (for view) and user (for edit)
$userManager->fetchUsers($searchTerm);
if ($action === 'edit_user' && $id) {
    $userManager->fetchUser($id);
    if (!$userManager->getUser()) {
        header("Location: users.php");
        exit;
    }
}

$userView = new UserView(
    $userManager->getUsers(),
    $userManager->getUser(),
    $action,
    $searchTerm,
    $userManager->getSuccessMessage(),
    $userManager->getErrorMessage()
);
$userView->render();