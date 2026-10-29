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

// 6. Garbage Data Fuzzing & Malformed JSON-RPC Requests
run_test("Garbage Data Fuzzing & Protocol Robustness", function() {
    $server = new \OpenCartMcp\Protocol\McpServer(new SecurityPolicy());

    // 1. Empty string
    $r1 = json_decode($server->handleRaw(''), true);
    assert($r1['error']['code'] === -32700, "Empty input must return parse error -32700");

    // 2. Corrupted JSON syntax
    $r2 = json_decode($server->handleRaw('{"jsonrpc": "2.0", "broken'), true);
    assert($r2['error']['code'] === -32700, "Corrupted JSON must return parse error -32700");

    // 3. Method property is not a string
    $r3 = json_decode($server->handleRaw('{"jsonrpc": "2.0", "id": 1, "method": 999}'), true);
    assert($r3['error']['code'] === -32600, "Non-string method must return invalid request -32600");

    // 4. Non-existent method call
    $r4 = json_decode($server->handleRaw('{"jsonrpc": "2.0", "id": 42, "method": "ghost_unknown_tool"}'), true);
    assert($r4['error']['code'] === -32601, "Unknown method must return method not found -32601");

    // 5. Empty batch array
    $r5 = json_decode($server->handleRaw('[]'), true);
    assert($r5['error']['code'] === -32600, "Empty batch must return invalid request -32600");

    // 6. Extreme negative ID & type confusion in params
    $r6 = json_decode($server->handleRaw('{"jsonrpc": "2.0", "id": -999999999, "method": "ping", "params": "string_instead_of_object"}'), true);
    assert($r6['id'] === -999999999, "Extreme negative ID must be preserved in response");
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
