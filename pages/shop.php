<?php
include_once '../includes/db_config.php';
include_once '../includes/header.php';
include_once '../includes/preloader.php';
include_once '../includes/svg_symbols.php';
include_once '../includes/modals.php';
$stmt = $pdo->prepare("SELECT logo_value FROM logo WHERE logo_key = 'favicon' LIMIT 1");
$stmt->execute();
$favicon = $stmt->fetchColumn() ?: 'favicon.png'; // Default to favicon.png if not found

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Hide the preloader when the page has fully loaded
        document.querySelector(".preloader").style.display = "none";
    });
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Shop - KaSim Store</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="icon" href="assets/images/logo/<?php echo htmlspecialchars($favicon); ?>" type="image/png">
    <link rel="stylesheet" href="../assets/css/vendor.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,900;1,900&family=Source+Sans+Pro:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        .notification { 
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 10000;
            display: none;
            text-align: center;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .notification.show { display: block; }
        .notification.error { background-color: #dc3545; }
        .notification a {
            color: #fff;
            text-decoration: underline;
            font-weight: bold;
        }
        .notification a:hover {
            color: #f8f9fa;
        }
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: bold;
            text-transform: uppercase;
            z-index: 1;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            display: inline-block;
        }
        @media (max-width: 768px) {
            .product-badge {
                font-size: 0.8em;
                padding: 4px 8px;
            }
            .notification {
                width: 80%;
            }
        }
    </style>
