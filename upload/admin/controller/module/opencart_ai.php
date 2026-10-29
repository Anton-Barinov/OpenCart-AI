<?php
/**
 * OpenCart-AI Admin Module Controller (OpenCart 2.0.x - 2.2.x Legacy Architecture)
 * Part of OpenCart-AI by Anton Barinov <https://github.com/Anton-Barinov>
 * @license MIT
 */

if (!class_exists('ControllerExtensionModuleOpencartAi')) {
    $parent = DIR_APPLICATION . 'controller/extension/module/opencart_ai.php';
    if (file_exists($parent)) {
        require_once $parent;
    }
}

if (class_exists('ControllerExtensionModuleOpencartAi')) {
    class ControllerModuleOpencartAi extends ControllerExtensionModuleOpencartAi {}
} else {
    class ControllerModuleOpencartAi extends Controller
    {
        public function index()
        {
            $this->load->language('module/opencart_ai');
            $this->document->setTitle($this->language->get('heading_title'));
            $this->load->model('setting/setting');

            $token = isset($this->session->data['token']) ? $this->session->data['token'] : '';

            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
                $this->model_setting_setting->editSetting('opencart_ai', $this->request->post);
                $this->session->data['success'] = $this->language->get('text_success');
                $this->response->redirect($this->url->link('extension/module', 'token=' . $token, true));
            }

            $data['heading_title'] = $this->language->get('heading_title');
            $data['text_edit'] = $this->language->get('text_edit');
            $data['action'] = $this->url->link('module/opencart_ai', 'token=' . $token, true);
            $data['cancel'] = $this->url->link('extension/module', 'token=' . $token, true);
            $data['token_param'] = 'token=' . $token;

            $this->response->setOutput($this->load->view('module/opencart_ai.tpl', $data));
        }

        protected function validate()
        {
            return $this->user->hasPermission('modify', 'module/opencart_ai');
        }
    }
}
