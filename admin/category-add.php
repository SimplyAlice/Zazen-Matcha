<?php
/**
 * Administrator Add Category Page
 * Zazen Matcha & Tea Co.
 *
 * Protected administration form and POST handler for inserting a new category
 * into the `categories` table using PDO prepared statements, CSRF protection,
 * duplicate-name checking, server-side validation, and PRG redirect.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$categoriesListUrl = site_url('admin/categories.php');
$categoryAddUrl    = site_url('admin/category-add.php');

// ----------------------------------------------------------------------------
// 1. Initialise Form State
// ----------------------------------------------------------------------------
$formData = [
    'name'        => '',
    'description' => '',
];

$formErrors = [];

// ----------------------------------------------------------------------------
// 2. Handle POST Submission
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name']        = trim(isset($_POST['name']) && !is_array($_POST['name']) ? (string) $_POST['name'] : '');
    $formData['description'] = trim(isset($_POST['description']) && !is_array($_POST['description']) ? (string) $_POST['description'] : '');

    // 2a. Verify CSRF Token
    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    $csrfValid = verify_csrf_token($submittedToken);
    if (!$csrfValid) {
        $formErrors['general'] = 'Invalid security token. Please review the form and try submitting again.';
    }

    // 2b. Validate Category Name (Required, VARCHAR(100) in `categories` schema)
    if ($formData['name'] === '') {
        $formErrors['name'] = 'Category name is required.';
    } elseif (mb_strlen($formData['name']) > 100) {
        $formErrors['name'] = 'Category name must not exceed 100 characters.';
    } else {
        // 2c. Check for Duplicate Category Name via Prepared PDO Query
        try {
            $duplicateSql = "
                SELECT
                    category_id,
                    name
                FROM categories
                WHERE LOWER(name) = LOWER(:name)
                LIMIT 1
            ";

            $existingCategory = db_fetch_one($pdo, $duplicateSql, [
                'name' => $formData['name'],
            ]);

            if ($existingCategory !== null) {
                $formErrors['name'] = 'A category with this name already exists. Please enter a unique category name.';
            }
        } catch (Throwable $exception) {
            error_log('Admin Add Category Duplicate Check Error: ' . $exception->getMessage());
            $formErrors['general'] = 'Unable to verify category uniqueness right now. Please try again shortly.';
        }
    }

    // 2d. Validate Optional Description (Nullable TEXT in `categories` schema; cap at 5,000 characters)
    $validatedDescription = null;
    if ($formData['description'] !== '') {
        if (mb_strlen($formData['description']) > 5000) {
            $formErrors['description'] = 'Category description must not exceed 5,000 characters.';
        } else {
            $validatedDescription = $formData['description'];
        }
    }

    // ------------------------------------------------------------------------
    // 3. Insert Validated Category into MySQL & Redirect (PRG Pattern)
    // ------------------------------------------------------------------------
    if ($csrfValid && empty($formErrors)) {
        try {
            $insertSql = "
                INSERT INTO categories (
                    name,
                    description
                ) VALUES (
                    :name,
                    :description
                )
            ";

            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                'name'        => $formData['name'],
                'description' => $validatedDescription,
            ]);

            set_flash_message(
                'success',
                'Category "' . $formData['name'] . '" has been added to the catalogue.'
            );

            redirect($categoriesListUrl);
        } catch (Throwable $exception) {
            error_log('Admin Add Category Insert Error: ' . $exception->getMessage());
            $formErrors['general'] = 'We could not save the category right now. Please try again shortly.';
        }
    }
}

$pageTitle = 'Add Category';

// Ensure Categories is highlighted as the active navigation item in the shared header
$originalPhpSelf     = $_SERVER['PHP_SELF'] ?? '';
$_SERVER['PHP_SELF'] = 'categories.php';

require_once dirname(__DIR__) . '/includes/admin-header.php';

$_SERVER['PHP_SELF'] = $originalPhpSelf;
?>

<!-- 1. Page Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-add-category-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Catalogue Administration</span>
                <h1 id="admin-add-category-heading" class="checkout-title">Add Category</h1>
                <p class="checkout-subtitle">
                    Create a new product category to organise ceremonial matcha powders, preparation vessels, and gift sets.
                </p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?php echo e($categoriesListUrl); ?>" class="btn btn-outline">
                    &larr; Back to Categories
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Main Add Category Form Section -->
<section class="section admin-dashboard-section admin-form-section" aria-label="Add new category form">
    <div class="container">
        <div class="admin-dashboard-grid admin-form-layout">
            <!-- Left / Main Column: Category Details Form -->
            <div class="checkout-card admin-panel-card">
                <div class="admin-panel-header">
                    <div>
                        <span class="eyebrow">01 / Category Details</span>
                        <h2 class="checkout-section-heading">New Category Entry</h2>
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

                <form action="<?php echo e($categoryAddUrl); ?>" method="post" class="admin-category-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                    <!-- Category Name -->
                    <div class="form-group">
                        <label for="name" class="form-label">Category Name *</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="100"
                            placeholder="e.g. Seasonal Matcha"
                            value="<?php echo e($formData['name']); ?>"
                            required
                            <?php echo isset($formErrors['name']) ? 'aria-invalid="true" aria-describedby="error-name"' : ''; ?>
                        >
                        <?php if (isset($formErrors['name'])): ?>
                            <span id="error-name" class="form-error"><?php echo e($formErrors['name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Description (Optional) -->
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            maxlength="5000"
                            placeholder="Optional overview of the products included in this category..."
                            <?php echo isset($formErrors['description']) ? 'aria-invalid="true" aria-describedby="error-description"' : ''; ?>
                        ><?php echo e($formData['description']); ?></textarea>
                        <p class="account-section-note">
                            Optional description (up to 5,000 characters) displayed in catalogue administration.
                        </p>
                        <?php if (isset($formErrors['description'])): ?>
                            <span id="error-description" class="form-error"><?php echo e($formErrors['description']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Form Actions -->
                    <div class="contact-form-actions">
                        <a href="<?php echo e($categoriesListUrl); ?>" class="btn btn-outline">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary contact-submit-btn">
                            Save Category
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Column: Category Guidelines Panel -->
            <aside class="auth-editorial-card admin-quicklinks-card" aria-labelledby="category-guide-heading">
                <span class="eyebrow">02 / Taxonomy Guidelines</span>
                <h2 id="category-guide-heading" class="auth-editorial-title">
                    Category Standards
                </h2>
                <p class="auth-editorial-intro">
                    Categories group products across the storefront filter bar and the administration catalogue.
                </p>

                <dl class="auth-benefit-list">
                    <div class="auth-benefit-item">
                        <dt>Unique Category Names</dt>
                        <dd>Each category must have a unique name up to <code>100</code> characters in length.</dd>
                    </div>
                    <div class="auth-benefit-item">
                        <dt>Optional Description</dt>
                        <dd>Provide a concise summary of the tea varieties, vessels, or sets belonging to this category, or leave blank if not needed.</dd>
                    </div>
                    <div class="auth-benefit-item">
                        <dt>Catalogue Assignment</dt>
                        <dd>Once created, the category immediately becomes available when adding or editing products.</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
