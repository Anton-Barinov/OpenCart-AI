<?php
/**
 * OCMOD Package & XML Tests
 */

class PackageTest
{
    public function testInstallXmlIsValidXml()
    {
        $xmlPath = dirname(__DIR__) . '/install.xml';
        assert(file_exists($xmlPath), "install.xml must exist");

        $doc = new DOMDocument();
        $loaded = @$doc->load($xmlPath);
        assert($loaded !== false, "install.xml must be valid XML");

        $modifications = $doc->getElementsByTagName('modification');
        assert($modifications->length === 1, "Must contain root modification tag");

        $code = $doc->getElementsByTagName('code')->item(0)->nodeValue;
        assert(!empty($code), "Modification must have a code");

        echo "  ✓ testInstallXmlIsValidXml passed (code: {$code})\n";
    }

    public function testOcmodZipContainsRequiredFiles()
    {
        $zipPath = dirname(__DIR__) . '/dist/opencart-ai.ocmod.zip';
        assert(file_exists($zipPath), "opencart-ai.ocmod.zip must exist");

        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);
        assert($opened === true, "Must be able to open zip archive");

        $hasInstallXml = ($zip->locateName('install.xml') !== false);
        assert($hasInstallXml, "Zip must contain install.xml at root");

        $hasAdminController = ($zip->locateName('upload/admin/controller/extension/module/opencart_ai.php') !== false);
        assert($hasAdminController, "Zip must contain admin controller");

        $hasCatalogController = ($zip->locateName('upload/catalog/controller/extension/module/opencart_ai.php') !== false);
        assert($hasCatalogController, "Zip must contain catalog controller");

        $hasLibrary = ($zip->locateName('upload/system/library/opencart_ai/autoload.php') !== false);
        assert($hasLibrary, "Zip must contain opencart_ai system library");

        $zip->close();
        echo "  ✓ testOcmodZipContainsRequiredFiles passed\n";
    }
}

if (basename(__FILE__) === basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '')) {
    echo "=== OCMOD Package Tests ===\n";
    $test = new PackageTest();
    $test->testInstallXmlIsValidXml();
    $test->testOcmodZipContainsRequiredFiles();
    echo "Package tests complete.\n";
}
