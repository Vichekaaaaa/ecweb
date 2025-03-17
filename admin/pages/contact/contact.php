<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php'; // Assumes this provides Database class

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class SocialMediaManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (!$this->pdo) {
            throw new Exception("PDO connection is not initialized.");
        }
    }

    public function updateLink($platform, $url) {
        $platform = htmlspecialchars($platform ?? '');
        $url = htmlspecialchars($url ?? '');

        if (!$platform) return ['success' => false, 'message' => 'Platform is required.'];
        if (!$url) return ['success' => false, 'message' => 'URL is required.'];

        try {
            $platform = ($platform === 'Twitter') ? 'X' : $platform;
            $stmt = $this->pdo->prepare("SELECT id FROM social_media_links WHERE platform = :platform");
            $stmt->execute(['platform' => $platform]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $this->pdo->prepare("UPDATE social_media_links SET url = :url, updated_at = CURRENT_TIMESTAMP WHERE platform = :platform");
                $stmt->execute(['url' => $url, 'platform' => $platform]);
                return ['success' => true, 'message' => 'Link updated successfully.', 'platform' => $platform, 'url' => $url];
            }
            return ['success' => false, 'message' => 'The selected platform does not exist in the database.'];
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    public function getLinks() {
        try {
            $stmt = $this->pdo->query("SELECT platform, url FROM social_media_links");
            if ($stmt === false) {
                throw new Exception("Query failed: " . print_r($this->pdo->errorInfo(), true));
            }
            $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($links as $key => $link) {
                if ($link['platform'] === 'Twitter') {
                    $stmt = $this->pdo->prepare("UPDATE social_media_links SET platform = 'X' WHERE platform = 'Twitter'");
                    $stmt->execute();
                    $links[$key]['platform'] = 'X';
                }
            }
            return $links;
        } catch (Exception $e) {
            error_log("Error fetching links: " . $e->getMessage());
            return [];
        }
    }
}

class ContactManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (!$this->pdo) {
            throw new Exception("PDO connection is not initialized.");
        }
    }

    public function updateContact($email, $phone, $map_link) {
        $email = htmlspecialchars($email ?? '');
        $phone = htmlspecialchars($phone ?? '');
        $map_link = htmlspecialchars($map_link ?? '');

        if (!$email) return ['success' => false, 'message' => 'Email is required.'];
        if (!$phone) return ['success' => false, 'message' => 'Phone is required.'];

        try {
            $stmt = $this->pdo->prepare("SELECT id FROM contact_details LIMIT 1");
            $stmt->execute();
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $this->pdo->prepare("UPDATE contact_details SET email = :email, phone = :phone, map_link = :map_link, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                $stmt->execute(['email' => $email, 'phone' => $phone, 'map_link' => $map_link, 'id' => $existing['id']]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO contact_details (email, phone, map_link) VALUES (:email, :phone, :map_link)");
                $stmt->execute(['email' => $email, 'phone' => $phone, 'map_link' => $map_link]);
            }
            return ['success' => true, 'message' => 'Contact details updated successfully!'];
        } catch (PDOException $e) {
            error_log("PDOException: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    public function getContactDetails() {
        try {
            $stmt = $this->pdo->query("SELECT email, phone, map_link FROM contact_details LIMIT 1");
            $details = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'email' => $details['email'] ?? 'sales@shoestore.com',
                'phone' => $details['phone'] ?? '+1 (123) 456-7890',
                'map_link' => $details['map_link'] ?? 'https://maps.google.com/?q=YourStoreLocation'
            ];
        } catch (PDOException $e) {
            error_log("Error fetching contact details: " . $e->getMessage());
            return [
                'email' => 'sales@shoestore.com',
                'phone' => '+1 (123) 456-7890',
                'map_link' => 'https://maps.google.com/?q=YourStoreLocation'
            ];
        }
    }
}

// Initialize managers
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Failed to get PDO connection from Database class.");
}

