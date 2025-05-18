<?php
// modules/settings/manage_settings.php
$page_title = "Shop Settings";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/header.php'; // Also includes functions.php and db_helpers.php

$settings_file = __DIR__ . '/../../data/settings.json';
$current_settings = read_json_file($settings_file);

// Define default values if settings are not yet present
$defaults = [
    'shop_name' => 'My Grocery Store',
    'address' => '',
    'contact' => '',
    'tax_rate_percent' => 0.0,
    'currency_symbol' => 'LKR' // Default to Sri Lankan Rupee based on context
];

// Merge current settings with defaults to ensure all keys exist for the form
$form_data = array_merge($defaults, $current_settings);


// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_shop_name = sanitize_input(get_post_var('shop_name', $defaults['shop_name']));
    $submitted_address = sanitize_input(get_post_var('address', $defaults['address']));
    $submitted_contact = sanitize_input(get_post_var('contact', $defaults['contact']));
    $submitted_tax_rate = filter_var(get_post_var('tax_rate_percent'), FILTER_VALIDATE_FLOAT);
    $submitted_currency_symbol = sanitize_input(get_post_var('currency_symbol', $defaults['currency_symbol']));

    // Update form_data with submitted values for sticky form behavior even on error
    $form_data['shop_name'] = $submitted_shop_name;
    $form_data['address'] = $submitted_address;
    $form_data['contact'] = $submitted_contact;
    $form_data['currency_symbol'] = $submitted_currency_symbol;


    $errors = [];
    if (empty($submitted_shop_name)) {
        $errors[] = "Shop name cannot be empty.";
    }
    if ($submitted_tax_rate === false || $submitted_tax_rate < 0) {
        $errors[] = "Tax rate must be a valid non-negative number.";
        // Keep the erroneous value for sticky form if needed, or reset to a valid default for display
        $form_data['tax_rate_percent'] = get_post_var('tax_rate_percent'); // Keep user's input for correction
    } else {
         $form_data['tax_rate_percent'] = $submitted_tax_rate; // Validated value
    }
     if (empty($submitted_currency_symbol)) {
        $errors[] = "Currency symbol cannot be empty.";
    }


    if (!empty($errors)) {
        $_SESSION['settings_update_errors'] = $errors;
        // Errors will be displayed below, form fields will retain $form_data
    } else {
        $updated_settings_data = [
            'shop_name' => $submitted_shop_name,
            'address' => $submitted_address,
            'contact' => $submitted_contact,
            'tax_rate_percent' => (float)$submitted_tax_rate,
            'currency_symbol' => $submitted_currency_symbol
        ];

        if (write_json_file($settings_file, $updated_settings_data)) {
            set_flash_message('settings_update_success', 'Settings updated successfully!');
            // Update $current_settings and $form_data for immediate reflection on the page if not redirecting,
            // but redirecting is cleaner to avoid re-submission issues.
            // $current_settings = $updated_settings_data;
            // $form_data = array_merge($defaults, $current_settings);
            redirect('manage_settings.php'); // Redirect to self to show fresh data and clear POST
        } else {
            set_flash_message('settings_update_error', 'Failed to save settings. Please check file permissions or logs.');
        }
    }
}

// Retrieve errors from session if set by the POST handler above for display
$form_errors = $_SESSION['settings_update_errors'] ?? [];
unset($_SESSION['settings_update_errors']); // Clear after retrieving

?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Shop Settings</h1>

        <?php
        display_flash_message('settings_update_success', 'success');
        display_flash_message('settings_update_error', 'error');

        if (!empty($form_errors)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Please correct the following errors:</p>
                <ul class="list-disc ml-5 mt-2">
                    <?php foreach ($form_errors as $error): ?>
                        <li><?php echo sanitize_input($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="manage_settings.php" method="POST" class="space-y-6">
            <div>
                <label for="shop_name" class="block text-sm font-medium text-gray-700 mb-1">Shop Name <span class="text-red-500">*</span></label>
                <input type="text" name="shop_name" id="shop_name" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="<?php echo sanitize_input($form_data['shop_name']); ?>">
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" id="address" rows="3"
                          class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo sanitize_input($form_data['address']); ?></textarea>
            </div>

            <div>
                <label for="contact" class="block text-sm font-medium text-gray-700 mb-1">Contact (Phone/Email)</label>
                <input type="text" name="contact" id="contact"
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="<?php echo sanitize_input($form_data['contact']); ?>">
            </div>

            <div>
                <label for="currency_symbol" class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol <span class="text-red-500">*</span></label>
                <input type="text" name="currency_symbol" id="currency_symbol" required placeholder="e.g., LKR, $, €"
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="<?php echo sanitize_input($form_data['currency_symbol']); ?>">
            </div>

            <div>
                <label for="tax_rate_percent" class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%) <span class="text-red-500">*</span></label>
                <input type="number" name="tax_rate_percent" id="tax_rate_percent" step="0.01" min="0" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="<?php echo sanitize_input((string)$form_data['tax_rate_percent']); // Cast to string for value attribute ?>">
            </div>
            
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-6 rounded-lg shadow-md transition duration-150 ease-in-out">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>