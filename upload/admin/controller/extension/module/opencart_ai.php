<?php
/**
 * OpenCart-AI Admin Module Controller
 * Compatible with OpenCart 2.0, 2.1, 2.2, 2.3, 3.x, and 4.x
 * Supports official OpenCart, ocStore, BazarCMS, Prostore, OpenCart.pro
 *
 * @author Anton Barinov <https://github.com/Anton-Barinov>
 * @license MIT
 */

class ControllerExtensionModuleOpencartAi extends Controller
{
    private $error = array();

    public function index()
    {
        // 1. Language Loading with legacy fallback
        if (version_compare(defined('VERSION') ? VERSION : '3.0.0.0', '2.3.0.0', '<')) {
            $this->load->language('module/opencart_ai');
        } else {
            $this->load->language('extension/module/opencart_ai');
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        // 2. Token resolution across OpenCart versions
        $tokenKey = isset($this->session->data['user_token']) ? 'user_token' : (isset($this->session->data['token']) ? 'token' : 'user_token');
        $tokenValue = isset($this->session->data[$tokenKey]) ? $this->session->data[$tokenKey] : '';
        $tokenParam = $tokenKey . '=' . $tokenValue;

        // 3. Extension list link resolution
        if ($tokenKey === 'user_token') {
            // OpenCart 3.0+ / 4.0+
            $extRoute = 'marketplace/extension';
            $extArgs = $tokenParam . '&type=module';
            $moduleRoute = 'extension/module/opencart_ai';
        } elseif (version_compare(defined('VERSION') ? VERSION : '2.3.0.0', '2.3.0.0', '>=')) {
            // OpenCart 2.3.x
            $extRoute = 'extension/extension';
            $extArgs = $tokenParam . '&type=module';
            $moduleRoute = 'extension/module/opencart_ai';
        } else {
            // OpenCart 2.0 - 2.2
            $extRoute = 'extension/module';
            $extArgs = $tokenParam;
            $moduleRoute = 'module/opencart_ai';
        }

        // 4. Save settings on POST
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            // OpenCart 3.x / 4.x format (module_opencart_ai_*)
            $this->model_setting_setting->editSetting('module_opencart_ai', $this->request->post);

            // OpenCart 2.x format (opencart_ai_*)
            $oc2Settings = array();
            foreach ($this->request->post as $key => $val) {
                $oc2Key = (strpos($key, 'module_opencart_ai_') === 0) ? substr($key, 7) : $key;
                $oc2Settings[$oc2Key] = $val;
                $oc2Settings[$key] = $val;
            }
            $this->model_setting_setting->editSetting('opencart_ai', $oc2Settings);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link($extRoute, $extArgs, true));
        }

        // 5. Language strings
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        // Warnings / Errors
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $tokenParam, true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($extRoute, $extArgs, true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link($moduleRoute, $tokenParam, true)
        );

        $data['action'] = $this->url->link($moduleRoute, $tokenParam, true);
        $data['cancel'] = $this->url->link($extRoute, $extArgs, true);
        $data['token_key'] = $tokenKey;
        $data['token_value'] = $tokenValue;
        $data['token_param'] = $tokenParam;

        // Form fields with dual fallback (module_opencart_ai_* and opencart_ai_*)
        $fields = array(
            'module_opencart_ai_status'          => 1,
            'module_opencart_ai_token'           => '',
            'module_opencart_ai_allow_mutations' => 0,
            'module_opencart_ai_mask_pii'        => 1,
            'module_opencart_ai_allowed_ips'     => '',
        );

        foreach ($fields as $key => $default) {
            $legacyKey = (strpos($key, 'module_') === 0) ? substr($key, 7) : $key;

            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } elseif ($this->config->has($key)) {
                $data[$key] = $this->config->get($key);
            } elseif ($this->config->has($legacyKey)) {
                $data[$key] = $this->config->get($legacyKey);
            } else {
                $data[$key] = $default;
            }
        }

        // Generate cryptographically secure token if empty
        if (empty($data['module_opencart_ai_token'])) {
            $data['module_opencart_ai_token'] = bin2hex(openssl_random_pseudo_bytes(24));
        }

        // Endpoint URL resolution
        $catalogUrl = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTPS_CATALOG') ? HTTPS_CATALOG : (defined('HTTP_SERVER') ? HTTP_SERVER : '/'));
        if (version_compare(defined('VERSION') ? VERSION : '3.0.0.0', '2.3.0.0', '<')) {
            $data['endpoint_url'] = rtrim($catalogUrl, '/') . '/index.php?route=module/opencart_ai/endpoint';
        } else {
            $data['endpoint_url'] = rtrim($catalogUrl, '/') . '/index.php?route=extension/module/opencart_ai/endpoint';
        }

        // Claude Desktop JSON snippet
        $claudeConfig = array(
            'mcpServers' => array(
                'opencart-store' => array(
                    'command' => 'npx',
                    'args'    => array(
                        '-y',
                        '@modelcontextprotocol/server-fetch',
                        $data['endpoint_url']
                    ),
                    'env'     => array(
                        'OPENCART_BEARER_TOKEN' => $data['module_opencart_ai_token']
                    )
                )
            )
        );
        $data['claude_snippet'] = json_encode($claudeConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Header / Column / Footer
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Render appropriate template (Twig for OC 3.x/4.x, TPL for OC 2.x)
        $hasTpl = defined('DIR_TEMPLATE') && file_exists(DIR_TEMPLATE . 'extension/module/opencart_ai.tpl');
        $isTwig = (defined('VERSION') && version_compare(VERSION, '3.0.0.0', '>=')) || !$hasTpl;
        if ($isTwig) {
            $this->response->setOutput($this->load->view('extension/module/opencart_ai', $data));
        } else {
            $tpl = (version_compare(defined('VERSION') ? VERSION : '2.3.0.0', '2.3.0.0', '<')) ? 'module/opencart_ai.tpl' : 'extension/module/opencart_ai.tpl';
            $this->response->setOutput($this->load->view($tpl, $data));
        }
    }

    public function generateToken()
    {
        $json = array();
        if ($this->validate()) {
            $json['token'] = bin2hex(openssl_random_pseudo_bytes(24));
            $json['status'] = 'success';
        } else {
            $json['status'] = 'error';
            $json['message'] = $this->language->get('error_permission');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validate()
    {
        $permRoute = (version_compare(defined('VERSION') ? VERSION : '3.0.0.0', '2.3.0.0', '<')) ? 'module/opencart_ai' : 'extension/module/opencart_ai';
        if (!$this->user->hasPermission('modify', $permRoute) && !$this->user->hasPermission('modify', 'extension/module/opencart_ai')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
