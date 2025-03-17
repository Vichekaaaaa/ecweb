<?php
include_once 'includes/config.php'; // Adjust path to reach the root

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Instantiate Database class to get PDO connection
$database = new Database();
$pdo = $database->getConnection();

class AboutUsManager {
    private $pdo;
    private $logDir;
    private $logFile;
    private $message = '';
    private $editId = null;
    private $token;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->logDir = dirname(__FILE__) . '/../logs';
        $this->logFile = $this->logDir . '/token_debug.log';

        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['token'])) {
            $_SESSION['token'] = md5(uniqid(mt_rand(), true));
        }
        $this->token = $_SESSION['token'];
    }

    public function getToken() {
        return $this->token;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getEditId() {
        return $this->editId;
    }

    public function processForm() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->fetchData();
        }

        error_log("Received token: " . ($_POST['token'] ?? 'null') . ", Session token: " . $this->token, 3, $this->logFile);

        if (!isset($_POST['token']) || $_POST['token'] !== $this->token) {
            $this->message = "Error: Invalid request token.";
            error_log("Token mismatch: Received " . ($_POST['token'] ?? 'null') . ", Expected " . $this->token, 3, $this->logFile);
            return $this->fetchData();
        }

        $section = $_POST['section'] ?? '';
        $content = $_POST['content'] ?? '';
        $name = $_POST['name'] ?? null;
        $role = $_POST['role'] ?? null;
        $imagePath = $_POST['image_path'] ?? null;
        $this->editId = $_POST['edit_id'] ?? null;
        $deleteId = $_POST['delete_id'] ?? null;

        if ($deleteId) {
            $this->handleDelete($deleteId);
        } elseif ($section && $content) {
            $this->handleSaveOrUpdate($section, $content, $name, $role, $imagePath);
        }

        return $this->fetchData();
    }

    private function handleDelete($deleteId) {
        try {
            $stmt = $this->pdo->prepare("SELECT image_path FROM about_us WHERE id = :id");
            $stmt->execute([':id' => $deleteId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['image_path']) && file_exists($row['image_path'])) {
                if (!unlink($row['image_path'])) {
                    error_log("Failed to delete image file: " . $row['image_path'], 3, $this->logFile);
                    $this->message = "Warning: Entry deleted, but image file could not be removed.";
                }
            }

            $stmt = $this->pdo->prepare("DELETE FROM about_us WHERE id = :id");
            $stmt->execute([':id' => $deleteId]);
            $this->message = $this->message ?: "Entry deleted successfully!";
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage(), 3, $this->logFile);
            $this->message = "Error: Could not delete entry.";
        }
    }

    private function handleSaveOrUpdate($section, $content, $name, $role, $imagePath) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->handleImageUpload($this->editId);
            if (strpos($imagePath, 'Error') === 0) {
                $this->message = $imagePath;
                return;
            }
        }

        try {
            $params = [
                ':section' => $section,
                ':content' => $content,
                ':image_path' => $imagePath,
                ':name' => $name,
                ':role' => $role
            ];

            if ($this->editId) {
                $params[':id'] = $this->editId;
                $stmt = $this->pdo->prepare("UPDATE about_us SET section = :section, content = :content, image_path = :image_path, name = :name, role = :role WHERE id = :id");
                $stmt->execute($params);
                $this->message = "Entry updated successfully!";
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO about_us (section, content, image_path, name, role) VALUES (:section, :content, :image_path, :name, :role)");
                $stmt->execute($params);
                $this->message = "Content added successfully!";
            }

            $_SESSION['token'] = md5(uniqid(mt_rand(), true));
            $this->token = $_SESSION['token'];
            $this->editId = null;
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage(), 3, $this->logFile);
            $this->message = "Error: Database operation failed.";
        }
    }

    private function handleImageUpload($editId) {
        $targetDir = "../assets/images/about_us/";
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            return "Error: Could not create directory.";
        }
        if (!is_writable($targetDir) && !chmod($targetDir, 0755)) {
            return "Error: Directory is not writable.";
        }

        $fileName = uniqid() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '', basename($_FILES['image']['name']));
        $targetFile = $targetDir . $fileName;
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExt, $allowedExts)) {
            return "Error: Only JPEG, PNG, and GIF files are allowed.";
        }

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            return "Error: Failed to upload image.";
        }

        if ($editId) {
            $stmt = $this->pdo->prepare("SELECT image_path FROM about_us WHERE id = :id");
            $stmt->execute([':id' => $editId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['image_path']) && file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
        }

        return $targetFile;
    }

    private function fetchData() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM about_us ORDER BY section, name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage(), 3, $this->logFile);
            $this->message = "Error fetching data.";
            return [];
        }
    }
}

