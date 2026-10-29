<?php
/**
 * OpenCart-AI Catalog Endpoint Controller
 * Handles incoming MCP JSON-RPC requests from Claude Desktop, Cursor, ChatGPT, etc.
 *
 * @author Anton Barinov <https://github.com/Anton-Barinov>
 * @license MIT
 */

class ControllerExtensionModuleOpencartAi extends Controller
{
    /**
     * Alternative endpoint action
     */
    public function endpoint()
    {
        $this->index();
    }

    /**
     * Main JSON-RPC 2.0 MCP gateway
     */
    public function index()
    {
        // 1. Require MCP autoload
        $autoload = DIR_SYSTEM . 'library/opencart_ai/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        // 2. Check if OpenCart-AI module is enabled in admin
        $status = $this->config->get('module_opencart_ai_status');
        if (!$status && $status !== '1' && $status !== 1) {
            $this->sendJsonError(503, -32000, 'OpenCart-AI MCP bridge is currently disabled in store admin.');
            return;
        }

        $token = $this->config->get('module_opencart_ai_token');
        $allowMutations = (bool)$this->config->get('module_opencart_ai_allow_mutations');
        $maskPii = (bool)$this->config->get('module_opencart_ai_mask_pii');

        $whitelistStr = (string)$this->config->get('module_opencart_ai_ip_whitelist');
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

        // 3. Configure Database connection and security policy
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

        if (isset($this->pdo) && $this->pdo instanceof \PDO) {
            $options['pdo'] = $this->pdo;
        }

        try {
            $security = new \OpenCartMcp\Core\SecurityPolicy([
                'maskPii'        => $maskPii,
                'allowMutations' => $allowMutations,
                'token'          => $token,
                'ipWhitelist'    => $ipWhitelist,
            ]);

            // 4. IP whitelist enforcement
            $clientIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            if (!$security->isIpAllowed($clientIp)) {
                $this->sendJsonError(403, -32001, 'Forbidden: Client IP not in whitelist: ' . $clientIp);
                return;
            }

            // 5. Bearer Token Authentication
            $authHeader = '';
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                if (isset($headers['Authorization'])) {
                    $authHeader = $headers['Authorization'];
                }
            }

            if (empty($authHeader) && isset($_GET['token'])) {
                $authHeader = 'Bearer ' . $_GET['token'];
            }

            if (!$security->validateToken($authHeader)) {
                $this->response->addHeader('WWW-Authenticate: Bearer realm="OpenCart-AI MCP"');
                $this->sendJsonError(401, -32002, 'Unauthorized: Invalid or missing Bearer token.');
                return;
            }

            $server = \OpenCartMcp\Factory::create($options);

            // 6. Handle GET requests with MCP Bridge status metadata
            $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
            if ($method === 'GET') {
                $this->response->addHeader('Content-Type: application/json; charset=utf-8');
                $this->response->setOutput(json_encode([
                    'status'          => 'online',
                    'server'          => 'OpenCart-AI MCP Bridge',
                    'version'         => '1.0.0',
                    'tools_available' => count($server->getTools()),
                    'prompts'         => count($server->getPrompts()),
                    'resources'       => count($server->getResources()),
                    'instructions'    => 'Send JSON-RPC 2.0 requests via HTTP POST with Authorization: Bearer <token>'
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return;
            }

            // 7. Handle JSON-RPC POST payload
            $rawInput = file_get_contents('php://input');
            if (empty($rawInput) && !empty($this->request->post)) {
                $rawInput = json_encode($this->request->post);
            }

            $response = $server->handleRaw($rawInput);

            $this->response->addHeader('Content-Type: application/json; charset=utf-8');
            $this->response->addHeader('Access-Control-Allow-Origin: *');
            $this->response->setOutput($response !== null ? $response : '');

        } catch (\Exception $e) {
            $this->sendJsonError(500, -32603, 'Internal MCP Error: ' . $e->getMessage());
        }
    }

    /**
     * Send standardized JSON-RPC error response
     *
     * @param int $httpStatus
     * @param int $code
     * @param string $message
     */
    private function sendJsonError($httpStatus, $code, $message)
    {
        $statusHeaders = [
            401 => 'HTTP/1.1 401 Unauthorized',
            403 => 'HTTP/1.1 403 Forbidden',
            500 => 'HTTP/1.1 500 Internal Server Error',
            503 => 'HTTP/1.1 503 Service Unavailable',
        ];

        if (isset($statusHeaders[$httpStatus])) {
            $this->response->addHeader($statusHeaders[$httpStatus]);
        }
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode([
            'jsonrpc' => '2.0',
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
            'id'      => null
        ], JSON_PRETTY_PRINT));
    }
}
