<?php
/**
 * Customer Contact / Studio Enquiries Page
 * Zazen Matcha & Tea Co.
 *
 * Allows guests and logged-in customers to submit enquiries into the
 * `contact_messages` table using server-side validation, CSRF protection,
 * and PDO prepared statements.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/customer-auth.php';

ensure_session_started();

$contactUrl = site_url('customer/contact.php');
$shopUrl    = site_url('customer/products.php');
$aboutUrl   = site_url('customer/about.php');

// ----------------------------------------------------------------------------
// 1. Optional Logged-In Customer Prefill (Guests Do Not Require Login)
// ----------------------------------------------------------------------------
$defaultName  = '';
$defaultEmail = '';

$loggedInCustomerId = get_logged_in_customer_id();
if ($loggedInCustomerId !== null) {
    try {
        $customerRow = db_fetch_one(
            $pdo,
            'SELECT first_name, last_name, email FROM customers WHERE customer_id = :customer_id LIMIT 1',
            ['customer_id' => $loggedInCustomerId]
        );

        if ($customerRow !== null) {
            $defaultName  = trim(((string) ($customerRow['first_name'] ?? '')) . ' ' . ((string) ($customerRow['last_name'] ?? '')));
            $defaultEmail = (string) ($customerRow['email'] ?? '');
        }
    } catch (Throwable $exception) {
        error_log('Contact Page Customer Prefill Error: ' . $exception->getMessage());
    }
}

$formData = [
    'name'    => $defaultName,
    'email'   => $defaultEmail,
    'subject' => '',
    'message' => '',
];

$formErrors    = [];
$submitSuccess = false;

// ----------------------------------------------------------------------------
// 2. Handle POST Contact Form Submission
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name']    = clean_input($_POST['name'] ?? '');
    $formData['email']   = strtolower(clean_input($_POST['email'] ?? ''));
    $formData['subject'] = clean_input($_POST['subject'] ?? '');
    $formData['message'] = clean_input($_POST['message'] ?? '');

    // 2a. Verify CSRF Token
    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    $csrfValid = verify_csrf_token($submittedToken);
    if (!$csrfValid) {
        $formErrors['general'] = 'Invalid security token. Please review your message and submit the form again.';
    }

    // 2b. Validate Name (required, <= 100 chars per contact_messages.name VARCHAR(100))
    if ($formData['name'] === '') {
        $formErrors['name'] = 'Your name is required.';
    } elseif (mb_strlen($formData['name']) > 100) {
        $formErrors['name'] = 'Name must not exceed 100 characters.';
    }

    // 2c. Validate Email (required, <= 150 chars per contact_messages.email VARCHAR(150))
    if ($formData['email'] === '') {
        $formErrors['email'] = 'Your email address is required.';
    } elseif (mb_strlen($formData['email']) > 150) {
        $formErrors['email'] = 'Email address must not exceed 150 characters.';
    } elseif (filter_var($formData['email'], FILTER_VALIDATE_EMAIL) === false) {
        $formErrors['email'] = 'Please enter a valid email address.';
    }

    // 2d. Validate Subject (required, <= 200 chars per contact_messages.subject VARCHAR(200))
    if ($formData['subject'] === '') {
        $formErrors['subject'] = 'A subject line is required.';
    } elseif (mb_strlen($formData['subject']) > 200) {
        $formErrors['subject'] = 'Subject must not exceed 200 characters.';
    }

    // 2e. Validate Message (required, <= 5000 chars for contact_messages.message TEXT)
    if ($formData['message'] === '') {
        $formErrors['message'] = 'Please enter your message.';
    } elseif (mb_strlen($formData['message']) > 5000) {
        $formErrors['message'] = 'Message must not exceed 5,000 characters.';
    }

    // ------------------------------------------------------------------------
    // 3. Insert Validated Enquiry into contact_messages
    // ------------------------------------------------------------------------
    if ($csrfValid && empty($formErrors)) {
        try {
            $insertSql = "
                INSERT INTO contact_messages (
                    name,
                    email,
                    subject,
                    message
                ) VALUES (
                    :name,
                    :email,
                    :subject,
                    :message
                )
            ";

            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                'name'    => $formData['name'],
                'email'   => $formData['email'],
                'subject' => $formData['subject'],
                'message' => $formData['message'],
            ]);

            $submitSuccess = true;
            set_flash_message('success', "Thank you for your message. We've received your note.");

            // Clear submitted form values after successful insertion
            $formData = [
                'name'    => '',
                'email'   => '',
                'subject' => '',
                'message' => '',
            ];
        } catch (Throwable $exception) {
            error_log('Contact Form Submission Error: ' . $exception->getMessage());
            $formErrors['general'] = 'We could not send your message at this moment. Please try again shortly.';
        }
    }
}

$pageTitle = 'Contact';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- 1. Hero Section -->
<section class="checkout-hero contact-hero" aria-labelledby="contact-heading">
    <div class="container">
        <div class="checkout-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Studio Enquiries</span>
                <h1 id="contact-heading" class="checkout-title">Let's Talk Matcha &amp; Tea.</h1>
                <p class="checkout-subtitle">
                    Questions about an order, our specialty teas, or the ritual itself? Leave us a note and we'll get back to you.
                </p>
            </div>
            <p class="contact-hero-note">
                Thoughtful questions welcome.
            </p>
        </div>
    </div>
</section>

<!-- 2 & 3. Contact Information & Enquiry Form Section -->
<section class="section contact-section" aria-label="Contact details and enquiry form">
    <div class="container">
        <div class="contact-layout">
            <!-- Left Column: Editorial Contact Information -->
            <aside class="contact-info-column" aria-labelledby="contact-info-heading">
                <div class="auth-editorial-card contact-info-card">
                    <span class="eyebrow">01 / Direct Correspondence</span>
                    <h2 id="contact-info-heading" class="auth-editorial-title">
                        Studio &amp; Enquiries
                    </h2>
                    <p class="auth-editorial-intro">
                        Whether you are selecting your first ceremonial matcha tin, pairing a wide-base ceramic bowl, or checking on an existing order reference, our studio desk is here to assist.
                    </p>

                    <dl class="contact-detail-list">
                        <div class="contact-detail-item">
                            <dt>Studio Email</dt>
                            <dd>
                                <a href="mailto:hello@zazenmatcha.co.za" class="contact-email-link">hello@zazenmatcha.co.za</a>
                                <span class="contact-subtext">Direct correspondence with our Cape Town tea studio.</span>
                            </dd>
                        </div>

                        <div class="contact-detail-item">
                            <dt>Studio Location</dt>
                            <dd>Cape Town, South Africa</dd>
                        </div>

                        <div class="contact-detail-item">
                            <dt>Studio Hours</dt>
                            <dd>Monday &ndash; Saturday, 08:30 &ndash; 17:00 SAST</dd>
                        </div>
                    </dl>

                    <p class="contact-studio-note">
                        All correspondence and tasting enquiries are reviewed by our tea specialists within one business day.
                    </p>

                    <div class="contact-info-links">
                        <a href="<?php echo e($shopUrl); ?>" class="editorial-link">
                            Explore the Collection &rarr;
                        </a>
                        <a href="<?php echo e($aboutUrl); ?>" class="editorial-link">
                            Read Our Philosophy &rarr;
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Right Column: Contact Form -->
            <div class="checkout-card contact-form-card">
                <h2 class="checkout-section-heading">02 / Send a Message</h2>

                <?php if ($submitSuccess): ?>
                    <div class="contact-success-banner" role="status">
                        <p class="contact-success-title">Thank you for your message. We've received your note.</p>
                        <p class="contact-success-text">
                            Your enquiry has been recorded in the Zazen Matcha &amp; Tea Co. studio message log.
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($formErrors)): ?>
                    <div class="cart-warnings auth-form-alert" role="alert">
                        <p class="cart-warnings-title">Please review your message details:</p>
                        <?php if (!empty($formErrors['general'])): ?>
                            <p><?php echo e($formErrors['general']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e($contactUrl); ?>" method="post" class="contact-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                    <div class="checkout-field-grid">
                        <div class="form-group">
                            <label for="name" class="form-label">Your Name *</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                maxlength="100"
                                placeholder="Full name"
                                value="<?php echo e($formData['name']); ?>"
                                required
                                autocomplete="name"
                                <?php echo isset($formErrors['name']) ? 'aria-invalid="true" aria-describedby="error-name"' : ''; ?>
                            >
                            <?php if (isset($formErrors['name'])): ?>
                                <span id="error-name" class="form-error"><?php echo e($formErrors['name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                maxlength="150"
                                placeholder="name@example.com"
                                value="<?php echo e($formData['email']); ?>"
                                required
                                autocomplete="email"
                                <?php echo isset($formErrors['email']) ? 'aria-invalid="true" aria-describedby="error-email"' : ''; ?>
                            >
                            <?php if (isset($formErrors['email'])): ?>
                                <span id="error-email" class="form-error"><?php echo e($formErrors['email']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="subject" class="form-label">Subject *</label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            maxlength="200"
                            placeholder="e.g. Question about Ceremonial Matcha or Order #3"
                            value="<?php echo e($formData['subject']); ?>"
                            required
                            <?php echo isset($formErrors['subject']) ? 'aria-invalid="true" aria-describedby="error-subject"' : ''; ?>
                        >
                        <?php if (isset($formErrors['subject'])): ?>
                            <span id="error-subject" class="form-error"><?php echo e($formErrors['subject']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="message" class="form-label">Message *</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            maxlength="5000"
                            placeholder="Write your question or note here..."
                            required
                            <?php echo isset($formErrors['message']) ? 'aria-invalid="true" aria-describedby="error-message"' : ''; ?>
                        ><?php echo e($formData['message']); ?></textarea>
                        <?php if (isset($formErrors['message'])): ?>
                            <span id="error-message" class="form-error"><?php echo e($formErrors['message']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="contact-form-actions">
                        <button type="submit" class="btn btn-primary contact-submit-btn">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
