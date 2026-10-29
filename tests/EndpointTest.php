<?php
/**
 * Catalog Endpoint Controller Tests
 */

require_once __DIR__ . '/MockOpenCart.php';
require_once dirname(__DIR__) . '/upload/catalog/controller/extension/module/opencart_ai.php';

class EndpointTest
{
    public function testDisabledModuleReturns503()
    {
        $controller = new ControllerExtensionModuleOpencartAi();
        $controller->config->set('module_opencart_ai_status', '0');

        $controller->index();

        $output = json_decode($controller->response->getOutput(), true);
        assert(isset($output['error']), "Expected error when module is disabled");
        assert($output['error']['code'] === -32000, "Expected error code -32000 for disabled module");
        assert(in_array('HTTP/1.1 503 Service Unavailable', $controller->response->headers), "Expected 503 header");
        echo "  ✓ testDisabledModuleReturns503 passed\n";
    }

    public function testUnauthorizedWithoutTokenReturns401()
    {
        $controller = new ControllerExtensionModuleOpencartAi();
        $controller->config->set('module_opencart_ai_status', '1');
        $controller->config->set('module_opencart_ai_token', 'test_secret_token_123');

        unset($_SERVER['HTTP_AUTHORIZATION']);
        unset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        unset($_GET['token']);

        $controller->index();

        $output = json_decode($controller->response->getOutput(), true);
        assert(isset($output['error']), "Expected error when token is missing");
        assert($output['error']['code'] === -32002, "Expected error code -32002 for unauthorized");
        assert(in_array('HTTP/1.1 401 Unauthorized', $controller->response->headers), "Expected 401 header");
        echo "  ✓ testUnauthorizedWithoutTokenReturns401 passed\n";
    }

    public function testIpWhitelistRejectionReturns403()
    {
        $controller = new ControllerExtensionModuleOpencartAi();
        $controller->config->set('module_opencart_ai_status', '1');
        $controller->config->set('module_opencart_ai_token', 'test_secret_token_123');
        $controller->config->set('module_opencart_ai_ip_whitelist', '10.0.0.1, 10.0.0.2');

        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test_secret_token_123';

        $controller->index();

        $output = json_decode($controller->response->getOutput(), true);
        assert(isset($output['error']), "Expected error when IP is not in whitelist");
        assert($output['error']['code'] === -32001, "Expected error code -32001 for forbidden IP");
        assert(in_array('HTTP/1.1 403 Forbidden', $controller->response->headers), "Expected 403 header");
        echo "  ✓ testIpWhitelistRejectionReturns403 passed\n";
    }

    public function testGetRequestReturnsStatusMetadata()
    {
        $controller = new ControllerExtensionModuleOpencartAi();
        $controller->pdo = new \PDO('sqlite::memory:');
        $controller->config->set('module_opencart_ai_status', '1');
        $controller->config->set('module_opencart_ai_token', 'test_token');
        $controller->config->set('module_opencart_ai_ip_whitelist', '');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test_token';

        $controller->index();

        $output = json_decode($controller->response->getOutput(), true);
        assert(isset($output['status']) && $output['status'] === 'online', "Expected online status");
        assert($output['server'] === 'OpenCart-AI MCP Bridge', "Expected server name");
        assert($output['tools_available'] > 0, "Expected registered tools");
        assert($output['prompts'] > 0, "Expected registered prompts");
        assert($output['resources'] > 0, "Expected registered resources");
        echo "  ✓ testGetRequestReturnsStatusMetadata passed (tools: {$output['tools_available']}, prompts: {$output['prompts']})\n";
    }
}

if (basename(__FILE__) === basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '')) {
    echo "=== Endpoint Tests ===\n";
    $test = new EndpointTest();
    $test->testDisabledModuleReturns503();
    $test->testUnauthorizedWithoutTokenReturns401();
    $test->testIpWhitelistRejectionReturns403();
    $test->testGetRequestReturnsStatusMetadata();
    echo "Endpoint tests complete.\n";
}
