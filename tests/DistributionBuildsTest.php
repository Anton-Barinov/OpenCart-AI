<?php
/**
 * OpenCart Distribution Specifics Test
 * Tests compatibility with custom distributions:
 * - ocStore (main_category, SEO URLs)
 * - BazarCMS / Prostore (custom prefixes, CIS currencies)
 * - Multi-store configurations
 *
 * @author Anton Barinov <https://github.com/Anton-Barinov>
 * @license MIT
 */

require_once __DIR__ . '/MockOpenCart.php';
require_once dirname(__DIR__) . '/upload/system/library/opencart_ai/autoload.php';

use OpenCartMcp\Core\Database;
use OpenCartMcp\Core\SchemaAdapter;
use OpenCartMcp\Tools\ProductTools;
use OpenCartMcp\Core\SecurityPolicy;

echo "=== Running OpenCart Distribution Builds (ocStore, BazarCMS, Prostore) Tests ===\n\n";

// 1. Create in-memory SQLite database
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Seed tables with custom prefix 'bazar_'
$pdo->exec("
    CREATE TABLE bazar_setting (
        setting_id INTEGER PRIMARY KEY,
        store_id INTEGER DEFAULT 0,
        code TEXT,
        `key` TEXT,
        value TEXT,
        serialized INTEGER DEFAULT 0
    );
    CREATE TABLE bazar_language (
        language_id INTEGER PRIMARY KEY,
        name TEXT,
        code TEXT,
        status INTEGER DEFAULT 1
    );
    CREATE TABLE bazar_currency (
        currency_id INTEGER PRIMARY KEY,
        title TEXT,
        code TEXT,
        symbol_left TEXT,
        symbol_right TEXT,
        decimal_place INTEGER,
        value REAL,
        status INTEGER DEFAULT 1
    );
    CREATE TABLE bazar_product (
        product_id INTEGER PRIMARY KEY,
        model TEXT,
        sku TEXT,
        price REAL,
        quantity INTEGER,
        status INTEGER,
        image TEXT,
        date_available TEXT,
        date_added TEXT,
        date_modified TEXT
    );
    CREATE TABLE bazar_product_description (
        product_id INTEGER,
        language_id INTEGER,
        name TEXT,
        description TEXT,
        tag TEXT,
        meta_title TEXT,
        meta_description TEXT,
        meta_keyword TEXT
    );
    -- ocStore hallmark: main_category column!
    CREATE TABLE bazar_product_to_category (
        product_id INTEGER,
        category_id INTEGER,
        main_category INTEGER DEFAULT 0
    );
    CREATE TABLE bazar_category (
        category_id INTEGER PRIMARY KEY,
        status INTEGER DEFAULT 1
    );
    CREATE TABLE bazar_category_description (
        category_id INTEGER,
        language_id INTEGER,
        name TEXT
    );
    CREATE TABLE bazar_seo_url (
        seo_url_id INTEGER PRIMARY KEY,
        store_id INTEGER,
        language_id INTEGER,
        query TEXT,
        keyword TEXT
    );
");

// Populate settings & languages
$pdo->exec("
    INSERT INTO bazar_language (language_id, name, code) VALUES (1, 'Russian', 'ru-ru');
    INSERT INTO bazar_language (language_id, name, code) VALUES (2, 'English', 'en-gb');
    
    INSERT INTO bazar_setting (code, `key`, value, store_id) VALUES ('config', 'config_language', 'ru-ru', 0);
    INSERT INTO bazar_setting (code, `key`, value, store_id) VALUES ('config', 'config_currency', 'RUB', 0);
    INSERT INTO bazar_setting (code, `key`, value, store_id) VALUES ('config', 'config_url', 'https://bazar-demo.ru/', 0);
    INSERT INTO bazar_setting (code, `key`, value, store_id) VALUES ('config', 'config_url', 'https://second-store.ru/', 1);

    -- Currencies for CIS distributions
    INSERT INTO bazar_currency (currency_id, title, code, symbol_left, symbol_right, decimal_place, value)
    VALUES (1, 'Рубль', 'RUB', '', ' ₽', 2, 1.0);
    INSERT INTO bazar_currency (currency_id, title, code, symbol_left, symbol_right, decimal_place, value)
    VALUES (2, 'Белорусский рубль', 'BYN', '', ' руб.', 2, 0.035);
    INSERT INTO bazar_currency (currency_id, title, code, symbol_left, symbol_right, decimal_place, value)
    VALUES (3, 'Тенге', 'KZT', '₸ ', '', 0, 5.2);
    INSERT INTO bazar_currency (currency_id, title, code, symbol_left, symbol_right, decimal_place, value)
    VALUES (4, 'US Dollar', 'USD', '$', '', 2, 0.011);

    -- Products with categories
    INSERT INTO bazar_product (product_id, model, sku, price, quantity, status, image, date_added, date_modified)
    VALUES (101, 'MOD-101', 'SKU-101', 12500.0, 15, 1, 'catalog/iphone.jpg', '2026-01-01', '2026-01-02');
    INSERT INTO bazar_product_description (product_id, language_id, name)
    VALUES (101, 1, 'Смартфон Apple iPhone');

    -- Product has 3 categories, but category 50 is MAIN CATEGORY (ocStore)
    INSERT INTO bazar_product_to_category (product_id, category_id, main_category) VALUES (101, 10, 0);
    INSERT INTO bazar_product_to_category (product_id, category_id, main_category) VALUES (101, 50, 1);
    INSERT INTO bazar_product_to_category (product_id, category_id, main_category) VALUES (101, 99, 0);

    INSERT INTO bazar_seo_url (store_id, language_id, query, keyword)
    VALUES (0, 1, 'product_id=101', 'apple-iphone-pro');
");

$db = new Database(['pdo' => $pdo, 'db_prefix' => 'bazar_']);
$schema = new SchemaAdapter($db, '3.x');
$security = new SecurityPolicy(['allowMutations' => true]);
$tools = new ProductTools($db, $schema, $security);

// 1. Verify ocStore main category resolution
echo "1. Testing ocStore main_category resolution...\n";
assert($schema->hasMainCategoryColumn() === true, "Must detect main_category column");
$mainCat = $schema->getMainCategoryId(101);
assert($mainCat === 50, "Expected main_category_id to be 50, got {$mainCat}");
echo "  [PASS] ocStore main_category correctly resolved (ID 50).\n";

// 2. Verify product listing attaches ocStore main_category and formatted price
echo "2. Testing ProductTools::listProducts with ocStore metadata...\n";
$res = $tools->listProducts(['limit' => 5]);
assert(count($res['products']) === 1, "Expected 1 product");
$p = $res['products'][0];
assert($p['main_category_id'] === 50, "Product must include main_category_id: 50");
assert($p['formatted_price'] === '12 500.00 ₽', "Price must be formatted in Russian Rubles (got: {$p['formatted_price']})");
assert($p['seo_url'] === 'apple-iphone-pro', "SEO URL must match");
echo "  [PASS] listProducts returns main_category_id (50) and currency formatting ('12 500.00 ₽').\n";

// 3. Verify CIS currency formatting
echo "3. Testing CIS currency formats (RUB, BYN, KZT, USD)...\n";
assert($schema->formatCurrency(12500, 'RUB') === '12 500.00 ₽', "RUB format");
assert($schema->formatCurrency(350.5, 'BYN') === '350.50 руб.', "BYN format");
assert($schema->formatCurrency(78000, 'KZT') === '₸ 78 000', "KZT format");
assert($schema->formatCurrency(99.99, 'USD') === '$99.99', "USD format");
echo "  [PASS] All CIS currency formats verified.\n";

// 4. Verify Multi-store URL resolution
echo "4. Testing Multi-store URL resolution...\n";
$mainUrl = $schema->getStoreUrl(0);
$subUrl = $schema->getStoreUrl(1);
assert($mainUrl === 'https://bazar-demo.ru/', "Main store URL: {$mainUrl}");
assert($subUrl === 'https://second-store.ru/', "Sub-store URL: {$subUrl}");
echo "  [PASS] Multi-store URLs successfully resolved.\n";

echo "\n---------------------------------------------------------";
echo "\nALL OPENCART DISTRIBUTION BUILDS TESTS PASSED! 🎉\n";
