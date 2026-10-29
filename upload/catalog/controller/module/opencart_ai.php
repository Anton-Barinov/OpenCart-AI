<?php
/**
 * OpenCart-AI Catalog Endpoint Controller (OpenCart 2.0.x - 2.2.x Legacy Architecture)
 * Route: index.php?route=module/opencart_ai/endpoint
 *
 * @author Anton Barinov <https://github.com/Anton-Barinov>
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
        public function endpoint()
        {
            $this->index();
        }

        public function index()
        {
            $autoload = DIR_SYSTEM . 'library/opencart_ai/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }

            $status = $this->config->get('opencart_ai_status');
            if (!$status) {
                $status = $this->config->get('module_opencart_ai_status');
            }

            if (!$status && $status !== '1' && $status !== 1) {
                $this->response->addHeader('HTTP/1.0 503 Service Unavailable');
                $this->response->addHeader('Content-Type: application/json; charset=utf-8');
                $this->response->setOutput(json_encode([
                    'jsonrpc' => '2.0',
                    'error'   => ['code' => -32000, 'message' => 'OpenCart-AI MCP bridge is currently disabled in store admin.'],
                    'id'      => null
                ]));
                return;
            }

            // Run standard MCP server
            $token = $this->config->get('opencart_ai_token') ?: $this->config->get('module_opencart_ai_token');
            $allowMutations = (bool)($this->config->get('opencart_ai_allow_mutations') ?: $this->config->get('module_opencart_ai_allow_mutations'));
            $maskPii = (bool)($this->config->get('opencart_ai_mask_pii') !== null ? $this->config->get('opencart_ai_mask_pii') : true);

            $options = [
                'root'            => defined('DIR_CATALOG') ? dirname(DIR_CATALOG) : (defined('DIR_APPLICATION') ? dirname(DIR_APPLICATION) : getcwd()),
                'db_host'         => defined('DB_HOSTNAME') ? DB_HOSTNAME : 'localhost',
                'db_user'         => defined('DB_USERNAME') ? DB_USERNAME : 'root',
                'db_password'     => defined('DB_PASSWORD') ? DB_PASSWORD : '',
                'db_name'         => defined('DB_DATABASE') ? DB_DATABASE : '',
                'db_port'         => defined('DB_PORT') ? (int)DB_PORT : 3306,
                'db_prefix'       => defined('DB_PREFIX') ? DB_PREFIX : 'oc_',
                'token'           => $token,
                'allow_mutations' => $allowMutations,
                'mask_pii'        => $maskPii,
            ];

            $server = \OpenCartMcp\Factory::create($options);
            $transport = new \OpenCartMcp\Transport\HttpTransport($server);
            $transport->handle();
        }
    }
}
