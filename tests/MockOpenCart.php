<?php
/**
 * Mock OpenCart Framework Environment for Unit Testing
 */

if (!defined('DIR_APPLICATION')) {
    define('DIR_APPLICATION', dirname(__DIR__) . '/upload/catalog/');
}
if (!defined('DIR_SYSTEM')) {
    define('DIR_SYSTEM', dirname(__DIR__) . '/upload/system/');
}
if (!defined('DIR_CATALOG')) {
    define('DIR_CATALOG', dirname(__DIR__) . '/upload/catalog/');
}
if (!defined('HTTP_SERVER')) {
    define('HTTP_SERVER', 'https://shop.example.com/');
}
if (!defined('HTTP_CATALOG')) {
    define('HTTP_CATALOG', 'https://shop.example.com/');
}
if (!defined('DB_HOSTNAME')) {
    define('DB_HOSTNAME', '127.0.0.1');
}
if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', 'test');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', 'test');
}
if (!defined('DB_DATABASE')) {
    define('DB_DATABASE', 'test_db');
}
if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', 'oc_');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', 3306);
}

class MockConfig
{
    private $data = [];

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }
}

class MockResponse
{
    public $headers = [];
    public $output = '';
    public $redirectUrl = null;

    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function redirect($url)
    {
        $this->redirectUrl = $url;
    }
}

class MockRequest
{
    public $get = [];
    public $post = [];
    public $server = ['REQUEST_METHOD' => 'GET'];
}

class MockSession
{
    public $data = [
        'user_token' => 'dummy_token_123'
    ];
}

class MockUser
{
    public $canModify = true;

    public function hasPermission($type, $key)
    {
        if ($type === 'modify') {
            return $this->canModify;
        }
        return true;
    }
}

class MockUrl
{
    public function link($route, $args = '', $secure = true)
    {
        return "https://shop.example.com/admin/index.php?route={$route}&{$args}";
    }
}

class MockLanguage
{
    private $data = [
        'heading_title' => 'OpenCart-AI MCP Assistant',
        'text_edit' => 'Edit Settings',
        'text_enabled' => 'Enabled',
        'text_disabled' => 'Disabled',
        'text_yes' => 'Yes',
        'text_no' => 'No',
        'text_home' => 'Home',
        'text_extension' => 'Extensions',
        'text_success' => 'Success: You have modified OpenCart-AI!',
        'error_permission' => 'Warning: You do not have permission to modify OpenCart-AI!'
    ];

    public function load($route)
    {
        return $this->data;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $key;
    }

    public function set($key, $val)
    {
        $this->data[$key] = $val;
    }
}

class MockDocument
{
    public $title = '';

    public function setTitle($title)
    {
        $this->title = $title;
    }
}

class MockLoader
{
    public function language($route)
    {
        return [];
    }

    public function model($route)
    {
        // noop
    }

    public function controller($route)
    {
        return '';
    }

    public function view($route, $data)
    {
        return '<!-- rendered view: ' . $route . ' -->';
    }
}

abstract class Controller
{
    public $config;
    public $response;
    public $request;
    public $session;
    public $user;
    public $url;
    public $language;
    public $load;
    public $document;
    public $pdo;
    public $model_setting_setting;

    public function __construct()
    {
        $this->config = new MockConfig();
        $this->response = new MockResponse();
        $this->request = new MockRequest();
        $this->session = new MockSession();
        $this->user = new MockUser();
        $this->url = new MockUrl();
        $this->language = new MockLanguage();
        $this->load = new MockLoader();
        $this->document = new MockDocument();
        $this->model_setting_setting = new class {
            public $saved = [];
            public function editSetting($code, $data) {
                $this->saved[$code] = $data;
            }
            public function getSetting($code) {
                return isset($this->saved[$code]) ? $this->saved[$code] : [];
            }
        };
    }
}
