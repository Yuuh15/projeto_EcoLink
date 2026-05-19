<?php
// tests/php/ValidationTest.php

class ValidationTest {
    private $passed = 0;
    private $failed = 0;
    private $tests = [];

    public function test($name, $condition) {
        if ($condition) {
            $this->passed++;
            $this->tests[] = ['name' => $name, 'status' => 'PASS'];
        } else {
            $this->failed++;
            $this->tests[] = ['name' => $name, 'status' => 'FAIL'];
        }
    }

    public function assertEquals($expected, $actual, $message = '') {
        $this->test($message ?: "Expected: $expected, Got: $actual", $expected === $actual);
    }

    public function assertTrue($value, $message = '') {
        $this->test($message ?: "Expected true", $value === true);
    }

    public function assertFalse($value, $message = '') {
        $this->test($message ?: "Expected false", $value === false);
    }

    public function assertEmpty($value, $message = '') {
        $this->test($message ?: "Expected empty value", empty($value));
    }

    public function assertNotEmpty($value, $message = '') {
        $this->test($message ?: "Expected non-empty value", !empty($value));
    }

    public function run() {
        $this->testUserRegistrationValidation();
        $this->testUserLoginValidation();
        $this->testEventCreationValidation();
        $this->testEventRegistrationValidation();
        $this->testPasswordHashing();
        $this->printResults();
    }

    private function testUserRegistrationValidation() {
        // Test empty fields
        $this->test("Registration: Empty name rejected", $this->validateRegistration('', 'test@email.com', 'password123', 'password123') === 'Todos os campos são obrigatórios!');
        $this->test("Registration: Empty email rejected", $this->validateRegistration('John', '', 'password123', 'password123') === 'Todos os campos são obrigatórios!');
        $this->test("Registration: Empty password rejected", $this->validateRegistration('John', 'test@email.com', '', '') === 'Todos os campos são obrigatórios!');
        
        // Test invalid email
        $this->test("Registration: Invalid email rejected", $this->validateRegistration('John', 'invalid-email', 'password123', 'password123') === 'Email inválido!');
        
        // Test short password
        $this->test("Registration: Short password rejected", $this->validateRegistration('John', 'test@email.com', '12345', '12345') === 'Senha deve ter no mínimo 6 caracteres!');
        
        // Test password mismatch
        $this->test("Registration: Password mismatch rejected", $this->validateRegistration('John', 'test@email.com', 'password123', 'password456') === 'Senhas não coincidem!');
        
        // Test valid data
        $this->test("Registration: Valid data passes validation", $this->validateRegistration('John', 'test@email.com', 'password123', 'password123') === true);
    }

    private function testUserLoginValidation() {
        // Test empty fields
        $this->test("Login: Empty email rejected", $this->validateLogin('', 'password') === 'Preencha todos os campos!');
        $this->test("Login: Empty password rejected", $this->validateLogin('test@email.com', '') === 'Preencha todos os campos!');
        
        // Test valid data format
        $this->test("Login: Valid data passes validation", $this->validateLogin('test@email.com', 'password123') === true);
    }

    private function testEventCreationValidation() {
        // Test empty fields
        $this->test("Event: Empty name rejected", $this->validateEvent('', 'Description', 'Address', '2026-06-01', 50) === 'Preencha todos os campos obrigatórios!');
        $this->test("Event: Empty address rejected", $this->validateEvent('Event Name', 'Description', '', '2026-06-01', 50) === 'Preencha todos os campos obrigatórios!');
        $this->test("Event: Empty date rejected", $this->validateEvent('Event Name', 'Description', 'Address', '', 50) === 'Preencha todos os campos obrigatórios!');
        
        // Test invalid capacity
        $this->test("Event: Zero capacity rejected", $this->validateEvent('Event Name', 'Description', 'Address', '2026-06-01', 0) === 'Preencha todos os campos obrigatórios!');
        $this->test("Event: Negative capacity rejected", $this->validateEvent('Event Name', 'Description', 'Address', '2026-06-01', -10) === 'Preencha todos os campos obrigatórios!');
        
        // Test valid data
        $this->test("Event: Valid data passes validation", $this->validateEvent('Event Name', 'Description', 'Address', '2026-06-01', 50) === true);
    }

    private function testEventRegistrationValidation() {
        // Test session checks
        $this->test("Registration: No session redirects", $this->checkSession([]) === 'no_session');
        $this->test("Registration: Criador blocked", $this->checkSession(['usuario_id' => 1, 'usuario_tipo' => 'criador']) === 'is_criador');
        $this->test("Registration: Usuario allowed", $this->checkSession(['usuario_id' => 1, 'usuario_tipo' => 'usuario']) === 'allowed');
    }

    private function testPasswordHashing() {
        $password = 'testpassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertTrue(password_verify($password, $hash), "Password verification works");
        $this->assertFalse(password_verify('wrongpassword', $hash), "Wrong password fails verification");
        $this->assertTrue(strlen($hash) > 50, "Hash is sufficiently long");
        $this->assertTrue(strpos($hash, '$2y$') === 0, "Hash uses bcrypt algorithm");
    }

    private function validateRegistration($nome, $email, $senha, $confirmar) {
        if (empty($nome) || empty($email) || empty($senha)) {
            return 'Todos os campos são obrigatórios!';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email inválido!';
        }
        if (strlen($senha) < 6) {
            return 'Senha deve ter no mínimo 6 caracteres!';
        }
        if ($senha !== $confirmar) {
            return 'Senhas não coincidem!';
        }
        return true;
    }

    private function validateLogin($email, $senha) {
        if (empty($email) || empty($senha)) {
            return 'Preencha todos os campos!';
        }
        return true;
    }

    private function validateEvent($nome_evento, $descricao, $endereco, $data_evento, $capacidade_maxima) {
        if (empty($nome_evento) || empty($endereco) || empty($data_evento) || $capacidade_maxima <= 0) {
            return 'Preencha todos os campos obrigatórios!';
        }
        return true;
    }

    private function checkSession($session) {
        if (!isset($session['usuario_id'])) {
            return 'no_session';
        }
        if (isset($session['usuario_tipo']) && $session['usuario_tipo'] === 'criador') {
            return 'is_criador';
        }
        return 'allowed';
    }

    private function printResults() {
        echo "\n=== EcoLink Test Results ===\n\n";
        
        foreach ($this->tests as $test) {
            $status = $test['status'] === 'PASS' ? '✓ PASS' : '✗ FAIL';
            echo "$status: {$test['name']}\n";
        }
        
        echo "\n=== Summary ===\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo "Passed: $this->passed\n";
        echo "Failed: $this->failed\n";
        
        if ($this->failed > 0) {
            exit(1);
        }
    }
}

$test = new ValidationTest();
$test->run();