</head>
<body>
    <div class="notification" id="notification"></div>

    <section class="product-store">
        <div class="container-md">
            <div class="display-header d-flex align-items-center justify-content-between">
                <h2 class="section-title text-uppercase">Shop All Products</h2>
                <div class="category-filter">
                    <select id="categoryFilter" name="category" onchange="filterCategory()">
                        <option value="">All Categories</option>
                        <?php
                        try {
                            $categoryQuery = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category";
                            $categoryStmt = $pdo->query($categoryQuery);
                            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($categories as $cat) {
                                $selected = (isset($_GET['category']) && $_GET['category'] === $cat['category']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($cat['category']) . '" ' . $selected . '>' . htmlspecialchars($cat['category']) . '</option>';
                            }
                        } catch (PDOException $e) {
                            echo '<p class="text-danger">Error fetching categories: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="product-content padding-small">
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5" id="productList">
                    <?php
                    try {
                        $query = "SELECT id, image, title, price, discount, category, sizes, colors, description FROM products";
                        $params = [];
                        if (isset($_GET['category']) && !empty($_GET['category'])) {
                            $query .= " WHERE category = :category";
                            $params[':category'] = $_GET['category'];
                        }
                        if (isset($_GET['discount']) && $_GET['discount'] == 1) {
                            $query .= (isset($_GET['category']) ? " AND" : " WHERE") . " discount > 0";
                        }
                        $stmt = $pdo->prepare($query);
                        $stmt->execute($params);
                        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($products)) {
                            echo '<div class="no-products">No products found in this category.</div>';
                        } else {
                            foreach ($products as $index => $product) {
                                $originalPrice = $product['price'];
                                $discount = $product['discount'] ?: 0;
                                $discountedPrice = $originalPrice * (1 - $discount / 100);
                                echo '<div class="col mb-4 product-item" data-index="' . $index . '" data-id="' . $product['id'] . '">';
                                echo '<div class="product-card position-relative">';
                                // Add badge with dynamic discount percentage
                                if ($discount > 0) {
                                    echo '<div class="product-badge">' . number_format($discount, 0) . '% OFF</div>';
                                }
                                echo '<div class="card-img">';
                                echo '<img src="../assets/images/products/' . htmlspecialchars($product['image']) . '" alt="' . htmlspecialchars($product['title']) . '" class="product-image img-fluid">';
                                echo '<div class="cart-concern position-absolute d-flex justify-content-center">';
                                echo '<div class="cart-button d-flex gap-2 justify-content-center align-items-center">';
                                echo '<button type="button" class="btn btn-light add-to-cart" data-index="' . $index . '" data-bs-toggle="modal" data-bs-target="#productModalSizeColor">';
                                echo '<svg class="shopping-carriage"><use xlink:href="#shopping-carriage"></use></svg>';
                                echo '</button>';
                                echo '<button type="button" class="btn btn-light quick-view" data-bs-toggle="modal" data-bs-target="#productModalSizeColor" data-index="' . $index . '">';
                                echo '<svg class="quick-view"><use xlink:href="#quick-view"></use></svg>';
                                echo '</button>';
                                echo '</div></div></div>';
                                echo '<div class="card-detail d-flex justify-content-between align-items-center mt-3">';
                                echo '<h3 class="card-title fs-6 fw-normal m-0"><a href="shop.php?id=' . $product['id'] . '">' . htmlspecialchars($product['title']) . '</a></h3>';
                                echo '<span class="price">';
                                if ($discount > 0) {
                                    echo '<span class="original-price">$' . number_format($originalPrice, 2) . '</span>';
                                    echo '<span class="discounted-price">$' . number_format($discountedPrice, 2) . '</span>';
                                } else {
                                    echo '<span class="card-price fw-bold">$' . number_format($originalPrice, 2) . '</span>';
                                }
                                echo '</span>';
                                echo '</div>';
                                echo '<p class="category-text">Category: ' . htmlspecialchars($product['category']) . '</p>';
                                echo '</div></div>';
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<p class="text-danger">Error fetching products: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="productModalSizeColor" tabindex="-1" aria-labelledby="productModalSizeColorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-image">
                        <img src="" alt="Product Image" id="modalImageSizeColor">
                    </div>
                    <div class="modal-details">
                        <h4 class="modal-title" id="modalTitleSizeColor"></h4>
                        <p class="modal-price" id="modalPriceSizeColor"></p>
                        <p class="modal-description" id="modalDescriptionSizeColor"></p>
                        <div class="selection-bar">
                            <select id="modalColorSizeColor" class="form-select" required>
                                <option value="">Select Color</option>
                            </select>
                            <select id="modalSizeSizeColor" class="form-select" required>
                                <option value="">Select Size</option>
                            </select>
                        </div>
                        <div class="quantity-controls">
                            <button type="button" class="btn btn-light" id="decreaseQtySizeColor">-</button>
                            <input type="text" id="modalQuantitySizeColor" value="1" readonly class="form-control text-center">
                            <button type="button" class="btn btn-light" id="increaseQtySizeColor">+</button>
                        </div>
                        <button type="button" class="modal-add-to-cart mt-3" id="addToCartModalSizeColor">Add to Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Add login check function
        function isUserLoggedIn() {
            <?php
            echo 'return ' . (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? 'true' : 'false') . ';';
            ?>
        }

        function filterCategory() {
            const category = document.getElementById('categoryFilter').value;
            const url = new URL(window.location.href);
            url.searchParams.set('category', category);
            window.location.href = url.toString();
        }

        const products = <?php echo json_encode($products); ?>;

        document.querySelectorAll('.add-to-cart, .quick-view').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                const product = products[index];

                document.getElementById('modalImageSizeColor').src = '../assets/images/products/' + product.image;
                document.getElementById('modalImageSizeColor').style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                document.getElementById('modalTitleSizeColor').textContent = product.title;
                document.getElementById('modalDescriptionSizeColor').textContent = product.description || 'No description available.';
                const originalPrice = product.price;
                const discount = product.discount || 0;
                const discountedPrice = originalPrice * (1 - discount / 100);
                document.getElementById('modalPriceSizeColor').innerHTML = discount > 0
                    ? `<span class="original-price">$${number_format(originalPrice, 2)}</span> <span class="discounted-price">$${number_format(discountedPrice, 2)}</span>`
                    : `<span class="card-price fw-bold">$${number_format(originalPrice, 2)}</span>`;

                const colorSelect = document.getElementById('modalColorSizeColor');
                const sizeSelect = document.getElementById('modalSizeSizeColor');
                colorSelect.innerHTML = '<option value="">Select Color</option>';
                sizeSelect.innerHTML = '<option value="">Select Size</option>';

                const colors = product.colors ? product.colors.split(',').map(c => c.trim()) : ['Red'];
                const sizes = product.sizes ? product.sizes.split(',').map(s => s.trim()) : ['US 7'];
                colors.forEach(color => {
                    const option = document.createElement('option');
                    option.value = color;
                    option.textContent = color.charAt(0).toUpperCase() + color.slice(1);
                    colorSelect.appendChild(option);
                });
                sizes.forEach(size => {
                    const option = document.createElement('option');
                    option.value = size;
                    option.textContent = size;
                    sizeSelect.appendChild(option);
                });

                document.getElementById('addToCartModalSizeColor').onclick = function() {
                    // Show SweetAlert if the user is not logged in
                    if (!isUserLoggedIn()) {
                        Swal.fire({
                            title: 'Login Required',
                            text: 'Please login or create an account to continue.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Login',
                            cancelButtonText: 'Register',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '/users/login.php?returnUrl=' + encodeURIComponent(window.location.href);
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                window.location.href = '/users/register.php';
                            }
                        });
                        return;
                    }

                    const selectedColor = document.getElementById('modalColorSizeColor').value;
                    const selectedSize = document.getElementById('modalSizeSizeColor').value;
                    const quantity = parseInt(document.getElementById('modalQuantitySizeColor').value);

                    if (!selectedColor || !selectedSize) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Please select both size and color before adding to cart!',
                            icon: 'error',
                            confirmButtonText: 'Ok'
                        });
                        return;
                    }

                    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
                    let existingItem = cart.find(x => 
                        x.id === product.id && 
                        x.size === selectedSize && 
                        x.color === selectedColor
                    );
                    let priceToAdd = discount > 0 ? discountedPrice : originalPrice;

                    if (existingItem) {
                        existingItem.quantity += quantity;
                        existingItem.price = priceToAdd;
                    } else {
                        cart.push({
                            id: product.id,
                            image: product.image,
                            title: product.title,
                            price: priceToAdd,
                            quantity: quantity,
                            discount: discount,
                            category: product.category || '',
                            color: selectedColor,
                            size: selectedSize
                        });
                    }
                    localStorage.setItem('cart', JSON.stringify(cart));

                    // Success SweetAlert
                    Swal.fire({
                        title: 'Added to Cart!',
                        text: product.title + ' has been added to your cart.',
                        icon: 'success',
                        confirmButtonText: 'Ok'
                    });

                    window.dispatchEvent(new Event('cartUpdated')); // Notify header of cart update

                    bootstrap.Modal.getInstance(document.getElementById('productModalSizeColor')).hide();
                };

                document.getElementById('increaseQtySizeColor').onclick = function() {
                    let qty = parseInt(document.getElementById('modalQuantitySizeColor').value);
                    document.getElementById('modalQuantitySizeColor').value = qty + 1;
                };

                document.getElementById('decreaseQtySizeColor').onclick = function() {
                    let qty = parseInt(document.getElementById('modalQuantitySizeColor').value);
                    if (qty > 1) document.getElementById('modalQuantitySizeColor').value = qty - 1;
                };
            });
        });

        function number_format(number, decimals, dec_point = '.', thousands_sep = ',') {
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            let n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = thousands_sep,
                dec = dec_point,
                s = '',
                toFixedFix = (n, prec) => '' + Math.round(n * Math.pow(10, prec)) / Math.pow(10, prec);
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }
    </script>

</body>
</html>
