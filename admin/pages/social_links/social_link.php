<?php
// pages/social_links/social_links.php
include_once 'includes/config.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

class SocialLinksManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSocialLinks() {
        try {
            $stmt = $this->pdo->query("SELECT id, platform, link, icon_name, status FROM social_links");
            return $stmt->rowCount() > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException $e) {
            return ['error' => htmlspecialchars($e->getMessage())];
        }
    }

    public function renderTable() {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Handle edit action
        if ($action === 'edit' && $id > 0) {
            include '../edit.php'; // Path to pages/edit.php
            exit;
        }

        $links = $this->getSocialLinks();
        ?>
        <div class="table-container">
            <h2>Manage Social Links</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Platform</th>
                        <th>Link</th>
                        <th>Icon Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (isset($links['error'])) {
                        echo "<tr><td colspan='6'>Error: " . $links['error'] . "</td></tr>";
                    } elseif (empty($links)) {
                        echo "<tr><td colspan='6'>No social links found.</td></tr>";
                    } else {
                        foreach ($links as $link) {
                            $id = isset($link['id']) ? intval($link['id']) : 'N/A';
                            $platform = isset($link['platform']) ? htmlspecialchars($link['platform'], ENT_QUOTES, 'UTF-8') : 'Unknown';
                            $linkValue = isset($link['link']) ? htmlspecialchars($link['link'], ENT_QUOTES, 'UTF-8') : 'N/A';
                            $iconName = isset($link['icon_name']) ? htmlspecialchars($link['icon_name'], ENT_QUOTES, 'UTF-8') : 'N/A';
                            $status = isset($link['status']) ? ($link['status'] ? 'Active' : 'Inactive') : 'Inactive';

                            echo "<tr>
                                <td>{$id}</td>
                                <td>{$platform}</td>
                                <td>{$linkValue}</td>
                                <td>{$iconName}</td>
                                <td>{$status}</td>
                                <td>
                                    <a href='?page=social_links&action=edit&id={$id}' class='btn btn-primary'>Edit</a>
                                </td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <style>
            .table-container {
                margin-left: 50px;
                padding: 20px;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
                margin-top: 20px;
                border: 1px solid #e0e0e0;
            }

            .table-container h2 {
                font-size: 1.8em;
                margin-bottom: 15px;
                color: #2c3e50;
                font-weight: 600;
            }

            .table-container table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            .table-container th,
            .table-container td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #e0e0e0;
            }

            .table-container th {
                background-color: #f5f7fa;
                font-weight: 600;
                color: #2c3e50;
            }

            .table-container td {
                color: #34495e;
            }

            .table-container tr:hover {
                background-color: #f9f9f9;
            }

            .btn {
                display: inline-block;
                padding: 8px 16px;
                text-decoration: none;
                border-radius: 5px;
                font-weight: 500;
                transition: background-color 0.3s ease;
            }

            .btn-primary {
                background-color: #3498db;
                color: #fff;
                border: 1px solid #2980b9;
            }

            .btn-primary:hover {
                background-color: #2980b9;
            }
        </style>
        <?php
    }
}

$socialLinksManager = new SocialLinksManager($pdo);
$socialLinksManager->renderTable();