<?php
// modules/products/edit_product.php
$page_title = "Edit Product";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/header.php'; // Go up two levels

$products_file = __DIR__ . '/../../data/products.json';
$categories_file = __DIR__ . '/../../data/categories.json';

$product_id_to_edit = sanitize_input(get_get_var('id'));
$product_to_edit = null;
$form_product_data = []; // For pre-filling the form

if (empty($product_id_to_edit)) {
    set_flash_message('product_action_error', 'No product ID provided for editing.');
    redirect('list_products.php');
}

$all_products = read_json_file($products_file);
foreach ($all_products as $p) {
    if (isset($p['id']) && $p['id'] === $product_id_to_edit) {
        $product_to_edit = $p;
        break;
    }
}

if (!$product_to_edit) {
    set_flash_message('product_action_error', 'Product not found for editing.');
    redirect('list_products.php');
}

// Load categories for the dropdown
$categories = read_json_file($categories_file);

// Retrieve sticky form data and errors from session if they exist from a failed update attempt
$sticky_data_key = 'sticky_form_data_' . $product_id_to_edit;
$error_session_key = 'product_edit_errors_' . $product_id_to_edit;

$sticky_data = $_SESSION[$sticky_data_key] ?? [];
$form_errors = $_SESSION[$error_session_key] ?? [];

// If there's sticky data, use it. Otherwise, use the product's current data.
if (!empty($sticky_data)) {
    $form_product_data = $sticky_data;
} else {
    $form_product_data = $product_to_edit;
}

// Clear them from session after retrieving
unset($_SESSION[$sticky_data_key]);
unset($_SESSION[$error_session_key]);


// Helper to get form value (checks sticky then original product data)
function get_form_value(string $field_name, $default = '') {
    global $form_product_data, $product_to_edit; // form_product_data is an amalgamation
    
    // Check sticky/submitted data first (which is now in form_product_data if set)
    if (isset($form_product_data[$field_name])) {
        return sanitize_input($form_product_data[$field_name]);
    }
    // Fallback to original product data if not in sticky/submitted
    if (isset($product_to_edit[$field_name])) {
         return sanitize_input($product_to_edit[$field_name]);
    }
    return $default;
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Edit Product: <?php echo sanitize_input($product_to_edit['name'] ?? 'N/A'); ?></h1>
            <a href="list_products.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-150">
                Back to Product List
            </a>
        </div>

        <?php
        // Display general flash messages (e.g., if redirected with a generic error not from this form's validation)
        display_flash_message('product_edit_error', 'error'); // General error for this edit context

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
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" value="<?php echo sanitize_input($product_id_to_edit); ?>">

            <div>
                <label for="product_name" class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                <input type="text" name="product_name" id="product_name" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="<?php echo get_form_value('name'); ?>">
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                <select name="category_id" id="category_id" required
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <?php if (isset($category['id']) && isset($category['name'])): ?>
                            <option value="<?php echo sanitize_input($category['id']); ?>"
                                <?php echo (get_form_value('category_id') == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize_input($category['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-1">Unit Price (<?php echo sanitize_input($settings['currency_symbol'] ?? '$'); ?>) <span class="text-red-500">*</span></label>
                <input type="number" name="unit_price" id="unit_price" step="0.01" min="0" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="<?php echo get_form_value('price'); ?>">
            </div>

            <div>
                <label for="unit_of_measure" class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure (e.g., pcs, kg, liter) <span class="text-red-500">*</span></label>
                <input type="text" name="unit_of_measure" id="unit_of_measure" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       placeholder="e.g., pcs, kg, liter, pack"
                       value="<?php echo get_form_value('unit'); // 'unit' is the key in products.json ?>">
            </div>

            <div>
                <label for="current_stock" class="block text-sm font-medium text-gray-700 mb-1">Current Stock Quantity <span class="text-red-500">*</span></label>
                <input type="number" name="current_stock" id="current_stock" step="1" min="0" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="<?php echo get_form_value('stock'); ?>">
            </div>

            <div>
                <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-1">Low Stock Threshold (Optional)</label>
                <input type="number" name="low_stock_threshold" id="low_stock_threshold" step="1" min="0"
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       placeholder="e.g., 10"
                       value="<?php echo get_form_value('low_stock_threshold'); ?>">
            </div>
            
            <div class="flex justify-end space-x-4">
                <a href="list_products.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition duration-150">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                    Update Product
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>