<?php
// modules/products/list_products.php
$page_title = "Manage Products";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/header.php'; // Go up two levels

$products_file = __DIR__ . '/../../data/products.json';
$products = read_json_file($products_file);

// Reverse the array to show newest products first, if desired
// $products = array_reverse($products);

$currency_symbol = $settings['currency_symbol'] ?? '$';

?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 md:mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4 md:mb-0">Product List</h1>
            <a href="add_product.php" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add New Product
            </a>
        </div>

        <?php
        // Display flash messages from process_product.php
        display_flash_message('product_action_success', 'success');
        display_flash_message('product_action_error', 'error');
        ?>

        <?php if (empty($products)): ?>
            <div class="text-center text-gray-500 py-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-4 text-xl">No products found.</p>
                <p class="mt-2">Get started by <a href="add_product.php" class="text-blue-500 hover:underline">adding a new product</a>.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Low Stock Alert</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                            <?php
                                $product_id = sanitize_input($product['id'] ?? 'N/A');
                                $product_name = sanitize_input($product['name'] ?? 'N/A');
                                $is_low_stock = isset($product['stock']) && isset($product['low_stock_threshold']) && $product['low_stock_threshold'] > 0 && $product['stock'] < $product['low_stock_threshold'];
                            ?>
                            <tr class="<?php echo $is_low_stock ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50'; ?>">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?php echo $product_id; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $product_name; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo sanitize_input($product['category_name'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo format_price((float)($product['price'] ?? 0), $currency_symbol); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm <?php echo $is_low_stock ? 'text-red-600 font-bold' : 'text-gray-600'; ?>">
                                    <?php echo sanitize_input($product['stock'] ?? '0'); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo sanitize_input($product['unit'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo (isset($product['low_stock_threshold']) && $product['low_stock_threshold'] > 0) ? sanitize_input($product['low_stock_threshold']) : '-'; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                    <a href="edit_product.php?id=<?php echo urlencode($product_id); ?>" class="text-indigo-600 hover:text-indigo-900 transition duration-150" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    <form action="process_product.php" method="POST" class="inline-block" id="deleteForm_<?php echo $product_id; ?>">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <button type="button" onclick="confirmDelete('deleteForm_<?php echo $product_id; ?>', '<?php echo addslashes($product_name); // Escape for JS ?>')" class="text-red-600 hover:text-red-900 transition duration-150" title="Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>