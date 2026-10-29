<?php
/**
 * Admin Controller Unit Tests
 */

require_once __DIR__ . '/MockOpenCart.php';
require_once dirname(__DIR__) . '/upload/admin/controller/extension/module/opencart_ai.php';

class AdminControllerTest
{
    public function testIndexRendersTemplateWithGeneratedToken()
    {
        $controller = new ControllerExtensionModuleOpencartAi();
        $controller->index();

        $output = $controller->response->getOutput();
        assert(strpos($output, 'extension/module/opencart_ai') !== false, "Expected view rendering");
        echo "  ✓ testIndexRendersTemplateWithGeneratedToken passed\n";
    }

    public function testGenerateTokenAjaxReturnsHex()
    {
        $controller = new ControllerExtensionModuleOpencartAi();
        $controller->generateToken();

        $output = json_decode($controller->response->getOutput(), true);
        assert(isset($output['status']) && $output['status'] === 'success', "Expected status success");
        assert(isset($output['token']) && strlen($output['token']) === 48, "Expected 48-char hex token (24 bytes)");
        echo "  ✓ testGenerateTokenAjaxReturnsHex passed\n";
    }

    public function testSaveSettingsOnPost()
    {
        $controller = new ControllerExtensionModuleOpencartAi();
        $controller->request->server['REQUEST_METHOD'] = 'POST';
        $controller->request->post = [
            'module_opencart_ai_status' => '1',
            'module_opencart_ai_token' => 'my_custom_token_12345',
            'module_opencart_ai_allow_mutations' => '1',
            'module_opencart_ai_mask_pii' => '1',
            'module_opencart_ai_allowed_ips' => '127.0.0.1, 192.168.1.1'
        ];

        $controller->index();

        $saved = $controller->model_setting_setting->getSetting('module_opencart_ai');
        assert(isset($saved['module_opencart_ai_token']) && $saved['module_opencart_ai_token'] === 'my_custom_token_12345', "Expected token saved");
        assert($controller->response->redirectUrl !== null, "Expected redirect after saving");
        echo "  ✓ testSaveSettingsOnPost passed\n";
    }

    public function testPermissionDeniedBlocksSave()
    {
        $controller = new ControllerExtensionModuleOpencartAi();
        $controller->user->canModify = false;
        $controller->request->server['REQUEST_METHOD'] = 'POST';
        $controller->request->post = [
            'module_opencart_ai_token' => 'hacked_token'
        ];

        $controller->index();

        $saved = $controller->model_setting_setting->getSetting('module_opencart_ai');
        assert(empty($saved), "Expected no settings saved when permission is denied");
        echo "  ✓ testPermissionDeniedBlocksSave passed\n";
    }
}

if (basename(__FILE__) === basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '')) {
    echo "=== Admin Controller Tests ===\n";
    $test = new AdminControllerTest();
    $test->testIndexRendersTemplateWithGeneratedToken();
    $test->testGenerateTokenAjaxReturnsHex();
    $test->testSaveSettingsOnPost();
    $test->testPermissionDeniedBlocksSave();
    echo "Admin controller tests complete.\n";
}
