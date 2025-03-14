<!-- pages/dashboard/dashboard.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';

// Use the PDO connection from config.php
global $pdo;

// Fetch total users
$userQuery = "SELECT COUNT(*) as total_users FROM users";
$userStmt = $pdo->prepare($userQuery);
$userStmt->execute();
$users = $userStmt->fetchColumn() ?: 0;

// Fetch total orders
$orderQuery = "SELECT COUNT(*) as total_orders FROM orders";
$orderStmt = $pdo->prepare($orderQuery);
$orderStmt->execute();
$orders = $orderStmt->fetchColumn() ?: 0;

// Fetch total sales (assuming total_amount exists in orders table)
$salesQuery = "SELECT SUM(total_amount) as total_sales FROM orders";
$salesStmt = $pdo->prepare($salesQuery);
$salesStmt->execute();
$sales = $salesStmt->fetchColumn() ?: 0;

// Fallback to calculate sales from order_items if total_amount is not available
if ($sales === null || $sales == 0) {
    $salesQuery = "SELECT SUM(oi.quantity * oi.price) as total_sales 
                   FROM order_items oi 
                   JOIN orders o ON oi.order_id = o.id";
    $salesStmt = $pdo->prepare($salesQuery);
    $salesStmt->execute();
    $sales = $salesStmt->fetchColumn() ?: 0;
}

// For Visitors, hardcoded for now
$visitors = 3; // Replace with actual logic if you have a visitors table
?>

<div class="main-content">
    <h1 class="dashboard-title"><i class="bi bi-speedometer2"></i> Admin Dashboard</h1>
    <div class="dashboard-panel">
        <div class="dashboard-row">
            <div class="widget-card visitors">
                <div class="widget-icon"><i class="bi bi-eye"></i></div>
                <h3>Visitors</h3>
                <p class="widget-value"><?php echo $visitors; ?></p>
            </div>
            <div class="widget-card sales">
                <div class="widget-icon"><i class="bi bi-currency-dollar"></i></div>
                <h3>Sales</h3>
                <p class="widget-value">$<?php echo number_format($sales, 2); ?></p>
            </div>
            <div class="widget-card users">
                <div class="widget-icon"><i class="bi bi-people"></i></div>
                <h3>Users</h3>
                <p class="widget-value"><?php echo $users; ?></p>
            </div>
            <div class="widget-card orders">
                <div class="widget-icon"><i class="bi bi-cart-check"></i></div>
                <h3>Orders</h3>
                <p class="widget-value"><?php echo $orders; ?></p>
            </div>
        </div>
        
        <!-- Added Graph Section -->
        <div class="dashboard-graph">
            <canvas id="dashboardChart" style="max-height: 400px;"></canvas>
        </div>
    </div>
</div>

<!-- Include Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Chart.js configuration
    const ctx = document.getElementById('dashboardChart').getContext('2d');
    const dashboardChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Visitors', 'Sales ($)', 'Users', 'Orders'],
            datasets: [{
                label: 'Dashboard Metrics',
                data: [<?php echo $visitors; ?>, <?php echo $sales; ?>, <?php echo $users; ?>, <?php echo $orders; ?>],
                backgroundColor: [
                    '#f1c40f',
                    '#2ecc71',
                    '#e74c3c',
                    '#3498db'
                ],
                borderColor: [
                    '#d4ac0d',
                    '#27ae60',
                    '#c0392b',
                    '#2980b9'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Values'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Metrics'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Hide legend since we only have one dataset
                },
                title: {
                    display: true,
                    text: 'Dashboard Overview',
                    font: {
                        size: 18
                    }
                }
            }
        }
    });
</script>

<style>
    .main-content {
        margin-left: 50px;
        padding: 20px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .dashboard-title {
        font-size: 2.2em;
        margin: 20px 0;
        color: #2c3e50;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dashboard-panel {
        padding: 25px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
        border: 1px solid #e0e0e0;
    }

    .dashboard-row {
        display: flex;
        gap: 20px;
        justify-content: flex-start;
    }

    .widget-card {
        width: 225px;
        height: 100px;
        border-radius: 10px;
        padding: 15px;
        color: white;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.1), rgba(0, 0, 0, 0.1));
        border: 1px solid rgba(255, 255, 255, 0.2);
        flex-shrink: 0;
    }

    .widget-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .widget-icon {
        position: absolute;
        top: 10px;
        left: 10px;
        font-size: 1.5em;
        opacity: 0.4;
    }

    .widget-card h3 {
        font-size: 1.1em;
        margin: 8px 0 4px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255, 255, 255, 0.9);
    }

    .widget-value {
        font-size: 1.6em;
        font-weight: 700;
        margin: 0;
        color: #ffffff;
    }

    .visitors { background-color: #f1c40f; }
    .sales { background-color: #2ecc71; }
    .users { background-color: #e74c3c; }
    .orders { background-color: #3498db; }

    /* Added Graph Styles */
    .dashboard-graph {
        margin-top: 30px;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Media query to adjust on smaller screens */
    @media (max-width: 800px) {
        .widget-card {
            width: 150px;
        }
        .widget-card h3 {
            font-size: 1em;
        }
        .widget-value {
            font-size: 1.4em;
        }
        .dashboard-graph {
            padding: 10px;
        }
    }
</style>