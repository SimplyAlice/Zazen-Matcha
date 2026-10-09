<?php
/**
 * Administrator Edit Category Page
 * Zazen Matcha & Tea Co.
 *
 * Protected administration page that loads an existing category by ?id=<category_id>,
 * populates the edit form with current database values, validates submitted changes
 * server-side with CSRF protection and case-insensitive duplicate-name checking
 * (excluding the current category), updates the `categories` record via a prepared
 * PDO UPDATE statement, and redirects to admin/categories.php via PRG.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$categoriesListUrl = site_url('admin/categories.php');

// ----------------------------------------------------------------------------
// 1. Validate Category ID from $_GET['id'] (Positive Integer Only)
// ----------------------------------------------------------------------------
$rawCategoryId = $_GET['id'] ?? '';
$categoryId    = (
    !is_array($rawCategoryId)
    && ctype_digit(trim((string) $rawCategoryId))
    && (int) trim((string) $rawCategoryId) > 0
)
    ? (int) trim((string) $rawCategoryId)
    : 0;

$existingCategory = null;
$categoryFound    = false;
$dbLoadError      = null;

// ----------------------------------------------------------------------------
// 2. Load Existing Category from MySQL via Prepared Statement
// ----------------------------------------------------------------------------
if ($categoryId > 0) {
    try {
        $categorySql = "
            SELECT
                category_id,
                name,
                description,
                created_at,
                updated_at
            FROM categories
            WHERE category_id = :category_id
            LIMIT 1
        ";

        $existingCategory = db_fetch_one($pdo, $categorySql, [
            'category_id' => $categoryId,
        ]);

        if ($existingCategory !== null) {
            $categoryFound = true;
        }
    } catch (Throwable $exception) {
        error_log('Admin Edit Category Load Error: ' . $exception->getMessage());
        $dbLoadError = 'Unable to load category details right now. Please check the database connection.';
    }
}

// ----------------------------------------------------------------------------
// 3. Populate Initial Form State from Existing Category
// ----------------------------------------------------------------------------
$formData = [
    'name'        => '',
    'description' => '',
];

if ($categoryFound && $existingCategory !== null) {
    $formData = [
        'name'        => (string) ($existingCategory['name'] ?? ''),
        'description' => (string) ($existingCategory['description'] ?? ''),
    ];
}

$formErrors      = [];
$categoryEditUrl = site_url('admin/category-edit.php?id=' . $categoryId);

// ----------------------------------------------------------------------------
// 4. Handle POST Update Submission (Only When Category Exists)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $categoryFound) {
    $formData['name']        = trim(isset($_POST['name']) && !is_array($_POST['name']) ? (string) $_POST['name'] : '');
    $formData['description'] = trim(isset($_POST['description']) && !is_array($_POST['description']) ? (string) $_POST['description'] : '');

    // 4a. Verify CSRF Token
    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    $csrfValid = verify_csrf_token($submittedToken);
    if (!$csrfValid) {
        $formErrors['general'] = 'Invalid security token. Please review your changes and try submitting again.';
    }

    // 4b. Validate Category Name (Required, VARCHAR(100) in `categories` schema)
    if ($formData['name'] === '') {
        $formErrors['name'] = 'Category name is required.';
    } elseif (mb_strlen($formData['name']) > 100) {
        $formErrors['name'] = 'Category name must not exceed 100 characters.';
    } else {
        // 4c. Check for Duplicate Category Name (Case-Insensitive, Excluding Current Category ID)
        try {
            $duplicateSql = "
                SELECT
                    category_id,
                    name
                FROM categories
                WHERE LOWER(name) = LOWER(:name)
                  AND category_id != :category_id
                LIMIT 1
            ";

            $duplicateCategory = db_fetch_one($pdo, $duplicateSql, [
                'name'        => $formData['name'],
                'category_id' => $categoryId,
            ]);

            if ($duplicateCategory !== null) {
                $formErrors['name'] = 'A category with this name already exists. Please enter a unique category name.';
            }
        } catch (Throwable $exception) {
            error_log('Admin Edit Category Duplicate Check Error: ' . $exception->getMessage());
            $formErrors['general'] = 'Unable to verify category uniqueness right now. Please try again shortly.';
        }
    }

    // 4d. Validate Optional Description (Nullable TEXT in `categories` schema; cap at 5,000 characters)
    $validatedDescription = null;
    if ($formData['description'] !== '') {
        if (mb_strlen($formData['description']) > 5000) {
            $formErrors['description'] = 'Category description must not exceed 5,000 characters.';
        } else {
            $validatedDescription = $formData['description'];
        }
    }

    // ------------------------------------------------------------------------
    // 5. Reconfirm Category Exists & Execute Scoped Prepared UPDATE (PRG)
    // ------------------------------------------------------------------------
    if ($csrfValid && empty($formErrors) && $dbLoadError === null) {
        try {
            $confirmSql = "
                SELECT category_id
                FROM categories
                WHERE category_id = :category_id
                LIMIT 1
            ";

            $confirmedCategory = db_fetch_one($pdo, $confirmSql, [
                'category_id' => $categoryId,
            ]);

            if ($confirmedCategory === null) {
                $categoryFound = false;
            } else {
                $updateSql = "
                    UPDATE categories
                    SET
                        name        = :name,
                        description = :description
                    WHERE category_id = :category_id
                ";

                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([
                    'name'        => $formData['name'],
                    'description' => $validatedDescription,
                    'category_id' => $categoryId,
                ]);

                set_flash_message(
                    'success',
                    'Category "' . $formData['name'] . '" has been updated successfully.'
                );

                redirect($categoriesListUrl);
            }
        } catch (Throwable $exception) {
            error_log('Admin Edit Category Update Error: ' . $exception->getMessage());
            $formErrors['general'] = 'We could not save your changes right now. Please try again shortly.';
        }
    }
}

$pageTitle = 'Edit Category';

// Highlight Categories as the active navigation item in the shared header
$originalPhpSelf     = $_SERVER['PHP_SELF'] ?? '';
$_SERVER['PHP_SELF'] = 'categories.php';

require_once dirname(__DIR__) . '/includes/admin-header.php';

$_SERVER['PHP_SELF'] = $originalPhpSelf;
?>

<!-- 1. Page Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-edit-category-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Catalogue Administration</span>
                <h1 id="admin-edit-category-heading" class="checkout-title">
                    <?php echo $categoryFound ? 'Edit Category' : 'Category Not Found'; ?>
                </h1>
                <p class="checkout-subtitle">
                    <?php if ($categoryFound): ?>
                        Editing category record <strong>#<?php echo (int) $categoryId; ?></strong> &mdash; update the category name or description displayed across catalogue administration.
                    <?php else: ?>
                        The requested category record could not be located in the Zazen Matcha catalogue.
                    <?php endif; ?>
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

<!-- 2. Main Edit Category Content Section -->
<section class="section admin-dashboard-section admin-form-section" aria-label="Edit category form">
    <div class="container">
        <?php if ($dbLoadError !== null): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Database Notice</p>
                <p><?php echo e($dbLoadError); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$categoryFound): ?>
            <!-- Clean Category Not Found State -->
            <div class="checkout-card admin-panel-card">
                <div class="admin-empty-state" role="status">
                    <p class="admin-empty-title">Category Not Found</p>
                    <p class="admin-empty-text">
                        The category ID provided is missing, invalid, or does not match an existing category in the database.
                    </p>
                    <div class="admin-empty-actions">
                        <a href="<?php echo e($categoriesListUrl); ?>" class="btn btn-primary">
                            Back to Categories
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="admin-dashboard-grid admin-form-layout">
                <!-- Left / Main Column: Edit Category Form -->
                <div class="checkout-card admin-panel-card">
                    <div class="admin-panel-header">
                        <div>
                            <span class="eyebrow">01 / Category Record #<?php echo (int) $categoryId; ?></span>
                            <h2 class="checkout-section-heading">Update Category Details</h2>
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

                    <form action="<?php echo e($categoryEditUrl); ?>" method="post" class="admin-category-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                        <!-- Category Name -->
                        <div class="form-group">
                            <label for="name" class="form-label">Category Name *</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                maxlength="100"
                                placeholder="e.g. Matcha"
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
                                Update Category
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Category Guidelines Panel -->
                <aside class="auth-editorial-card admin-quicklinks-card" aria-labelledby="category-edit-guide-heading">
                    <span class="eyebrow">02 / Taxonomy Guidelines</span>
                    <h2 id="category-edit-guide-heading" class="auth-editorial-title">
                        Category Standards
                    </h2>
                    <p class="auth-editorial-intro">
                        Updates made here apply immediately across the product catalogue and category listings.
                    </p>

                    <dl class="auth-benefit-list">
                        <div class="auth-benefit-item">
                            <dt>Category Identifier</dt>
                            <dd>Record <strong>#<?php echo (int) $categoryId; ?></strong> is permanently linked to its assigned products and cannot be reassigned to a different ID.</dd>
                        </div>
                        <div class="auth-benefit-item">
                            <dt>Unique Category Names</dt>
                            <dd>Each category must have a unique name up to <code>100</code> characters in length. Keeping the current name is always permitted.</dd>
                        </div>
                        <div class="auth-benefit-item">
                            <dt>Optional Description</dt>
                            <dd>Provide a concise summary up to <code>5,000</code> characters, or leave blank to store an empty description.</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
