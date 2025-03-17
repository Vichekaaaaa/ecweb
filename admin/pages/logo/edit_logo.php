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

    public function getLogoById($id) {
        try {
            if ($id > 0) {
                $stmt = $this->pdo->prepare("SELECT id, title, logo_value, created_at, updated_at FROM logo WHERE id = ?");
                $stmt->execute([$id]);
                $logo = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$logo) {
                    return null;
                }
                return $logo;
            }
            // Include updated_at as NULL for new logos
            return ['id' => 0, 'title' => '', 'logo_value' => '', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => null];
        } catch (PDOException $e) {
            die("Error fetching logo: " . $e->getMessage());
        }
    }

    public function saveLogo($id, $title, $logo_value, $created_at) {
        try {
            if ($id > 0) {
                // Update existing logo with updated_at set to NOW()
                $stmt = $this->pdo->prepare("UPDATE logo SET title = ?, logo_value = ?, created_at = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $logo_value, $created_at, $id]);
                return "updated";
            } else {
                // Insert new logo, updated_at will be NULL initially
                $stmt = $this->pdo->prepare("INSERT INTO logo (title, logo_value, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$title, $logo_value]);
                return "added";
            }
        } catch (PDOException $e) {
            die("Error " . ($id > 0 ? "updating" : "adding") . " logo: " . $e->getMessage());
        }
    }
}

// Initialize database and LogoManager
$database = new Database();
$pdo = $database->getConnection();
$logoManager = new LogoManager($pdo);

// Fetch logo data
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$logo = $logoManager->getLogoById($id);

if (!$logo && $id > 0) {
    echo "<h2>Logo Not Found</h2>";
    echo "<p><a href='?page=logo'>Back to Logo</a></p>";
    exit;
}

// Variables to hold alert states
$alert = '';
$alertTitle = '';
$alertMessage = '';
$alertType = '';
$redirect = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $logo_value = $logo['logo_value'] ?? '';

    // Basic validation
    if (empty($title)) {
        $alert = 'error';
        $alertTitle = 'Error';
        $alertMessage = 'Please provide a title for the logo.';
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetDir = '../assets/images/logo/';
        $targetFile = $targetDir . $imageName;

        // Check image dimensions
        list($width, $height) = getimagesize($_FILES['image']['tmp_name']);
        if ($width != 136 || $height != 73) {
            $alert = 'error';
            $alertTitle = 'Invalid Image Size';
            $alertMessage = 'Logo must be exactly 136x73 pixels.';
        } else {
            // Upload the image
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $logo_value = $imageName;
                if (!empty($logo['logo_value']) && file_exists($targetDir . $logo['logo_value'])) {
                    unlink($targetDir . $logo['logo_value']);
                }
            } else {
                $alert = 'error';
                $alertTitle = 'Upload Error';
                $alertMessage = 'Error uploading image. Please check permissions and try again.';
            }

            // Validation for file type and size
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                $alert = 'error';
                $alertTitle = 'Invalid File Type';
                $alertMessage = 'Please upload an image (JPG, PNG, JPEG).';
                unlink($targetFile);
            } elseif ($_FILES['image']['size'] > 2000000) {
                $alert = 'error';
                $alertTitle = 'File Too Large';
                $alertMessage = 'Maximum size is 2MB.';
                unlink($targetFile);
            }
        }
    }

    // If no errors, save the logo
    if (empty($alert)) {
        $action = $logoManager->saveLogo($id, $title, $logo_value, $logo['created_at']);
        $alert = 'success';
        $alertTitle = 'Success';
        $alertMessage = "The logo \"$title\" has been $action successfully.";
        $redirect = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $logo['id'] > 0 ? "Edit Logo" : "Add New Logo"; ?></title>
    <!-- Include SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <h2><?php echo $logo['id'] > 0 ? "Edit Logo" : "Add New Logo"; ?> - <?php echo htmlspecialchars($logo['title'] ?? 'New Logo'); ?></h2>
    <p>Update or add the logo details below.</p>

    <form method="POST" action="" class="product-form" enctype="multipart/form-data" id="logoForm">
        <label for="title">Logo Title:</label><br>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($logo['title'] ?? ''); ?>" required><br><br>

        <label for="image">Logo Image (136x73 pixels):</label><br>
        <input type="file" id="image" name="image" accept="image/*" <?php echo $logo['id'] == 0 ? 'required' : ''; ?>><br>
        <?php if (!empty($logo['logo_value'])): ?>
            <img src="../assets/images/logo/<?php echo htmlspecialchars($logo['logo_value']); ?>" alt="<?php echo htmlspecialchars($logo['title'] ?? 'Logo'); ?>" class="product-image" style="max-width: 136px; height: auto;">
            <input type="hidden" name="image" value="<?php echo htmlspecialchars($logo['logo_value']); ?>">
        <?php endif; ?><br><br>

        <!-- Display created_at and updated_at for reference -->
        <label>Created At:</label><br>
        <input type="text" value="<?php echo date('Y-m-d H:i', strtotime($logo['created_at'])); ?>" readonly><br><br>
        <label>Updated At:</label><br>
        <input type="text" value="<?php echo !empty($logo['updated_at']) ? date('Y-m-d H:i', strtotime($logo['updated_at'])) : 'N/A'; ?>" readonly><br><br>

        <input type="submit" value="<?php echo $logo['id'] > 0 ? "Update Logo" : "Add Logo"; ?>">
        <a href="?page=logo" class="back-button">Back</a>
    </form>

    <?php if (!empty($alert)): ?>
        <script>
            Swal.fire({
                title: '<?php echo $alertTitle; ?>',
                text: '<?php echo $alertMessage; ?>',
                icon: '<?php echo $alert; ?>',
                confirmButtonText: 'OK'
            }).then((result) => {
                <?php if ($redirect): ?>
                    if (result.isConfirmed) {
                        window.location.href = '?page=logo';
                    }
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>
</body>
</html>