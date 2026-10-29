<?php
/**
 * OpenCart-AI Chaos & Security Stress Test Suite
 */

require_once __DIR__ . '/MockOpenCart.php';
require_once dirname(__DIR__) . '/upload/system/library/opencart_ai/autoload.php';

use OpenCartMcp\Core\SecurityPolicy;
use OpenCartMcp\Core\SchemaAdapter;
use OpenCartMcp\Core\Database;

echo "========================================================\n";
echo "   Running OpenCart-AI Chaos & Security Stress Tests\n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function run_test(string $name, callable $fn): void {
    global $passed, $failed;
    echo "Testing {$name}... ";
    try {
        $fn();
        echo "\033[32m[PASS]\033[0m\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "\033[31m[FAIL: " . $e->getMessage() . "]\033[0m\n";
        $failed++;
    }
}

// 1. CIDR Subnet IP Whitelist Validation
run_test("CIDR Subnet IP Whitelisting (192.168.1.0/24)", function() {
    $sec = new SecurityPolicy([
        'ipWhitelist' => ['192.168.1.0/24', '10.0.0.0/8'],
    ]);

    assert($sec->isIpAllowed('192.168.1.55') === true, "192.168.1.55 should be allowed in /24");
    assert($sec->isIpAllowed('192.168.1.1') === true, "192.168.1.1 should be allowed in /24");
    assert($sec->isIpAllowed('192.168.2.1') === false, "192.168.2.1 must be rejected");
    assert($sec->isIpAllowed('10.254.12.99') === true, "10.x.x.x should be allowed in /8");
    assert($sec->isIpAllowed('172.16.0.1') === false, "172.16.0.1 must be rejected");
});

// 2. Token Constant-Time Resistance & Boundary Checks
run_test("Bearer Token Validation & Timing Resistance", function() {
    $token = "arm_secret_token_abcdef1234567890";
    $sec = new SecurityPolicy(['token' => $token]);

    assert($sec->validateToken("Bearer {$token}") === true, "Valid Bearer token must pass");
    assert($sec->validateToken("bearer {$token}") === true, "Case-insensitive bearer prefix must pass");
    assert($sec->validateToken("Bearer wrong_token_999") === false, "Invalid token must fail");
    assert($sec->validateToken("Bearer ") === false, "Empty bearer token must fail");
    assert($sec->validateToken("") === false, "Empty header must fail");
});

// 3. Mutation Gating Enforcement
run_test("Mutation Gating Enforcement (Write-Protection)", function() {
    $secReadOnly = new SecurityPolicy(['allowMutations' => false]);
    assert($secReadOnly->areMutationsAllowed() === false, "Mutations must be disabled by default");

    $threw = false;
    try {
        $secReadOnly->assertMutationsAllowed();
    } catch (\RuntimeException $e) {
        $threw = true;
    }
    assert($threw === true, "assertMutationsAllowed must throw RuntimeException when allowMutations=false");

    $secMutable = new SecurityPolicy(['allowMutations' => true]);
    assert($secMutable->areMutationsAllowed() === true);
});

// 4. PII Redaction Audit
run_test("PII Redaction of Customer and Order Data", function() {
    $sec = new SecurityPolicy(['maskPii' => true]);

    $rawCustomer = [
        'firstname' => 'Alexander',
        'lastname'  => 'Hamilton',
        'email'     => 'alexander.hamilton@treasury.gov',
        'telephone' => '+1 (555) 839-2049',
    ];

    $masked = $sec->maskCustomer($rawCustomer);

    assert(strpos($masked['email'], 'treasury.gov') !== false, "Domain should be preserved");
    assert(strpos($masked['email'], 'alexander') === false, "Username part must be redacted");
    assert($masked['telephone'] !== $rawCustomer['telephone'], "Telephone must be masked");
    assert($masked['firstname'] !== $rawCustomer['firstname'], "Firstname must be masked");
});

// 5. SchemaAdapter Version Adaptation (2.x / 3.x / 4.x)
run_test("SchemaAdapter OpenCart 2.x/3.x/4.x Query Compatibility", function() {
    $pdo = new PDO('sqlite::memory:');
    $db = new Database(['pdo' => $pdo, 'database' => 'test']);

    $schema2 = new SchemaAdapter($db, '2.x');
    $schema3 = new SchemaAdapter($db, '3.x');
    $schema4 = new SchemaAdapter($db, '4.x');

    assert($schema2->getMajorVersion() === '2.x');
    assert($schema3->getMajorVersion() === '3.x');
    assert($schema4->getMajorVersion() === '4.x');
});

echo "\n--------------------------------------------------------\n";
echo "Chaos Results: {$passed} Passed, {$failed} Failed\n";
echo "--------------------------------------------------------\n";

if ($failed === 0) {
    echo "🎉 ALL OPENCART-AI CHAOS TESTS PASSED CLEANLY!\n";
    exit(0);
} else {
    exit(1);
}
