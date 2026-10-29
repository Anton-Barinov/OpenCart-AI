<?php
/**
 * OpenCart-AI Admin Module Controller
 * Compatible with OpenCart 2.3, 3.x, and 4.x
 * Part of OpenCart-AI by Anton Barinov <https://github.com/Anton-Barinov>
 */

class ControllerExtensionModuleOpencartAi extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/opencart_ai');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_opencart_ai', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        // Language variables
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');

        // Warnings / Errors
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/opencart_ai', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/opencart_ai', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['user_token'] = $this->session->data['user_token'];

        // Form fields
        $fields = array(
            'module_opencart_ai_status'          => 1,
            'module_opencart_ai_token'           => '',
            'module_opencart_ai_allow_mutations' => 0,
            'module_opencart_ai_mask_pii'        => 1,
            'module_opencart_ai_allowed_ips'     => '',
        );

        foreach ($fields as $key => $default) {
            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } elseif ($this->config->has($key)) {
                $data[$key] = $this->config->get($key);
            } else {
                $data[$key] = $default;
            }
        }

        // Generate token if empty
        if (empty($data['module_opencart_ai_token'])) {
            $data['module_opencart_ai_token'] = bin2hex(openssl_random_pseudo_bytes(24));
        }

        // Endpoint URL
        $catalogUrl = defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_SERVER);
        $data['endpoint_url'] = rtrim($catalogUrl, '/') . '/index.php?route=extension/module/opencart_ai/endpoint';

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

        $this->response->setOutput($this->load->view('extension/module/opencart_ai', $data));
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
        if (!$this->user->hasPermission('modify', 'extension/module/opencart_ai')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
