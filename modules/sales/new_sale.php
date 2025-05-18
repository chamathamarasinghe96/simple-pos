<?php
// modules/sales/new_sale.php
$page_title = "New Sale Transaction";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/header.php'; // Also includes functions.php and db_helpers.php

$products_file = __DIR__ . '/../../data/products.json';
$all_products_raw = read_json_file($products_file);

// Filter out products with 0 or negative stock if you don't want them to be sellable
$available_products = array_filter($all_products_raw, function($product) {
    return isset($product['stock']) && $product['stock'] > 0;
});
// Re-index if necessary, though not strictly needed for Alpine.js iteration
// $available_products = array_values($available_products);


$settings_file = __DIR__ . '/../../data/settings.json';
$pos_settings = read_json_file($settings_file);
$tax_rate_percent = (float)($pos_settings['tax_rate_percent'] ?? 0);
$currency_symbol = $pos_settings['currency_symbol'] ?? '$';

?>

<div class="container mx-auto px-2 py-4" x-data="posApp()">
    <div class="flex flex-col lg:flex-row gap-4">

        <div class="lg:w-3/5 bg-white p-4 rounded-lg shadow-lg h-full">
            <h2 class="text-2xl font-semibold mb-3 text-gray-700">Select Products</h2>
            <div class="mb-4">
                <input type="text" x-model="searchTerm" @input="filterProducts()" placeholder="Search products by name or ID..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="overflow-y-auto h-[calc(100vh-250px)] max-h-[600px] pr-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button @click="addToCart(product)"
                                class="bg-gray-50 hover:bg-blue-100 border border-gray-200 p-3 rounded-lg shadow text-left focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-150"
                                :disabled="product.stock <= getCartItemQuantity(product.id)">
                            <h3 class="font-semibold text-sm text-gray-800 truncate" x-text="product.name"></h3>
                            <p class="text-xs text-gray-600" x-text="formatCurrency(product.price) + ' / ' + product.unit"></p>
                            <p class="text-xs mt-1" 
                               :class="{
                                   'text-green-600': product.stock - getCartItemQuantity(product.id) > (product.low_stock_threshold || 0),
                                   'text-yellow-600': product.stock - getCartItemQuantity(product.id) <= (product.low_stock_threshold || 0) && product.stock - getCartItemQuantity(product.id) > 0,
                                   'text-red-600 font-bold': product.stock - getCartItemQuantity(product.id) <= 0
                               }">
                                Stock: <span x-text="product.stock - getCartItemQuantity(product.id)"></span>
                            </p>
                             <span x-show="product.stock <= getCartItemQuantity(product.id)" class="text-xs text-red-500 font-semibold block">Out of stock / Max in cart</span>
                        </button>
                    </template>
                    <template x-if="filteredProducts.length === 0 && searchTerm.length > 0">
                        <p class="col-span-full text-center text-gray-500 py-4">No products match your search.</p>
                    </template>
                     <template x-if="availableProducts.length === 0">
                        <p class="col-span-full text-center text-gray-500 py-4">No products available for sale.</p>
                    </template>
                </div>
            </div>
        </div>

        <div class="lg:w-2/5 bg-white p-4 rounded-lg shadow-lg h-full">
            <h2 class="text-2xl font-semibold mb-3 text-gray-700">Current Sale</h2>
            <div class="overflow-y-auto h-[calc(100vh-380px)] max-h-[450px]">
                <template x-if="cart.length === 0">
                    <p class="text-gray-500 text-center py-10">Cart is empty. Add products to start a sale.</p>
                </template>
                <template x-if="cart.length > 0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="item in cart" :key="item.id">
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-800" x-text="item.name"></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        <input type="number" x-model.number="item.quantity" 
                                               @change="updateQuantity(item.id, item.quantity)" 
                                               min="1" :max="getProductStock(item.id)"
                                               class="w-16 text-center border border-gray-300 rounded-md py-1">
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-gray-600" x-text="formatCurrency(item.price)"></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-gray-800" x-text="formatCurrency(item.price * item.quantity)"></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-center">
                                        <button @click="removeFromCart(item.id)" class="text-red-500 hover:text-red-700" title="Remove Item">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
            </div>

            <div class="mt-auto pt-4 border-t border-gray-200">
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-semibold text-gray-800" x-text="formatCurrency(subtotal)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax (<?php echo $tax_rate_percent; ?>%):</span>
                        <span class="font-semibold text-gray-800" x-text="formatCurrency(taxAmount)"></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold">
                        <span class="text-gray-800">Grand Total:</span>
                        <span class="text-indigo-600" x-text="formatCurrency(grandTotal)"></span>
                    </div>
                </div>

                <form id="saleForm" action="process_sale.php" method="POST" class="mt-4">
                    <input type="hidden" name="cart_data" :value="JSON.stringify(cart)">
                    <input type="hidden" name="subtotal" :value="subtotal.toFixed(2)">
                    <input type="hidden" name="tax_amount" :value="taxAmount.toFixed(2)">
                    <input type="hidden" name="grand_total" :value="grandTotal.toFixed(2)">
                    <input type="hidden" name="tax_rate_percent" value="<?php echo $tax_rate_percent; ?>">
                    <input type="hidden" name="payment_method" value="Cash"> <button type="submit" @click="prepareSaleForSubmission"
                            :disabled="cart.length === 0"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg shadow-md transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                        Process Sale (Cash)
                    </button>
                </form>
                 <button @click="clearCartConfirmation()" x-show="cart.length > 0"
                        class="w-full mt-2 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                    Clear Sale
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function posApp() {
    return {
        searchTerm: '',
        // Important: Ensure PHP array keys are preserved if they are non-sequential, or use array_values if needed.
        // For product IDs, string keys are fine.
        availableProducts: <?php echo json_encode(array_values($available_products)); ?>, // Ensure it's a simple array for Alpine
        filteredProducts: [],
        cart: [], // Each item: {id, name, price, quantity, unit, stock (original available stock)}
        taxRate: <?php echo $tax_rate_percent / 100; ?>,
        currencySymbol: '<?php echo $currency_symbol; ?>',

        init() {
            this.filteredProducts = [...this.availableProducts];
            // Load cart from localStorage if needed (for persistence across page refresh - advanced)
            // if (localStorage.getItem('posCart')) {
            //     this.cart = JSON.parse(localStorage.getItem('posCart'));
            // }
            // this.$watch('cart', () => localStorage.setItem('posCart', JSON.stringify(this.cart)));
        },

        filterProducts() {
            if (this.searchTerm.trim() === '') {
                this.filteredProducts = [...this.availableProducts];
                return;
            }
            const lowerSearchTerm = this.searchTerm.toLowerCase();
            this.filteredProducts = this.availableProducts.filter(product => {
                return (product.name.toLowerCase().includes(lowerSearchTerm) || 
                        product.id.toLowerCase().includes(lowerSearchTerm));
            });
        },

        addToCart(product) {
            if (this.getCartItemQuantity(product.id) >= product.stock) {
                Swal.fire('Out of Stock', `Cannot add more of "${product.name}". Available stock reached.`, 'warning');
                return;
            }

            const cartItem = this.cart.find(item => item.id === product.id);
            if (cartItem) {
                if (cartItem.quantity < product.stock) {
                    cartItem.quantity++;
                } else {
                     Swal.fire('Stock Limit', `Maximum available stock for "${product.name}" already in cart.`, 'warning');
                }
            } else {
                this.cart.push({ 
                    id: product.id, 
                    name: product.name, 
                    price: parseFloat(product.price), 
                    quantity: 1, 
                    unit: product.unit,
                    original_stock: parseInt(product.stock) // Store original stock for validation
                });
            }
        },

        removeFromCart(productId) {
            this.cart = this.cart.filter(item => item.id !== productId);
        },
        
        getProductStock(productId) {
            const product = this.availableProducts.find(p => p.id === productId);
            return product ? product.stock : 0;
        },

        getCartItemQuantity(productId) {
            const cartItem = this.cart.find(item => item.id === productId);
            return cartItem ? cartItem.quantity : 0;
        },

        updateQuantity(productId, newQuantity) {
            const cartItem = this.cart.find(item => item.id === productId);
            if (cartItem) {
                const productOriginalStock = this.getProductStock(productId);
                if (newQuantity >= 1 && newQuantity <= productOriginalStock) {
                    cartItem.quantity = parseInt(newQuantity);
                } else if (newQuantity > productOriginalStock) {
                    cartItem.quantity = productOriginalStock; // Set to max available
                    Swal.fire('Stock Limit', `Quantity for "${cartItem.name}" adjusted to maximum available stock: ${productOriginalStock}.`, 'warning');
                } else {
                    // If newQuantity is less than 1, remove or set to 1. For now, let's remove if 0 or less.
                    // Or, simply ensure it's at least 1 via input min="1" and don't allow less.
                    // The input field has min="1", so this might only be an issue if changed programmatically.
                    if (newQuantity < 1) cartItem.quantity = 1; // Or this.removeFromCart(productId);
                }
            }
             // If quantity becomes 0 or less from input, remove it (optional behavior)
            if (cartItem && cartItem.quantity < 1) {
                this.removeFromCart(productId);
            }
        },

        get subtotal() {
            return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        },
        get taxAmount() {
            return this.subtotal * this.taxRate;
        },
        get grandTotal() {
            return this.subtotal + this.taxAmount;
        },

        formatCurrency(amount) {
            return this.currencySymbol + amount.toFixed(2);
        },
        
        clearCartConfirmation() {
            Swal.fire({
                title: 'Clear Current Sale?',
                text: "Are you sure you want to remove all items from the current sale?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, clear it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.cart = [];
                    // localStorage.removeItem('posCart'); // if using localStorage persistence
                    Swal.fire('Cleared!', 'The sale has been cleared.', 'success');
                }
            });
        },

        prepareSaleForSubmission(event) {
            if (this.cart.length === 0) {
                event.preventDefault(); // Prevent form submission
                Swal.fire('Empty Cart', 'Cannot process an empty sale. Please add products to the cart.', 'error');
                return false;
            }
            // The hidden inputs are bound with :value, so they should be up-to-date.
            // Additional validation before actual submission can go here if needed.
            // Example: Check if any cart quantity exceeds available stock (should be prevented by UI, but good to double check)
            let canSubmit = true;
            this.cart.forEach(item => {
                const productInSystem = this.availableProducts.find(p => p.id === item.id);
                if (!productInSystem || item.quantity > productInSystem.stock) {
                    Swal.fire('Stock Issue', `Quantity for "${item.name}" (${item.quantity}) exceeds available stock (${productInSystem ? productInSystem.stock : 0}). Please adjust.`, 'error');
                    canSubmit = false;
                    event.preventDefault();
                }
            });
            if (!canSubmit) return false;

            // Optionally, show a processing message
            Swal.fire({
                title: 'Processing Sale...',
                text: 'Please wait.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            // Form will submit due to button type="submit" if not prevented.
        }
    };
}
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>