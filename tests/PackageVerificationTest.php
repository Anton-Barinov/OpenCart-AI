<?php
/**
 * Package Verification Test
 * Verifies all 5 OpenCart distribution packages built by bin/package-ocmod
 *
 * @author Anton Barinov <https://github.com/Anton-Barinov>
 * @license MIT
 */

$root = dirname(__DIR__);
$distDir = $root . '/dist';

echo "=== Running Multi-Distribution Package Verification Tests ===\n\n";

$packages = [
    'opencart-ai.ocmod.zip' => [
        'name'          => 'Universal Distribution',
        'min_files'     => 30,
        'has_xml'       => true,
        'has_json'      => true,
        'must_have'     => [
            'install.xml',
            'upload/admin/controller/extension/module/opencart_ai.php',
            'upload/admin/view/template/extension/module/opencart_ai.twig',
            'upload/admin/view/template/extension/module/opencart_ai.tpl',
            'upload/admin/controller/module/opencart_ai.php',
            'upload/extension/opencart_ai/admin/controller/module/opencart_ai.php',
            'upload/system/library/opencart_ai/autoload.php'
        ]
    ],
    'opencart-ai-oc3.ocmod.zip' => [
        'name'          => 'OpenCart 3.0 / ocStore 3.0 Distribution',
        'min_files'     => 20,
        'has_xml'       => true,
        'has_json'      => false,
        'must_have'     => [
            'install.xml',
            'upload/admin/controller/extension/module/opencart_ai.php',
            'upload/admin/view/template/extension/module/opencart_ai.twig',
            'upload/catalog/controller/extension/module/opencart_ai.php',
            'upload/system/library/opencart_ai/autoload.php'
        ]
    ],
    'opencart-ai-oc23.ocmod.zip' => [
        'name'          => 'OpenCart 2.3 / ocStore 2.3 Distribution',
        'min_files'     => 20,
        'has_xml'       => true,
        'has_json'      => false,
        'must_have'     => [
            'install.xml',
            'upload/admin/controller/extension/module/opencart_ai.php',
            'upload/admin/view/template/extension/module/opencart_ai.tpl',
            'upload/catalog/controller/extension/module/opencart_ai.php',
            'upload/system/library/opencart_ai/autoload.php'
        ]
    ],
    'opencart-ai-oc2.ocmod.zip' => [
        'name'          => 'OpenCart 2.0-2.2 Distribution',
        'min_files'     => 20,
        'has_xml'       => true,
        'has_json'      => false,
        'must_have'     => [
            'install.xml',
            'upload/admin/controller/module/opencart_ai.php',
            'upload/admin/view/template/module/opencart_ai.tpl',
            'upload/admin/language/english/module/opencart_ai.php',
            'upload/admin/language/russian/module/opencart_ai.php',
            'upload/catalog/controller/module/opencart_ai.php',
            'upload/system/library/opencart_ai/autoload.php'
        ]
    ],
    'opencart-ai-oc4.ocmod.zip' => [
        'name'          => 'OpenCart 4.x Namespaced Distribution',
        'min_files'     => 20,
        'has_xml'       => false,
        'has_json'      => true,
        'must_have'     => [
            'install.json',
            'upload/extension/opencart_ai/admin/controller/module/opencart_ai.php',
            'upload/extension/opencart_ai/admin/view/template/module/opencart_ai.twig',
            'upload/extension/opencart_ai/catalog/controller/module/opencart_ai.php',
            'upload/system/library/opencart_ai/autoload.php'
        ]
    ],
];

foreach ($packages as $zipName => $info) {
    $zipPath = $distDir . '/' . $zipName;
    assert(file_exists($zipPath), "Archive {$zipName} must exist");
    assert(filesize($zipPath) > 5000, "Archive {$zipName} must be > 5KB");

    $zip = new ZipArchive();
    $res = $zip->open($zipPath);
    assert($res === true, "Archive {$zipName} must open cleanly");

    $fileList = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $fileList[] = $stat['name'];
    }
    $zip->close();

    assert(count($fileList) >= $info['min_files'], "Package {$zipName} must have at least {$info['min_files']} files, had " . count($fileList));

    foreach ($info['must_have'] as $req) {
        assert(in_array($req, $fileList), "Package {$zipName} must contain {$req}");
    }

    echo "  ✓ Verified {$info['name']} [{$zipName}]: " . count($fileList) . " files included\n";
}

echo "\n---------------------------------------------------------";
echo "\nALL 5 OCMOD PACKAGES VERIFIED SUCCESSFULLY! 🎉\n";
