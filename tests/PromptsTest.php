<?php
/**
 * Prompts & Documentation Integrity Tests
 */

class PromptsTest
{
    public function testPromptScenariosExistAndContainBilingualPrompts()
    {
        $promptDir = dirname(__DIR__) . '/prompts';
        $expectedFiles = [
            '01_inventory_markdown.md',
            '02_order_refund_workflow.md',
            '03_seo_catalog_enhancer.md',
            '04_weekly_sales_analysis.md',
            '05_customer_support_status.md',
        ];

        foreach ($expectedFiles as $file) {
            $path = $promptDir . '/' . $file;
            assert(file_exists($path), "Expected prompt file {$file} to exist");
            $content = file_get_contents($path);

            assert(strpos($content, 'Russian Prompt') !== false || strpos($content, 'Промпт на русском') !== false, "Expected Russian prompt in {$file}");
            assert(strpos($content, 'English Prompt') !== false, "Expected English prompt in {$file}");
            assert(strpos($content, 'oc_') !== false, "Expected MCP tools referenced in {$file}");
        }

        echo "  ✓ testPromptScenariosExistAndContainBilingualPrompts passed (5 scenarios verified)\n";
    }

    public function testClientConfigurationsAreValidJson()
    {
        $clientsDir = dirname(__DIR__) . '/clients';
        $files = ['claude_desktop_config.json', 'cursor_mcp.json'];

        foreach ($files as $file) {
            $path = $clientsDir . '/' . $file;
            assert(file_exists($path), "Expected config {$file} to exist");
            $json = json_decode(file_get_contents($path), true);
            assert(json_last_error() === JSON_ERROR_NONE, "File {$file} must be valid JSON");
            assert(isset($json['mcpServers']['opencart-ai']), "File {$file} must define opencart-ai server");
        }

        echo "  ✓ testClientConfigurationsAreValidJson passed\n";
    }
}

if (basename(__FILE__) === basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '')) {
    echo "=== Prompts & Client Tests ===\n";
    $test = new PromptsTest();
    $test->testPromptScenariosExistAndContainBilingualPrompts();
    $test->testClientConfigurationsAreValidJson();
    echo "Prompts tests complete.\n";
}