try {
    $socialManager = new SocialMediaManager($pdo);
    $contactManager = new ContactManager($pdo);
} catch (Exception $e) {
    die("Error initializing managers: " . $e->getMessage());
}

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $response = [];
    if ($_POST['action'] === 'update_social') {
        $result = $socialManager->updateLink($_POST['platform'] ?? '', $_POST['url'] ?? '');
        $response = [
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message']
        ];
        if ($result['success']) {
            $response['platform'] = $result['platform'];
            $response['url'] = $result['url'];
        }
        http_response_code($result['success'] ? 200 : ($result['message'] === 'The selected platform does not exist in the database.' ? 400 : 500));
    } elseif ($_POST['action'] === 'update_contact') {
        $result = $contactManager->updateContact($_POST['email'] ?? '', $_POST['phone'] ?? '', $_POST['map_link'] ?? '');
        $response = [
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message']
        ];
        http_response_code($result['success'] ? 200 : 500);
    }
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get data for display
$links = $socialManager->getLinks();
$contact = $contactManager->getContactDetails();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Contact Info - KASIM Shoe Store</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 2em;
            color: #333;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 1.5em;
            color: #333;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-size: 1em;
            color: #555;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="email"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        button {
            background-color: #ff5733;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #e04e2d;
        }
        .links-table {
            margin-top: 20px;
        }
        .links-table p {
            margin: 5px 0;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <h1>Update Contact Information</h1>
        <h2>Contact Details</h2>
        <form method="POST" action="" id="contactForm">
            <input type="hidden" name="action" value="update_contact">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($contact['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($contact['phone']); ?>" required>
            </div>
            <div class="form-group">
                <label for="map_link">Map Link:</label>
                <input type="text" id="map_link" name="map_link" value="<?php echo htmlspecialchars($contact['map_link']); ?>" placeholder="e.g., https://maps.google.com/?q=YourStoreLocation">
            </div>
            <button type="submit" id="updateContactButton">Update Contact Details</button>
        </form>

        <h2>Social Media Links</h2>
        <form method="POST" action="" id="linkForm">
            <input type="hidden" name="action" value="update_social">
            <div class="form-group">
                <label for="platform">Platform:</label>
                <select id="platform" name="platform" required>
                    <option value="">Select a platform to update</option>
                    <?php foreach ($links as $link): ?>
                        <option value="<?php echo htmlspecialchars($link['platform']); ?>">
                            <?php echo htmlspecialchars($link['platform']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="url">URL:</label>
                <input type="text" id="url" name="url" required placeholder="e.g., https://x.com/kasimshoestore">
            </div>
            <button type="submit" id="updateButton">Update Link</button>
        </form>

        <div class="links-table" id="currentLinks">
            <h2>Current Links</h2>
            <?php foreach ($links as $link): ?>
                <p data-platform="<?php echo htmlspecialchars($link['platform']); ?>">
                    <strong><?php echo htmlspecialchars($link['platform']); ?>:</strong>
                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['url']); ?></a>
                </p>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.getElementById('linkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                console.log('Social Response status:', response.status);
                console.log('Social Response ok:', response.ok);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Social Parsed response:', data);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Link updated successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        const platform = data.platform;
                        const url = data.url;
                        const linkElement = document.querySelector(`#currentLinks p[data-platform="${platform}"]`);
                        if (linkElement) {
                            linkElement.innerHTML = `
                                <strong>${platform}:</strong>
                                <a href="${url}" target="_blank">${url}</a>
                            `;
                        }
                        document.getElementById('platform').value = '';
                        document.getElementById('url').value = '';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'An unknown error occurred.',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Social Fetch error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update link. Please try again.',
                    confirmButtonText: 'OK'
                });
            });
        });

        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(response => {
                console.log('Contact Response status:', response.status);
                console.log('Contact Response ok:', response.ok);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Contact Parsed response:', data);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'An unknown error occurred.',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Contact Fetch error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update contact details. Please try again.',
                    confirmButtonText: 'OK'
                });
            });
        });
    </script>
</body>
</html>