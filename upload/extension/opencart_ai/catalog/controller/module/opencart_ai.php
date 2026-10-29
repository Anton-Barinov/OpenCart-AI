<?php
namespace Opencart\Catalog\Controller\Extension\OpencartAi\Module;

/**
 * OpenCart-AI Catalog Endpoint Controller (OpenCart 4.x Namespaced Architecture)
 * Route: index.php?route=extension/opencart_ai/module/opencart_ai|endpoint
 *
 * @author Anton Barinov <https://github.com/Anton-Barinov>
 * @license MIT
 */
class OpencartAi extends \Opencart\System\Engine\Controller
{
    public function endpoint(): void
    {
        $this->index();
    }

    public function index(): void
    {
        $autoload = DIR_SYSTEM . 'library/opencart_ai/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        $status = $this->config->get('module_opencart_ai_status');
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

        $token = (string)$this->config->get('module_opencart_ai_token');
        $allowMutations = (bool)$this->config->get('module_opencart_ai_allow_mutations');
        $maskPii = (bool)$this->config->get('module_opencart_ai_mask_pii');

        $whitelistStr = (string)$this->config->get('module_opencart_ai_allowed_ips');
        $ipWhitelist = [];
        if (!empty($whitelistStr)) {
            $ips = preg_split('/[\r\n,]+/', $whitelistStr);
            foreach ($ips as $ip) {
                $trimmed = trim($ip);
                if (!empty($trimmed)) {
                    $ipWhitelist[] = $trimmed;
                }
            }
        }

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
            'ip_whitelist'    => $ipWhitelist,
        ];

        try {
            $security = new \OpenCartMcp\Core\SecurityPolicy([
                'maskPii'        => $maskPii,
                'allowMutations' => $allowMutations,
                'token'          => $token,
                'ipWhitelist'    => $ipWhitelist,
            ]);

            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (!$security->isIpAllowed($clientIp)) {
                $this->response->addHeader('HTTP/1.0 403 Forbidden');
                $this->response->addHeader('Content-Type: application/json; charset=utf-8');
                $this->response->setOutput(json_encode([
                    'jsonrpc' => '2.0',
                    'error'   => ['code' => -32001, 'message' => 'Forbidden: Client IP not in whitelist: ' . $clientIp],
                    'id'      => null
                ]));
                return;
            }

            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
            if (empty($authHeader) && function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                $authHeader = $headers['Authorization'] ?? '';
            }
            if (empty($authHeader) && isset($_GET['token'])) {
                $authHeader = 'Bearer ' . $_GET['token'];
            }

            if (!$security->validateToken($authHeader)) {
                $this->response->addHeader('HTTP/1.0 401 Unauthorized');
                $this->response->addHeader('WWW-Authenticate: Bearer realm="OpenCart-AI MCP"');
                $this->response->addHeader('Content-Type: application/json; charset=utf-8');
                $this->response->setOutput(json_encode([
                    'jsonrpc' => '2.0',
                    'error'   => ['code' => -32002, 'message' => 'Unauthorized: Invalid or missing Bearer token.'],
                    'id'      => null
                ]));
                return;
            }

            $server = \OpenCartMcp\Factory::create($options);

            $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
            if ($method === 'GET') {
                $this->response->addHeader('Content-Type: application/json; charset=utf-8');
                $this->response->setOutput(json_encode([
                    'status'          => 'online',
                    'server'          => 'OpenCart-AI MCP Bridge',
                    'version'         => '1.0.0',
                    'opencart_compat' => '4.x',
                    'tools_count'     => count($server->getTools()),
                    'prompts_count'   => count($server->getPrompts()),
                    'mutations'       => $allowMutations ? 'enabled' : 'disabled',
                    'pii_masking'     => $maskPii ? 'enabled' : 'disabled',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return;
            }

            $transport = new \OpenCartMcp\Transport\HttpTransport($server);
            $transport->handle();
        } catch (\Exception $e) {
            $this->response->addHeader('HTTP/1.0 500 Internal Server Error');
            $this->response->addHeader('Content-Type: application/json; charset=utf-8');
            $this->response->setOutput(json_encode([
                'jsonrpc' => '2.0',
                'error'   => ['code' => -32603, 'message' => 'Internal MCP Bridge Exception: ' . $e->getMessage()],
                'id'      => null
            ]));
        }
    }
}
