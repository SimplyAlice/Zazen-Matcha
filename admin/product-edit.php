<?php
/**
 * Administrator Edit Product Page (Phase 3: Update Existing Product)
 * Zazen Matcha & Tea Co.
 *
 * Protected administration page that loads an existing product by ?id=<product_id>,
 * populates the edit form with current database values and dynamic categories,
 * validates submitted changes server-side with CSRF protection, updates the
 * `products` record via a prepared PDO UPDATE statement, and redirects via PRG.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$productsListUrl = site_url('admin/products.php');

// ----------------------------------------------------------------------------
// 1. Validate Product ID from $_GET['id'] (Positive Integer Only)
// ----------------------------------------------------------------------------
$rawProductId = $_GET['id'] ?? '';
$productId    = (
    !is_array($rawProductId)
    && ctype_digit(trim((string) $rawProductId))
    && (int) trim((string) $rawProductId) > 0
)
    ? (int) trim((string) $rawProductId)
    : 0;

$existingProduct  = null;
$productFound     = false;
$categories       = [];
$validCategoryMap = [];
$dbLoadError      = null;

// ----------------------------------------------------------------------------
// 2. Load Categories & Existing Product from MySQL
// ----------------------------------------------------------------------------
try {
    $categoriesSql = "
        SELECT
            category_id,
            name,
            description
        FROM categories
        ORDER BY category_id ASC
    ";
    $categories = db_fetch_all($pdo, $categoriesSql);

    foreach ($categories as $catRow) {
        $catId = (int) ($catRow['category_id'] ?? 0);
        if ($catId > 0) {
            $validCategoryMap[$catId] = trim((string) ($catRow['name'] ?? ''));
        }
    }

    if ($productId > 0) {
        $productSql = "
            SELECT
                product_id,
                category_id,
                name,
                description,
                price,
                stock_quantity,
                image,
                status
            FROM products
            WHERE product_id = :product_id
            LIMIT 1
        ";

        $existingProduct = db_fetch_one($pdo, $productSql, [
            'product_id' => $productId,
        ]);

        if ($existingProduct !== null) {
            $productFound = true;
        }
    }
} catch (Throwable $exception) {
    error_log('Admin Edit Product Load Error: ' . $exception->getMessage());
    $dbLoadError = 'Unable to load product details right now. Please check the database connection.';
}

// ----------------------------------------------------------------------------
// 3. Populate Initial Form State from Existing Product
// ----------------------------------------------------------------------------
$formData = [
    'name'           => '',
    'category_id'    => '',
    'description'    => '',
    'price'          => '',
    'stock_quantity' => '0',
    'image'          => '',
    'status'         => 'active',
];

if ($productFound && $existingProduct !== null) {
    $formData = [
        'name'           => (string) ($existingProduct['name'] ?? ''),
        'category_id'    => (string) ((int) ($existingProduct['category_id'] ?? 0)),
        'description'    => (string) ($existingProduct['description'] ?? ''),
        'price'          => number_format((float) ($existingProduct['price'] ?? 0.00), 2, '.', ''),
        'stock_quantity' => (string) ((int) ($existingProduct['stock_quantity'] ?? 0)),
        'image'          => (string) ($existingProduct['image'] ?? ''),
        'status'         => strtolower(trim((string) ($existingProduct['status'] ?? 'active'))),
    ];
}

$formErrors    = [];
$allowedStatus = ['active', 'inactive'];
$productEditUrl = site_url('admin/product-edit.php?id=' . $productId);

// ----------------------------------------------------------------------------
// 4. Handle POST Update Submission (Only When Product Exists)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $productFound) {
    $formData['name']           = clean_input(isset($_POST['name']) && !is_array($_POST['name']) ? (string) $_POST['name'] : '');
    $formData['category_id']    = clean_input(isset($_POST['category_id']) && !is_array($_POST['category_id']) ? (string) $_POST['category_id'] : '');
    $formData['description']    = clean_input(isset($_POST['description']) && !is_array($_POST['description']) ? (string) $_POST['description'] : '');
    $formData['price']          = clean_input(isset($_POST['price']) && !is_array($_POST['price']) ? (string) $_POST['price'] : '');
    $formData['stock_quantity'] = clean_input(isset($_POST['stock_quantity']) && !is_array($_POST['stock_quantity']) ? (string) $_POST['stock_quantity'] : '');
    $formData['image']          = clean_input(isset($_POST['image']) && !is_array($_POST['image']) ? (string) $_POST['image'] : '');
    $formData['status']         = strtolower(clean_input(isset($_POST['status']) && !is_array($_POST['status']) ? (string) $_POST['status'] : ''));

    // 4a. Verify CSRF Token
    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    $csrfValid = verify_csrf_token($submittedToken);
    if (!$csrfValid) {
        $formErrors['general'] = 'Invalid security token. Please review your changes and try submitting again.';
    }

    // 4b. Validate Product Name (Required, <= 150 chars)
    if ($formData['name'] === '') {
        $formErrors['name'] = 'Product name is required.';
    } elseif (mb_strlen($formData['name']) > 150) {
        $formErrors['name'] = 'Product name must not exceed 150 characters.';
    }

    // 4c. Validate Category (Required, must exist in `categories` table)
    $selectedCategoryId = 0;
    if ($formData['category_id'] === '') {
        $formErrors['category_id'] = 'Please select a product category.';
    } elseif (
        !ctype_digit($formData['category_id'])
        || (int) $formData['category_id'] <= 0
        || !isset($validCategoryMap[(int) $formData['category_id']])
    ) {
        $formErrors['category_id'] = 'Please select a valid existing product category.';
    } else {
        $selectedCategoryId = (int) $formData['category_id'];
    }

    // 4d. Validate Description (Required, <= 5000 chars)
    if ($formData['description'] === '') {
        $formErrors['description'] = 'Product description is required.';
    } elseif (mb_strlen($formData['description']) > 5000) {
        $formErrors['description'] = 'Product description must not exceed 5,000 characters.';
    }

    // 4e. Validate Price (Required, numeric, > 0, <= 99999999.99)
    $validatedPrice = 0.00;
    if ($formData['price'] === '') {
        $formErrors['price'] = 'Product price is required.';
    } elseif (!is_numeric($formData['price'])) {
        $formErrors['price'] = 'Price must be a valid numeric amount.';
    } else {
        $parsedPrice = (float) $formData['price'];
        if ($parsedPrice <= 0) {
            $formErrors['price'] = 'Price must be greater than 0.';
        } elseif ($parsedPrice > 99999999.99) {
            $formErrors['price'] = 'Price exceeds the maximum allowed amount.';
        } else {
            $validatedPrice = round($parsedPrice, 2);
        }
    }

    // 4f. Validate Stock Quantity (Required, integer >= 0; 0 is explicitly valid)
    $validatedStock = 0;
    if ($formData['stock_quantity'] === '') {
        $formErrors['stock_quantity'] = 'Stock quantity is required.';
    } else {
        $filteredStock = filter_var($formData['stock_quantity'], FILTER_VALIDATE_INT);
        if ($filteredStock === false || $filteredStock < 0) {
            $formErrors['stock_quantity'] = 'Stock quantity must be a whole number of 0 or greater.';
        } elseif ($filteredStock > 1000000) {
            $formErrors['stock_quantity'] = 'Stock quantity exceeds the maximum allowed limit.';
        } else {
            $validatedStock = (int) $filteredStock;
        }
    }

    // 4g. Validate Image Filename / Path (Optional; follows images/products/<filename> convention)
    $validatedImage = null;
    if ($formData['image'] !== '') {
        if (mb_strlen($formData['image']) > 255) {
            $formErrors['image'] = 'Image filename must not exceed 255 characters.';
        } else {
            $normalizedPath = str_replace('\\', '/', $formData['image']);
            $imageBasename  = basename($normalizedPath);

            if (
                $imageBasename === ''
                || $imageBasename === '.'
                || $imageBasename === '..'
                || !preg_match('/^[A-Za-z0-9._-]+\.(jpg|jpeg|png|webp|gif|svg)$/i', $imageBasename)
            ) {
                $formErrors['image'] = 'Please enter a valid image filename (for example: ceremonial-matcha.jpg).';
            } else {
                $validatedImage    = $imageBasename;
                $formData['image'] = $imageBasename;
            }
        }
    }

    // 4h. Validate Status (Strictly 'active' or 'inactive')
    if ($formData['status'] === '') {
        $formErrors['status'] = 'Please select a product status.';
    } elseif (!in_array($formData['status'], $allowedStatus, true)) {
        $formErrors['status'] = 'Status must be either Active or Inactive.';
    }

    // ------------------------------------------------------------------------
    // 5. Update Existing Product in MySQL & Redirect (PRG Pattern)
    // ------------------------------------------------------------------------
    if ($csrfValid && empty($formErrors) && $dbLoadError === null) {
        try {
            $updateSql = "
                UPDATE products
                SET
                    category_id    = :category_id,
                    name           = :name,
                    description    = :description,
                    price          = :price,
                    stock_quantity = :stock_quantity,
                    image          = :image,
                    status         = :status
                WHERE product_id = :product_id
            ";

            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([
                'category_id'    => $selectedCategoryId,
                'name'           => $formData['name'],
                'description'    => $formData['description'],
                'price'          => number_format($validatedPrice, 2, '.', ''),
                'stock_quantity' => $validatedStock,
                'image'          => $validatedImage,
                'status'         => $formData['status'],
                'product_id'     => $productId,
            ]);

            set_flash_message(
                'success',
                'Product "' . $formData['name'] . '" has been updated successfully.'
            );

            redirect($productsListUrl);
        } catch (Throwable $exception) {
            error_log('Admin Edit Product Update Error: ' . $exception->getMessage());
            $formErrors['general'] = 'We could not save your changes right now. Please try again shortly.';
        }
    }
}

$pageTitle = $productFound ? 'Edit Product #' . $productId : 'Product Not Found';

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<!-- 1. Page Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-edit-product-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Catalogue Administration</span>
                <h1 id="admin-edit-product-heading" class="checkout-title">
                    <?php echo $productFound ? 'Edit Product' : 'Product Not Found'; ?>
                </h1>
                <p class="checkout-subtitle">
                    <?php if ($productFound): ?>
                        Editing catalogue record <strong>#<?php echo (int) $productId; ?></strong> &mdash; update product details, pricing in ZAR, stock availability, or storefront status.
                    <?php else: ?>
                        The requested product record could not be located in the Zazen Matcha catalogue.
                    <?php endif; ?>
                </p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?php echo e($productsListUrl); ?>" class="btn btn-outline">
                    &larr; Back to Products
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Main Edit Product Content Section -->
<section class="section admin-dashboard-section admin-form-section" aria-label="Edit product form">
    <div class="container">
        <?php if ($dbLoadError !== null): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Database Notice</p>
                <p><?php echo e($dbLoadError); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$productFound): ?>
            <!-- Clean Product Not Found State -->
            <div class="checkout-card admin-panel-card">
                <div class="admin-empty-state" role="status">
                    <p class="admin-empty-title">Product Not Found</p>
                    <p class="admin-empty-text">
                        The product ID provided is missing, invalid, or does not match an existing product in the database.
                    </p>
                    <div class="admin-empty-actions">
                        <a href="<?php echo e($productsListUrl); ?>" class="btn btn-primary">
                            Return to Product Listing
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="admin-dashboard-grid admin-form-layout">
                <!-- Left / Main Column: Edit Product Form -->
                <div class="checkout-card admin-panel-card">
                    <div class="admin-panel-header">
                        <div>
                            <span class="eyebrow">01 / Catalogue Record #<?php echo (int) $productId; ?></span>
                            <h2 class="checkout-section-heading">Update Product Details</h2>
                        </div>
                    </div>

                    <?php if (!empty($formErrors)): ?>
                        <div class="cart-warnings auth-form-alert" role="alert">
                            <p class="cart-warnings-title">Please correct the highlighted fields before saving:</p>
                            <?php if (!empty($formErrors['general'])): ?>
                                <p><?php echo e($formErrors['general']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo e($productEditUrl); ?>" method="post" class="admin-product-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                        <!-- Product Name -->
                        <div class="form-group">
                            <label for="name" class="form-label">Product Name *</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                maxlength="150"
                                placeholder="e.g. Ceremonial Matcha"
                                value="<?php echo e($formData['name']); ?>"
                                required
                                <?php echo isset($formErrors['name']) ? 'aria-invalid="true" aria-describedby="error-name"' : ''; ?>
                            >
                            <?php if (isset($formErrors['name'])): ?>
                                <span id="error-name" class="form-error"><?php echo e($formErrors['name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Category & Status Row -->
                        <div class="checkout-field-grid">
                            <div class="form-group">
                                <label for="category_id" class="form-label">Category *</label>
                                <select
                                    id="category_id"
                                    name="category_id"
                                    required
                                    <?php echo isset($formErrors['category_id']) ? 'aria-invalid="true" aria-describedby="error-category"' : ''; ?>
                                >
                                    <option value="">Select a category...</option>
                                    <?php foreach ($categories as $category): ?>
                                        <?php
                                        $optionCatId   = (int) ($category['category_id'] ?? 0);
                                        $optionCatName = trim((string) ($category['name'] ?? ''));
                                        $isSelected    = ($formData['category_id'] !== '' && ctype_digit($formData['category_id']) && (int) $formData['category_id'] === $optionCatId);
                                        ?>
                                        <option
                                            value="<?php echo $optionCatId; ?>"
                                            <?php echo $isSelected ? 'selected' : ''; ?>
                                        >
                                            <?php echo e($optionCatName); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($formErrors['category_id'])): ?>
                                    <span id="error-category" class="form-error"><?php echo e($formErrors['category_id']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="status" class="form-label">Status *</label>
                                <select
                                    id="status"
                                    name="status"
                                    required
                                    <?php echo isset($formErrors['status']) ? 'aria-invalid="true" aria-describedby="error-status"' : ''; ?>
                                >
                                    <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>
                                        Active (Visible in Storefront)
                                    </option>
                                    <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>
                                        Inactive (Hidden from Storefront)
                                    </option>
                                </select>
                                <?php if (isset($formErrors['status'])): ?>
                                    <span id="error-status" class="form-error"><?php echo e($formErrors['status']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Price & Stock Quantity Row -->
                        <div class="checkout-field-grid">
                            <div class="form-group">
                                <label for="price" class="form-label">Price (ZAR) *</label>
                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="280.00"
                                    value="<?php echo e($formData['price']); ?>"
                                    required
                                    <?php echo isset($formErrors['price']) ? 'aria-invalid="true" aria-describedby="error-price"' : ''; ?>
                                >
                                <?php if (isset($formErrors['price'])): ?>
                                    <span id="error-price" class="form-error"><?php echo e($formErrors['price']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                <input
                                    type="number"
                                    id="stock_quantity"
                                    name="stock_quantity"
                                    step="1"
                                    min="0"
                                    placeholder="0"
                                    value="<?php echo e($formData['stock_quantity']); ?>"
                                    required
                                    <?php echo isset($formErrors['stock_quantity']) ? 'aria-invalid="true" aria-describedby="error-stock"' : ''; ?>
                                >
                                <?php if (isset($formErrors['stock_quantity'])): ?>
                                    <span id="error-stock" class="form-error"><?php echo e($formErrors['stock_quantity']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Image Filename / Path -->
                        <div class="form-group">
                            <label for="image" class="form-label">Image Filename / Path</label>
                            <input
                                type="text"
                                id="image"
                                name="image"
                                maxlength="255"
                                placeholder="e.g. ceremonial-matcha.jpg (or leave blank for placeholder)"
                                value="<?php echo e($formData['image']); ?>"
                                <?php echo isset($formErrors['image']) ? 'aria-invalid="true" aria-describedby="error-image"' : ''; ?>
                            >
                            <p class="account-section-note">
                                Enter a filename located in <code>images/products/</code> (such as <code>ceremonial-matcha.jpg</code> or <code>placeholder.jpg</code>), or leave blank if no image is assigned.
                            </p>
                            <?php if (isset($formErrors['image'])): ?>
                                <span id="error-image" class="form-error"><?php echo e($formErrors['image']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="description" class="form-label">Description *</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                maxlength="5000"
                                placeholder="Describe the origin, preparation notes, or vessel dimensions..."
                                required
                                <?php echo isset($formErrors['description']) ? 'aria-invalid="true" aria-describedby="error-description"' : ''; ?>
                            ><?php echo e($formData['description']); ?></textarea>
                            <?php if (isset($formErrors['description'])): ?>
                                <span id="error-description" class="form-error"><?php echo e($formErrors['description']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Form Actions -->
                        <div class="contact-form-actions">
                            <a href="<?php echo e($productsListUrl); ?>" class="btn btn-outline">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary contact-submit-btn">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Record Summary & Guidelines Panel -->
                <aside class="auth-editorial-card admin-quicklinks-card" aria-labelledby="edit-guide-heading">
                    <span class="eyebrow">02 / Record Summary</span>
                    <h2 id="edit-guide-heading" class="auth-editorial-title">
                        Product #<?php echo (int) $productId; ?>
                    </h2>
                    <p class="auth-editorial-intro">
                        Changes saved here update the live MySQL product catalogue immediately.
                    </p>

                    <dl class="auth-benefit-list">
                        <div class="auth-benefit-item">
                            <dt>Immutable Product ID</dt>
                            <dd>Record <code>#<?php echo (int) $productId; ?></code> remains linked to any existing customer order history.</dd>
                        </div>
                        <div class="auth-benefit-item">
                            <dt>Pricing &amp; Stock</dt>
                            <dd>Price must be greater than <code>0.00</code> ZAR. Stock quantity accepts any whole number of <code>0</code> or greater.</dd>
                        </div>
                        <div class="auth-benefit-item">
                            <dt>Visibility Control</dt>
                            <dd>Setting status to <strong>Inactive</strong> hides the product from customer browsing while preserving its catalogue record.</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
