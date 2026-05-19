<?php
// tests/run_tests.php
// Run all tests

echo "=== EcoLink Test Suite ===\n";
echo "Running all tests...\n\n";

// Run PHP unit tests
echo "--- PHP Validation Tests ---\n";
require_once __DIR__ . '/php/ValidationTest.php';

echo "\n--- Database Integration Tests ---\n";
require_once __DIR__ . '/integration/DatabaseTest.php';

echo "\n=== All Tests Complete ===\n";
