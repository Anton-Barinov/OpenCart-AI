<?php
/**
 * Multi-Version OpenCart Admin Controller Verification
 * Tests compatibility across:
 * - OpenCart 2.0 - 2.2 (Legacy tokens, extension/module, .tpl)
 * - OpenCart 2.3 (token, extension/extension, .tpl)
 * - OpenCart 3.0 (user_token, marketplace/extension, .twig)
 * - OpenCart 4.0 (Namespaced, user_token, extension routes)
 *
 * @author Anton Barinov <https://github.com/Anton-Barinov>
 * @license MIT
 */

require_once __DIR__ . '/MockOpenCart.php';
require_once __DIR__ . '/MockOpenCart4.php';
require_once dirname(__DIR__) . '/upload/admin/controller/extension/module/opencart_ai.php';
require_once dirname(__DIR__) . '/upload/admin/controller/module/opencart_ai.php';
require_once dirname(__DIR__) . '/upload/extension/opencart_ai/admin/controller/module/opencart_ai.php';

echo "=== Running Multi-Version OpenCart Compatibility Tests ===\n\n";

// -----------------------------------------------------------------------------
// 1. OpenCart 2.0 - 2.2 Environment Simulation
// -----------------------------------------------------------------------------
echo "1. Testing OpenCart 2.0 - 2.2 Simulation...\n";
$oc2Controller = new ControllerModuleOpencartAi();
$oc2Controller->session->data = ['token' => 'oc2_token_abc123']; // Legacy 'token'
$oc2Controller->request->server['REQUEST_METHOD'] = 'POST';
$oc2Controller->request->post = [
    'module_opencart_ai_status' => '1',
    'module_opencart_ai_token'  => 'token_for_oc2_store'
];

$oc2Controller->index();

// Check redirect URL uses legacy token parameter
assert($oc2Controller->response->redirectUrl !== null, 'OC2: Redirect URL must not be null');
assert(strpos($oc2Controller->response->redirectUrl, 'token=oc2_token_abc123') !== false, 'OC2: Token must be in redirect URL');
// Check settings saved under opencart_ai
$oc2Saved = $oc2Controller->model_setting_setting->getSetting('opencart_ai');
assert(isset($oc2Saved['opencart_ai_token']) && $oc2Saved['opencart_ai_token'] === 'token_for_oc2_store', 'OC2: opencart_ai_token must be saved');
echo "  [PASS] OpenCart 2.0-2.2 token resolution, settings saving, and redirection verified.\n";

// -----------------------------------------------------------------------------
// 2. OpenCart 2.3 Environment Simulation
// -----------------------------------------------------------------------------
echo "2. Testing OpenCart 2.3 Environment Simulation...\n";
$oc23Controller = new ControllerExtensionModuleOpencartAi();
$oc23Controller->session->data = ['token' => 'oc23_session_token']; // OC 2.3 uses 'token'
$oc23Controller->request->server['REQUEST_METHOD'] = 'POST';
$oc23Controller->request->post = [
    'module_opencart_ai_status' => '1',
    'module_opencart_ai_token'  => 'token_oc23_production'
];

$oc23Controller->index();

assert(strpos($oc23Controller->response->redirectUrl, 'token=oc23_session_token') !== false, 'OC2.3: token parameter preserved');
$oc23ModuleSaved = $oc23Controller->model_setting_setting->getSetting('module_opencart_ai');
$oc23LegacySaved = $oc23Controller->model_setting_setting->getSetting('opencart_ai');
assert(!empty($oc23ModuleSaved['module_opencart_ai_token']), 'OC2.3: module_opencart_ai setting saved');
assert(!empty($oc23LegacySaved['opencart_ai_token']), 'OC2.3: opencart_ai legacy sync setting saved');
echo "  [PASS] OpenCart 2.3 dual settings sync and token compatibility verified.\n";

// -----------------------------------------------------------------------------
// 3. OpenCart 3.0 Environment Simulation
// -----------------------------------------------------------------------------
echo "3. Testing OpenCart 3.0 Environment Simulation...\n";
$oc3Controller = new ControllerExtensionModuleOpencartAi();
$oc3Controller->session->data = ['user_token' => 'oc3_user_token_xyz999']; // OC 3 uses 'user_token'
$oc3Controller->request->server['REQUEST_METHOD'] = 'POST';
$oc3Controller->request->post = [
    'module_opencart_ai_status'          => '1',
    'module_opencart_ai_token'           => 'token_oc3_live_store',
    'module_opencart_ai_allow_mutations' => '0',
    'module_opencart_ai_mask_pii'        => '1'
];

$oc3Controller->index();

assert(strpos($oc3Controller->response->redirectUrl, 'marketplace/extension') !== false, 'OC3: Route marketplace/extension');
assert(strpos($oc3Controller->response->redirectUrl, 'user_token=oc3_user_token_xyz999') !== false, 'OC3: user_token in redirect');
$oc3Saved = $oc3Controller->model_setting_setting->getSetting('module_opencart_ai');
assert($oc3Saved['module_opencart_ai_token'] === 'token_oc3_live_store', 'OC3: token matches');
echo "  [PASS] OpenCart 3.0 user_token and marketplace/extension routing verified.\n";

// -----------------------------------------------------------------------------
// 4. OpenCart 4.x Namespaced Controller Simulation
// -----------------------------------------------------------------------------
echo "4. Testing OpenCart 4.x Namespaced Controller Simulation...\n";
$oc4Controller = new \Opencart\Admin\Controller\Extension\OpencartAi\Module\OpencartAi();
$oc4Controller->session->data = ['user_token' => 'oc4_token_hash_444'];
$oc4Controller->request->server['REQUEST_METHOD'] = 'POST';
$oc4Controller->request->post = [
    'module_opencart_ai_status' => '1',
    'module_opencart_ai_token'  => 'oc4_bearer_secure'
];

$oc4Controller->index();

assert(strpos($oc4Controller->response->redirectUrl, 'marketplace/extension') !== false, 'OC4: Marketplace extension route');
assert(strpos($oc4Controller->response->redirectUrl, 'user_token=oc4_token_hash_444') !== false, 'OC4: user_token parameter');
$oc4Saved = $oc4Controller->model_setting_setting->getSetting('module_opencart_ai');
assert($oc4Saved['module_opencart_ai_token'] === 'oc4_bearer_secure', 'OC4: setting saved');

echo "  [PASS] OpenCart 4.x namespaced controller executed and validated cleanly.\n";

echo "\n---------------------------------------------------------";
echo "\nALL MULTI-VERSION OPENCART COMPATIBILITY TESTS PASSED! 🎉\n";
