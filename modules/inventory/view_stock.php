<?php
// modules/inventory/view_stock.php
$page_title = "View Stock Levels";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/header.php';

$products_file = __DIR__ . '/../../data/products.json';
$all_products = read_json_file($products_file);

$currency_symbol = $settings['currency_symbol'] ?? '$'; // from header.php's $settings

// Filtering logic
$filter = sanitize_input(get_get_var('filter', 'all')); // 'all' or 'low_stock'
$products_to_display = [];

if ($filter === 'low_stock') {
    $page_title = "Low Stock Items"; // Update page title for filtered view
    foreach ($all_products as $product) {
        if (isset($product['stock']) && isset($product['low_stock_threshold']) && $product['low_stock_threshold'] > 0 && $product['stock'] < $product['low_stock_threshold']) {
            $products_to_display[] = $product;
        }
    }
} else {
    $products_to_display = $all_products;
}

// Sorting by stock level (optional, e.g., lowest stock first)
// usort($products_to_display, function($a, $b) {
//     return ($a['stock'] ?? PHP_INT_MAX) <=> ($b['stock'] ?? PHP_INT_MAX);
// });

?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 md:mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4 md:mb-0"><?php echo $page_title; ?></h1>
            <div class="flex space-x-2">
                <a href="view_stock.php?filter=all" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out <?php echo ($filter === 'all' ? 'ring-2 ring-blue-300 ring-offset-1' : ''); ?>">
                    All Stock
                </a>
                <a href="view_stock.php?filter=low_stock" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out <?php echo ($filter === 'low_stock' ? 'ring-2 ring-red-300 ring-offset-1' : ''); ?>">
                    Low Stock
                </a>
                <a href="update_stock.php" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                    Update Stock Manually
                </a>
            </div>
        </div>

        <?php
        // Display flash messages (e.g., after a manual stock update)
        display_flash_message('stock_update_success', 'success');
        display_flash_message('stock_update_error', 'error');
        ?>

        <?php if (empty($products_to_display)): ?>
            <div class="text-center text-gray-500 py-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4M4 7s0 0 0 0M12 15v4M8 11l4 4 4-4M8 7h8" />
                </svg>
                <p class="mt-4 text-xl">
                    <?php echo ($filter === 'low_stock') ? 'No low stock items found.' : 'No products found in inventory.'; ?>
                </p>
                <?php if ($filter !== 'low_stock'): ?>
                <p class="mt-2">You can add products via the <a href="<?php echo base_url(); ?>modules/products/add_product.php" class="text-blue-500 hover:underline">Product Management</a> page.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Low Stock Threshold</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products_to_display as $product): ?>
                            <?php
                                $product_id = sanitize_input($product['id'] ?? 'N/A');
                                $current_stock_val = (int)($product['stock'] ?? 0);
                                $low_stock_threshold_val = (int)($product['low_stock_threshold'] ?? 0);
                                $is_low_stock = ($low_stock_threshold_val > 0 && $current_stock_val < $low_stock_threshold_val);
                            ?>
                            <tr class="<?php echo $is_low_stock ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50'; ?>">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?php echo $product_id; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo sanitize_input($product['name'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo sanitize_input($product['category_name'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right <?php echo $is_low_stock ? 'text-red-600 font-bold' : 'text-gray-600'; ?>">
                                    <?php echo $current_stock_val; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-600">
                                    <?php echo ($low_stock_threshold_val > 0) ? $low_stock_threshold_val : '-'; ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo sanitize_input($product['unit'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                    <?php if ($is_low_stock): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Low Stock
                                        </span>
                                    <?php elseif ($low_stock_threshold_val > 0 && $current_stock_val == $low_stock_threshold_val) : ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            At Threshold
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Sufficient
                                        </span>
                                    <?php endif; ?>
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