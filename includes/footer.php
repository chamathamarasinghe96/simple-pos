<?php
// includes/footer.php

// The $settings array (including 'shop_name' and 'currency_symbol')
// is assumed to be loaded and available from when header.php was included on the page.
// If $settings is not available in this scope for some reason (e.g., function scoping),
// you would need to re-load it here:
/*
if (!isset($settings)) {
    // This is a fallback, ideally $settings from header.php should be in scope
    require_once __DIR__ . '/db_helpers.php'; // If not already included via header path
    require_once __DIR__ . '/functions.php'; // If not already included via header path
    $fallback_settings_file_path = __DIR__ . '/../data/settings.json';
    $settings_from_fallback = read_json_file($fallback_settings_file_path);
    $default_settings_fallback = [
        'shop_name' => 'Simple POS System',
        'currency_symbol' => 'LKR'
    ];
    $settings = array_merge($default_settings_fallback, $settings_from_fallback);
}
*/

$current_year_footer = date('Y');
$shop_name_footer = isset($settings['shop_name']) ? sanitize_input($settings['shop_name']) : 'Simple POS System';

?>

    <footer class="bg-gray-800 text-white text-center p-6 mt-12 shadow-inner">
        <p class="text-gray-300">&copy; <?php echo $current_year_footer; ?> <?php echo $shop_name_footer; ?>. All rights reserved.</p>
        <p class="text-sm text-gray-400 mt-1">Powered by a Simple PHP & Tailwind CSS Solution</p>
    </footer>

    <script>
        // Global JavaScript functions can be defined here or in a linked main.js

        /**
         * Displays a SweetAlert2 confirmation dialog before submitting a form.
         * @param {string} formId The ID of the form to submit.
         * @param {string} itemName The name of the item being affected (e.g., deleted).
         */
        function confirmDelete(formId, itemName = 'this item') {
            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to delete <strong>${itemName}</strong>.<br>This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',  // Red for delete
                cancelButtonColor: '#3085d6', // Blue for cancel
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById(formId);
                    if (form) {
                        // You could add a hidden input to signify confirmation if needed by backend,
                        // but usually the submission itself is the confirmation.
                        // e.g.,
                        // let confirmedInput = document.createElement('input');
                        // confirmedInput.type = 'hidden';
                        // confirmedInput.name = 'confirm_delete_flag';
                        // confirmedInput.value = '1';
                        // form.appendChild(confirmedInput);
                        form.submit();
                    } else {
                        Swal.fire('Error!', 'Could not find the form to submit.', 'error');
                    }
                }
            });
        }

        // You can add other global utility JavaScript functions here.
        // For example, a function to format currency, though Alpine.js components
        // might handle this internally for their specific needs.
    </script>

</body>
</html>