<?php
/**
 * Test Runner for OpenCart-AI
 * Runs each test suite in process isolation to mimic OpenCart multi-app environments
 */

$testFiles = [
    'Endpoint Tests' => __DIR__ . '/EndpointTest.php',
    'Admin Controller Tests' => __DIR__ . '/AdminControllerTest.php',
    'Multi-Version Admin Tests' => __DIR__ . '/MultiVersionAdminTest.php',
    'Prompts & Configs Tests' => __DIR__ . '/PromptsTest.php',
    'OCMOD Package Tests' => __DIR__ . '/PackageTest.php',
    'Multi-Distribution Package Tests' => __DIR__ . '/PackageVerificationTest.php',
    'ocStore & CIS Distribution Tests' => __DIR__ . '/DistributionBuildsTest.php',
    'Chaos & Security Tests' => __DIR__ . '/ChaosTest.php',
];

echo "🚀 Running OpenCart-AI Test Suite...\n\n";

$failedSuites = 0;
$totalSuites = count($testFiles);

foreach ($testFiles as $suiteName => $file) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "▶ {$suiteName}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];

    $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($file);
    $process = proc_open($cmd, $descriptorspec, $pipes);

    if (is_resource($process)) {
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);

        if (!empty($stdout)) {
            echo trim($stdout) . "\n";
        }
        if (!empty($stderr)) {
            echo "STDERR:\n" . trim($stderr) . "\n";
        }

        if ($returnCode !== 0) {
            echo "❌ {$suiteName} FAILED (exit code: {$returnCode})\n\n";
            $failedSuites++;
        } else {
            echo "✅ {$suiteName} PASSED\n\n";
        }
    } else {
        echo "❌ Failed to spawn process for {$file}\n\n";
        $failedSuites++;
    }
}

echo "================================================\n";
if ($failedSuites === 0) {
    echo "🎉 ALL {$totalSuites} TEST SUITES PASSED (100% SUCCESS)!\n";
    exit(0);
} else {
    echo "❌ {$failedSuites} of {$totalSuites} TEST SUITES FAILED!\n";
    exit(1);
}
