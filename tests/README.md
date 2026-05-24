# EcoLink Test Suite

## Overview

This directory contains tests for the EcoLink ecological events management platform.

## Test Structure

```
tests/
├── php/                          # PHP unit tests
│   └── ValidationTest.php        # Tests for validation logic
├── js/                           # JavaScript tests
│   ├── script.test.js            # Tests for carousel and search functionality
│   └── responsive.test.js        # Tests for CSS responsiveness and breakpoints
├── integration/                  # Integration tests
│   └── DatabaseTest.php          # Tests for database operations
├── functional/                   # Functional tests
│   └── FunctionalTest.php        # Complete application flow tests
├── usecase/                      # Use case tests
│   └── UseCaseTest.php           # Tests mapped to system use cases
├── run_tests.php                 # Main test runner
└── README.md                     # This file
```

## Running Tests

### PHP Tests (All)

```bash
php tests/run_tests.php
```

### Individual PHP Test Suites

```bash
php tests/php/ValidationTest.php
php tests/integration/DatabaseTest.php
php tests/functional/FunctionalTest.php
php tests/usecase/UseCaseTest.php
```

### JavaScript Tests

```bash
# Original tests
node tests/js/script.test.js

# Responsiveness tests
node tests/js/responsive.test.js
```

## Test Coverage

### PHP Validation Tests (`tests/php/ValidationTest.php`)
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

### JavaScript Tests (`tests/js/script.test.js`)
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

### Responsiveness Tests (`tests/js/responsive.test.js`)
- Viewport breakpoints (768px, 600px, 480px)
- Carousel responsive behavior (vertical stacking on mobile)
- Header responsive behavior (row -> column)
- Event grid responsive behavior (multi-column -> single column)
- Form responsive behavior (max-width, padding, font-size)
- Stats responsive behavior (row -> column)
- Perfil responsive behavior (row -> column, text centering)
- Component coverage verification

### Database Integration Tests (`tests/integration/DatabaseTest.php`)
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

### Functional Tests (`tests/functional/FunctionalTest.php`)
- **UC01**: User registration flow (create, retrieve, duplicate rejection)
- **UC03**: Creator registration flow (user + profile creation, CNPJ duplicate rejection)
- **UC02**: User login flow (password verification, wrong password rejection)
- **UC04**: Creator login flow (email + CNPJ verification)
- **UC05**: Event creation flow (insert, retrieve, default status, capacity)
- **UC07**: Event subscription flow (register, counter increment)
- **UC07**: Duplicate registration rejection
- **UC07**: Capacity limit enforcement
- **UC08**: Event unsubscription flow (delete, counter decrement)
- **UC11**: Event removal flow (verification, deletion)
- Test data cleanup

### Use Case Tests (`tests/usecase/UseCaseTest.php`)
- **UC01-C01~C05**: Cadastrar Usuário (dados válidos, inserção, persistência, tipo padrão, email duplicado)
- **UC02-C01~C05**: Login Usuário (busca por email, verificação bcrypt, tipo, senha errada)
- **UC03-C01~C06**: Cadastrar Criador (conta tipo criador, perfil completo, CNPJ, razão social, nome fantasia)
- **UC04-C01~C05**: Login Criador (JOIN criadores, tipo, perfil, CNPJ, senha)
- **UC05-C01~C07**: Criar Evento (dados válidos, nome, capacidade, status, inscritos, criador associado)
- **UC06-C01~C06**: Listar Eventos (consulta, array, campos: nome, data, capacidade, realizador)
- **UC07-C01~C05**: Inscrever em Evento (inscrição, status, associações, duplicata)
- **UC08-C01~C04**: Dashboard Usuário (dados, data cadastro, total inscrições, lista)
- **UC09-C01~C04**: Dashboard Criador (total eventos, total inscritos, ativos, capacidade)
- **UC10-C01~C03**: Retirar Inscrição (remoção, verificação, contador)
- **UC11-C01~C04**: Remover Evento (existência, permissão, remoção, verificação)
- **UC12-C01~C04**: Buscar Eventos (nome, termo inexistente, case-insensitive)

## Test Results

Tests output results in the following format:

```
=== Test Name ===

PASSOU: Test description
FALHOU: Test description

=== Summary ===
Total: X
Passed: Y
Failed: Z
```

Exit code is 1 if any tests fail, 0 if all tests pass.
