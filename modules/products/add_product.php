<?php
// modules/products/add_product.php
$page_title = "Add New Product";
// Ensure session is started at the very beginning if not already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/header.php'; // Also includes functions.php and db_helpers.php

// Load categories for the dropdown
$categories_file = __DIR__ . '/../../data/categories.json';
$categories = read_json_file($categories_file);

// Retrieve sticky form data and errors from session if they exist
$sticky_data = $_SESSION['sticky_form_data'] ?? [];
$form_errors = $_SESSION['product_add_errors'] ?? []; // Assuming process_product.php sets this key

// Clear them from session after retrieving
unset($_SESSION['sticky_form_data']);
unset($_SESSION['product_add_errors']);

// Helper to get sticky value or default
function get_sticky_value(string $field_name, $default = '') {
    global $sticky_data;
    return isset($sticky_data[$field_name]) ? sanitize_input($sticky_data[$field_name]) : $default;
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Add New Product</h1>
            <a href="list_products.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-150">
                View All Products
            </a>
        </div>

        <?php
        // Display general flash messages (e.g., from successful redirection not related to form validation)
        display_flash_message('product_add_success', 'success'); // This would be shown if redirected from another successful action
        display_flash_message('product_add_error', 'error');   // General error not from form validation specifically

        // Display form validation errors, if any
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

        <form action="process_product.php" method="POST" class="space-y-6">
            <input type="hidden" name="action" value="add_product">

            <div>
                <label for="product_name" class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                <input type="text" name="product_name" id="product_name" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo !empty($form_errors) && empty(get_sticky_value('product_name')) ? 'border-red-500' : ''; ?>"
                       value="<?php echo get_sticky_value('product_name'); ?>">
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                <select name="category_id" id="category_id" required
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo !empty($form_errors) && empty(get_sticky_value('category_id')) ? 'border-red-500' : ''; ?>">
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <?php if (isset($category['id']) && isset($category['name'])): ?>
                            <option value="<?php echo sanitize_input($category['id']); ?>"
                                <?php echo (get_sticky_value('category_id') == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize_input($category['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-1">Unit Price (<?php echo sanitize_input($settings['currency_symbol'] ?? '$'); ?>) <span class="text-red-500">*</span></label>
                <input type="number" name="unit_price" id="unit_price" step="0.01" min="0" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo !empty($form_errors) && (get_sticky_value('unit_price', -1) < 0) ? 'border-red-500' : ''; ?>"
                       value="<?php echo get_sticky_value('unit_price'); ?>">
            </div>

            <div>
                <label for="unit_of_measure" class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure (e.g., pcs, kg, liter) <span class="text-red-500">*</span></label>
                <input type="text" name="unit_of_measure" id="unit_of_measure" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo !empty($form_errors) && empty(get_sticky_value('unit_of_measure')) ? 'border-red-500' : ''; ?>"
                       placeholder="e.g., pcs, kg, liter, pack"
                       value="<?php echo get_sticky_value('unit_of_measure'); ?>">
            </div>

            <div>
                <label for="current_stock" class="block text-sm font-medium text-gray-700 mb-1">Current Stock Quantity <span class="text-red-500">*</span></label>
                <input type="number" name="current_stock" id="current_stock" step="1" min="0" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo !empty($form_errors) && (get_sticky_value('current_stock', -1) < 0) ? 'border-red-500' : ''; ?>"
                       value="<?php echo get_sticky_value('current_stock', '0'); ?>">
            </div>

            <div>
                <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-1">Low Stock Threshold (Optional)</label>
                <input type="number" name="low_stock_threshold" id="low_stock_threshold" step="1" min="0"
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm <?php echo !empty($form_errors) && get_sticky_value('low_stock_threshold') !== '' && (filter_var(get_sticky_value('low_stock_threshold'), FILTER_VALIDATE_INT) === false || get_sticky_value('low_stock_threshold') < 0) ? 'border-red-500' : ''; ?>"
                       placeholder="e.g., 10"
                       value="<?php echo get_sticky_value('low_stock_threshold'); ?>">
            </div>
            
            <div class="flex justify-end space-x-4">
                <a href="list_products.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition duration-150">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                    Add Product
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>