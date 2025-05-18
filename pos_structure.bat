@echo off
echo Creating Simple POS System folder and file structure...

REM Root directory files
echo Creating root files...
type nul > index.php
echo. > .htaccess

REM assets folder and subfolders/files
echo Creating assets folder...
mkdir assets
mkdir assets\css
type nul > assets\css\style.css
mkdir assets\js
type nul > assets\js\main.js

REM data folder (ensure this is not publicly accessible via web server config)
echo Creating data folder...
mkdir data
type nul > data\products.json
type nul > data\sales.json
type nul > data\categories.json
type nul > data\settings.json

REM modules folder and subfolders/files
echo Creating modules folder...
mkdir modules

REM modules/products
echo Creating modules/products...
mkdir modules\products
type nul > modules\products\add_product.php
type nul > modules\products\edit_product.php
type nul > modules\products\list_products.php
type nul > modules\products\process_product.php

REM modules/inventory
echo Creating modules/inventory...
mkdir modules\inventory
type nul > modules\inventory\view_stock.php
type nul > modules\inventory\update_stock.php

REM modules/sales
echo Creating modules/sales...
mkdir modules\sales
type nul > modules\sales\new_sale.php
type nul > modules\sales\process_sale.php
type nul > modules\sales\view_sales_history.php
type nul > modules\sales\receipt_template.php

REM modules/reports
echo Creating modules/reports...
mkdir modules\reports
type nul > modules\reports\daily_sales.php
type nul > modules\reports\product_sales_report.php

REM modules/settings
echo Creating modules/settings...
mkdir modules\settings
type nul > modules\settings\manage_settings.php

REM includes folder and files
echo Creating includes folder...
mkdir includes
type nul > includes\header.php
type nul > includes\footer.php
type nul > includes\functions.php
type nul > includes\db_helpers.php

REM vendor folder (usually managed by Composer, creating an empty one for structure)
echo Creating vendor folder...
mkdir vendor

echo.
echo Folder and file structure created successfully!
echo.
echo IMPORTANT:
echo Remember to configure your web server (e.g., Apache or Nginx) to:
echo 1. Deny direct web access to the 'data/' directory.
echo 2. Optionally, set up rewrite rules using the '.htaccess' file for clean URLs if you plan to implement them.
echo.

pause