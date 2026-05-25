<?php
// tests/run_tests.php
// Run all tests

echo "=== EcoLink Test Suite ===\n";
echo "Running all tests...\n\n";

$exitCode = 0;

function runTestSuite($name, $file) {
    echo "--- $name ---\n";
    ob_start();
    require_once $file;
    $output = ob_get_clean();
    echo $output;
    
    // Check if the test script exited with error
    $lastExit = preg_match('/Failed: (\d+)/', $output, $m) ? (int)$m[1] : 0;
    return $lastExit;
}

$failures = 0;

// PHP unit tests
$failures += runTestSuite('PHP Validation Tests', __DIR__ . '/php/ValidationTest.php');

echo "\n";

// Database integration tests
$failures += runTestSuite('Database Integration Tests', __DIR__ . '/integration/DatabaseTest.php');

echo "\n";

// Functional tests
$failures += runTestSuite('Functional Tests', __DIR__ . '/functional/FunctionalTest.php');

echo "\n";

// Use case tests
$failures += runTestSuite('Use Case Tests', __DIR__ . '/usecase/UseCaseTest.php');

echo "\n=== All Tests Complete ===\n\n";

if ($failures > 0) {
    echo "Some tests failed. Check output above for details.\n";
    exit(1);
} else {
    echo "All tests passed!\n";
}