// Instantiate the manager with the PDO object from Database class
$manager = new AboutUsManager($pdo);
$aboutData = $manager->processForm();
$message = $manager->getMessage();
$editId = $manager->getEditId();
$token = $manager->getToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Us - KaSim Store</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
            margin: 0;
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
        h3 {
            font-size: 32px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 15px;
            text-align: center;
            background: linear-gradient(135deg,rgb(28, 188, 251), #3498db);
            padding: 20px;
            border-radius: 10px 10px 0 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .message.success {
            background: #e6ffe6;
            color: #2d862d;
            border: 1px solid #b3ffb3;
        }
        .message.error {
            background: #ffe6e6;
            color: #cc0000;
            border: 1px solid #ffcccc;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 500;
            color: #1a2b49;
            margin-bottom: 6px;
            font-size: 0.95rem;
        }

        input, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e4e8;
            border-radius: 8px;
            font-size: 1rem;
            background: #fafbfc;
            transition: border-color 0.3s, background 0.3s;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #4a90e2;
            background: #fff;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        small {
            color: #7a8799;
            font-size: 0.85rem;
            margin-top: 4px;
            display: block;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #357abd;
        }

        .edit-btn, .delete-btn {
            padding: 8px 16px;
            margin: 0 5px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .edit-btn {
            background: #4a90e2;
            color: white;
        }

        .edit-btn:hover {
            background: #357abd;
        }

        .delete-btn {
            background: #e74c3c;
            color: white;
        }

        .delete-btn:hover {
            background: #c0392b;
        }

        .data-table {
            overflow-x: auto;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e4e8;
        }

        th {
            background: #4a90e2;
            color: white;
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        td {
            color: #555;
        }

        tr:hover {
            background: #f9fbfc;
        }

        img {
            max-width: 80px;
            max-height: 80px;
            border-radius: 4px;
            object-fit: cover;
        }

        @media (max-width: 600px) {
            .content {
                padding: 20px;
                margin: 20px;
            }
            th, td {
                padding: 10px;
                font-size: 0.9rem;
            }
            .edit-btn, .delete-btn {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage About Us</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="aboutUsForm">
            <input type="hidden" name="edit_id" id="edit_id" value="<?php echo htmlspecialchars($editId ?? ''); ?>">
            <input type="hidden" name="token" id="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="section">Section:</label>
                <input type="text" name="section" id="section" placeholder="e.g., Our Vision" required>
                <small>Enter a custom section name (e.g., Our Story, Meet Our Team).</small>
            </div>

            <div class="form-group">
                <label for="content">Content:</label>
                <textarea name="content" id="content" placeholder="Enter content here" rows="5" required></textarea>
            </div>

            <div class="form-group">
                <label for="image">Upload Image (optional):</label>
                <input type="file" name="image" id="image" accept="image/*">
            </div>

            <div class="form-group">
                <label for="image_path">Image Path (if not uploading):</label>
                <input type="text" name="image_path" id="image_path" placeholder="e.g., ../assets/images/about_us/example.jpg">
            </div>

            <div class="form-group">
                <label for="name">Name (for team members):</label>
                <input type="text" name="name" id="name" placeholder="Team member name">
            </div>

            <div class="form-group">
                <label for="role">Role (for team members):</label>
                <input type="text" name="role" id="role" placeholder="Team member role">
            </div>

            <button type="submit" id="submitButton">Save</button>
        </form>

        <h3>Current Data</h3>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Content</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aboutData as $item): ?>
                        <tr data-id="<?php echo htmlspecialchars($item['id']); ?>">
                            <td><?php echo htmlspecialchars($item['section']); ?></td>
                            <td><?php echo htmlspecialchars($item['content']); ?></td>
                            <td>
                                <?php if ($item['image_path'] && file_exists($item['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="Image">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['role'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="edit-btn" data-id="<?php echo htmlspecialchars($item['id']); ?>">Edit</button>
                                <button class="delete-btn" data-id="<?php echo htmlspecialchars($item['id']); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($aboutData)): ?>
                        <tr><td colspan="6">No data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const aboutData = <?php echo json_encode($aboutData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function updateFormFields(data = null) {
            const sectionField = document.getElementById('section');
            const contentField = document.getElementById('content');
            const imagePathField = document.getElementById('image_path');
            const nameField = document.getElementById('name');
            const roleField = document.getElementById('role');
            const editIdField = document.getElementById('edit_id');
            const tokenField = document.getElementById('token');
            const submitButton = document.getElementById('submitButton');

            if (data) {
                sectionField.value = data.section || '';
                contentField.value = data.content || '';
                imagePathField.value = data.image_path || '';
                nameField.value = data.name || '';
                roleField.value = data.role || '';
                editIdField.value = data.id || '';
                submitButton.textContent = 'Update';
            } else {
                sectionField.value = '';
                contentField.value = '';
                imagePathField.value = '';
                nameField.value = '';
                roleField.value = '';
                editIdField.value = '';
                submitButton.textContent = 'Save';
            }
        }

        function refreshTable() {
            fetch(window.location.href, {
                method: 'GET'
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('.data-table').innerHTML;
                document.querySelector('.data-table').innerHTML = newTable;
                initializeButtons();
                const newToken = doc.querySelector('#token').value;
                document.getElementById('token').value = newToken;
            })
            .catch(error => console.error('Error refreshing table:', error));
        }

        function initializeButtons() {
            const editButtons = document.querySelectorAll('.edit-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = button.getAttribute('data-id');
                    const data = aboutData.find(item => String(item.id) === id);
                    if (data) {
                        updateFormFields(data);
                        document.getElementById('aboutUsForm').scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });

            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = button.getAttribute('data-id');
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(window.location.href, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'delete_id=' + encodeURIComponent(id) + '&token=' + encodeURIComponent(document.getElementById('token').value)
                            })
                            .then(response => {
                                if (!response.ok) throw new Error('Delete failed');
                                return response.text();
                            })
                            .then(() => {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'The entry has been deleted successfully.',
                                    icon: 'success',
                                    confirmButtonColor: '#28a745',
                                    timer: 2000
                                }).then(() => {
                                    window.location.reload();
                                });
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to delete entry. Check the console for details.',
                                    icon: 'error',
                                    confirmButtonColor: '#dc3545'
                                });
                            });
                        }
                    });
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            <?php if (!$editId): ?>
                updateFormFields();
            <?php else: ?>
                updateFormFields(aboutData.find(item => String(item.id) === '<?php echo $editId; ?>'));
            <?php endif; ?>

            initializeButtons();

            document.getElementById('aboutUsForm').addEventListener('submit', (e) => {
                const section = document.getElementById('section').value.trim();
                const content = document.getElementById('content').value.trim();
                if (!section || !content) {
                    e.preventDefault();
                    alert('Section and Content are required.');
                    return;
                }

                e.preventDefault();

                const editId = document.getElementById('edit_id').value;
                const actionText = editId ? 'update' : 'save';
                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to ${actionText} this entry?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4a90e2',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `Yes, ${actionText} it!`
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData(document.getElementById('aboutUsForm'));
                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newMessage = doc.querySelector('.message')?.textContent;
                            if (newMessage) {
                                document.querySelector('.message')?.remove();
                                const messageDiv = document.createElement('div');
                                messageDiv.className = newMessage.includes('Error') ? 'message error' : 'message success';
                                messageDiv.textContent = newMessage;
                                document.querySelector('.container').insertBefore(messageDiv, document.querySelector('form'));
                                setTimeout(() => messageDiv.style.display = 'none', 5000);
                            }
                   // Auto-refresh the entire page after successful save/update

                            if (!newMessage || !newMessage.includes('Error')) {
                                window.location.reload();
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                });
            });

            const messageDiv = document.querySelector('.message');
            if (messageDiv) {
                setTimeout(() => messageDiv.style.display = 'none', 5000);
            }
        });
    </script>
</body>
</html>