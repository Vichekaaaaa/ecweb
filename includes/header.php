<?php
require_once 'db_config.php'; // Ensure this matches your database config file

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use full_name from session if available
$user_full_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : '';
?>
<header id="header" class="site-header text-black" style="position: relative; z-index: 1000;">
    <div class="header-top border-bottom py-2">
        <div class="container-lg">
            <div class="row justify-content-evenly">
                <div class="col">
                    <ul class="social-links list-unstyled d-flex m-0">
                        <?php
                        $stmt = $pdo->query("SELECT * FROM social_links WHERE status = 1");
                        while ($link = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <li class="pe-2">
                            <a href="<?= htmlspecialchars($link['link']); ?>" target="_blank">
                                <svg class="<?= htmlspecialchars($link['icon_name']); ?>" width="20" height="20">
                                    <use xlink:href="#<?= htmlspecialchars($link['icon_name']); ?>"></use>
                                </svg>
                            </a>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <?php  
                $stmt = $pdo->prepare("SELECT special_value FROM special_offer WHERE special_key = 'special_offer_title'");
                $stmt->execute();
                $special_offer_title = $stmt->fetchColumn();
                $stmt = $pdo->prepare("SELECT special_value FROM special_offer WHERE special_key = 'special_offer'");
                $stmt->execute();
                $special_offer = $stmt->fetchColumn();
                ?>
                <div class="col d-none d-md-block">
                    <p class="text-center text-black m-0">
                        <strong><?php echo htmlspecialchars($special_offer_title); ?></strong>: <?php echo htmlspecialchars($special_offer); ?>
                    </p>
                </div>
                <div class="col">
                    <ul class="d-flex justify-content-end gap-3 list-unstyled m-0">
                        <li><a href="../pages/contact.php">Contact US</a></li>
                        <li><a href="../pages/cart.php">Cart</a></li>
                        <li><a href="../pages/orders.php">Your Older</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <nav id="header-nav" class="navbar navbar-expand-lg">
        <div class="container-lg" style="position: relative; overflow: visible;">
            <?php
            $stmt = $pdo->prepare("SELECT logo_value FROM logo WHERE logo_key = 'logo_image' LIMIT 1");
            $stmt->execute();
            $logo = $stmt->fetchColumn();
            $logo_image = $logo ?: 'main-logo.png';
            echo "<!-- Debug: Frontend header navbar logo query returned: {$logo}, using: {$logo_image} -->";
            ?>
            <a class="navbar-brand" href="../index.php"><img src="../assets/images/logo/<?php echo htmlspecialchars($logo_image); ?>" class="logo" alt="logo"></a>
            <button class="navbar-toggler d-flex d-lg-none order-3 border-0 p-1 ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#bdNavbar" aria-controls="bdNavbar">
                <svg class="navbar-icon"><use xlink:href="#navbar-icon"></use></svg>
            </button>
            <div class="offcanvas offcanvas-end" tabindex="-1" id="bdNavbar">
                <div class="offcanvas-header px-4 pb-0">
                    <?php
                    $stmt = $pdo->prepare("SELECT logo_value FROM logo WHERE logo_key = 'logo_image' LIMIT 1");
                    $stmt->execute();
                    $logo = $stmt->fetchColumn();
                    $logo_image = $logo ?: 'main-logo.png';
                    echo "<!-- Debug: Frontend offcanvas logo query returned: {$logo}, using: {$logo_image} -->";
                    ?>
                    <a class="navbar-brand ps-3" href="../index.php"><img src="../assets/images/logo/<?php echo htmlspecialchars($logo_image); ?>" class="logo" alt="logo"></a>
                    <button type="button" class="btn-close btn-close-black p-5" data-bs-dismiss="offcanvas" aria-label="Close" data-bs-target="#bdNavbar"></button>
                </div>
                <div class="offcanvas-body">
                    <ul id="navbar" class="navbar-nav fw-bold justify-content-end align-items-center flex-grow-1">
                        <li class="nav-item"><a class="nav-link me-5" href="../index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link me-5" href="../pages/men.php">Men</a></li>
                        <li class="nav-item"><a class="nav-link me-5" href="../pages/women.php">Women</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link me-5 active dropdown-toggle border-0" href="#" data-bs-toggle="dropdown" aria-expanded="false">Page</a>
                            <ul class="dropdown-menu fw-bold">
                                <li><a href="../pages/about_us.php" class="dropdown-item">About Us</a></li>
                                <li><a class="dropdown-item" href="../pages/shop.php">Shop</a></li>
                                <li><a class="dropdown-item" href="../pages/cart.php">Cart</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link me-5" href="../pages/shop.php">Shop</a></li>
                        <li class="nav-item"><a class="nav-link me-5" href="../pages/sale.php">Sale</a></li>
                    </ul>
                </div>
            </div>
            <div class="user-items ps-0 ps-md-5" style="position: relative;">
                <ul class="d-flex justify-content-end list-unstyled align-items-center m-0">
                    <!-- Profile Icon with Dropdown -->
                    <li class="pe-3 dropdown">
                        <a href="#" class="border-0 dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" data-bs-popper-config='{"placement":"bottom-end"}' aria-expanded="false">
                            <svg class="user" width="24" height="24"><use xlink:href="#user"></use></svg>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end fw-bold" style="position: absolute;" aria-labelledby="profileDropdown">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <!-- User is logged in -->
                                <li><a href="../pages/profile.php" class="dropdown-item">Your Profile (<?php echo $user_full_name ?: 'User'; ?>)</a></li>
                                <li><a href="../users/logout.php" class="dropdown-item">Logout</a></li>
                            <?php else: ?>
                                <!-- User is not logged in -->
                                <li><a href="../users/login.php" class="dropdown-item">Login</a></li>
                                <li><a href="../users/register.php" class="dropdown-item">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <!-- Cart Icon -->
                    <li class="pe-3">
                        <a href="../pages/cart.php" class="border-0 position-relative">
                            <svg class="shopping-cart" width="24" height="24"><use xlink:href="#shopping-cart"></use></svg>
                            <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
                        </a>
                    </li>
                    <!-- Search Icon -->
                    <li>
                        <a href="#" class="search-item border-0" data-bs-toggle="collapse" data-bs-target="#search-box" aria-expanded="false" aria-controls="search-box">
                            <svg class="search" width="24" height="24"><use xlink:href="#search"></use></svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <!-- Search Box (Collapsible) -->
<div class="collapse position-absolute w-100" id="search-box" style="top: 100%; left: 0; z-index: 1000;">
    <div class="container-lg py-2 bg-white border shadow-sm">
        <form id="search-form" action="/pages/search.php" method="GET" class="d-flex align-items-center">
            <input type="text" name="query" class="form-control me-2" placeholder="Search product names..." required>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
</div>
    </nav>
</header>

<script>
    // Function to update cart count
    function updateCartCount() {
        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        let totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        let cartCount = document.getElementById('cart-count');
        if (totalItems > 0) {
            cartCount.textContent = totalItems;
            cartCount.style.display = 'block';
        } else {
            cartCount.style.display = 'none';
        }
    }

    // Update cart count on page load
    document.addEventListener('DOMContentLoaded', updateCartCount);

    // Listen for storage changes (e.g., when cart is updated from another page)
    window.addEventListener('storage', updateCartCount);

    // Custom event listener for same-page updates
    window.addEventListener('cartUpdated', updateCartCount);

    // Handle search form submission (optional client-side validation)
    document.getElementById('search-form')?.addEventListener('submit', function(e) {
        const query = this.querySelector('input[name="query"]').value.trim();
        if (!query) {
            e.preventDefault();
            alert('Please enter a product name to search.');
        }
    });
</script>

<style>
    .badge {
        font-size: 0.7rem;
        padding: 0.25em 0.5em;
    }

    /* Ensure dropdown opens below the profile icon */
    .user-items .dropdown-menu {
        top: 100% !important;
        transform: translateY(0) !important;
        margin-top: 5px;
        left: auto !important;
        right: 0 !important; /* Align to the right edge of the trigger */
    }

    /* Ensure parent containers allow dropdown visibility */
    .site-header, .navbar, .container-lg {
        overflow: visible !important;
    }

    /* Style for the search box */
    #search-box {
        background-color: #fff;
    }
    #search-box .form-control {
        border-radius: 0.25rem;
    }
    #search-box .btn-primary {
        border-radius: 0.25rem;
    }
</style>