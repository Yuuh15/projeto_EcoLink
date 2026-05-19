# EcoLink Test Suite

## Overview

This directory contains tests for the EcoLink ecological events management platform.

## Test Structure

```
tests/
├── php/                          # PHP unit tests
│   └── ValidationTest.php        # Tests for validation logic
├── js/                           # JavaScript tests
│   └── script.test.js            # Tests for carousel and search functionality
├── integration/                  # Integration tests
│   └── DatabaseTest.php          # Tests for database operations
└── run_tests.php                 # Main test runner
```

## Running Tests

### PHP Tests

```bash
# Run all tests
php tests/run_tests.php

# Run specific test files
php tests/php/ValidationTest.php
php tests/integration/DatabaseTest.php
```

### JavaScript Tests

```bash
# Run with Node.js
node tests/js/script.test.js
```

## Test Coverage

### PHP Validation Tests
- User registration validation
  - Empty fields
  - Email format
  - Password length
  - Password confirmation
- User login validation
  - Empty fields
- Event creation validation
  - Required fields
  - Capacity validation
- Event registration validation
  - Session checks
  - User type checks
- Password hashing
  - Hash generation
  - Password verification
  - Algorithm verification

### JavaScript Tests
- Carousel functionality
  - Search filtering
  - Case insensitivity
  - No results handling
- Search and filter
  - Title search
  - Description search
  - Empty search reset
- Utility functions
  - String operations
  - Array operations

### Database Integration Tests
- Database connection
- User table operations
  - Insert user
  - Retrieve user
  - Duplicate email rejection
- Event table operations
  - Insert event
  - Retrieve event
  - Event status
- Registration table operations
  - Insert registration
  - Retrieve registration
  - Duplicate registration rejection
- Transaction handling
  - Rollback
  - Commit
- Cleanup

## Test Results

Tests output results in the following format:

```
✓ PASS: Test description
✗ FAIL: Test description

=== Summary ===
Total: X
Passed: Y
Failed: Z
```

Exit code is 1 if any tests fail, 0 if all tests pass.
