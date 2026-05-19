<?php
// tests/integration/DatabaseTest.php
// Integration tests for database operations

require_once __DIR__ . '/../../config/database.php';

class DatabaseTest {
    private $pdo;
    private $passed = 0;
    private $failed = 0;
    private $tests = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

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

    public function assertNotNull($value, $message = '') {
        $this->test($message ?: "Expected non-null value", $value !== null);
    }

    public function run() {
        $this->testDatabaseConnection();
        $this->testUserTable();
        $this->testEventTable();
        $this->testInscricoesTable();
        $this->testTransactionHandling();
        $this->cleanupTestData();
        $this->printResults();
    }

    private function testDatabaseConnection() {
        $this->assertNotNull($this->pdo, 'PDO connection exists');
        
        try {
            $stmt = $this->pdo->query('SELECT 1');
            $this->assertTrue($stmt !== false, 'Database query executes successfully');
        } catch (PDOException $e) {
            $this->test('Database query fails', false);
        }
    }

    private function testUserTable() {
        // Insert test user
        $email = 'test_' . time() . '@example.com';
        $senha_hash = password_hash('testpassword', PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
            $result = $stmt->execute(['Test User', $email, $senha_hash]);
            $this->assertTrue($result, 'User insert succeeds');
            
            // Verify user was inserted
            $stmt = $this->pdo->prepare("SELECT id, nome, email, tipo FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            $this->assertNotNull($user, 'User can be retrieved');
            $this->assertEquals('Test User', $user['nome'], 'User name is correct');
            $this->assertEquals($email, $user['email'], 'User email is correct');
            $this->assertEquals('usuario', $user['tipo'], 'User type is correct');
            
            // Test duplicate email
            try {
                $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
                $stmt->execute(['Duplicate User', $email, $senha_hash, 'usuario']);
                $this->test('Duplicate email is rejected', false);
            } catch (PDOException $e) {
                $this->test('Duplicate email is rejected', true);
            }
            
            // Store user ID for cleanup
            $this->test_user_id = $user['id'];
            
        } catch (PDOException $e) {
            $this->test('User insert fails: ' . $e->getMessage(), false);
        }
    }

    private function testEventTable() {
        // First, create a criador user
        $email = 'criador_' . time() . '@example.com';
        $senha_hash = password_hash('testpassword', PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'criador')");
            $stmt->execute(['Test Criador', $email, $senha_hash]);
            $usuario_id = $this->pdo->lastInsertId();
            
            // Create criador profile
            $stmt = $this->pdo->prepare("INSERT INTO criadores (usuario_id, cnpj, razao_social) VALUES (?, ?, ?)");
            $stmt->execute([$usuario_id, '12345678000199', 'Test Company Ltd']);
            $criador_id = $this->pdo->lastInsertId();
            
            // Create event
            $data_evento = date('Y-m-d', strtotime('+30 days'));
            $stmt = $this->pdo->prepare("INSERT INTO eventos (criador_id, nome_evento, descricao, endereco, data_evento, capacidade_maxima) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$criador_id, 'Test Event', 'Test Description', 'Test Address', $data_evento, 100]);
            $this->assertTrue($result, 'Event insert succeeds');
            
            // Verify event was inserted
            $stmt = $this->pdo->prepare("SELECT id, nome_evento, descricao, endereco, data_evento, capacidade_maxima FROM eventos WHERE nome_evento = ?");
            $stmt->execute(['Test Event']);
            $evento = $stmt->fetch();
            
            $this->assertNotNull($evento, 'Event can be retrieved');
            $this->assertEquals('Test Event', $evento['nome_evento'], 'Event name is correct');
            $this->assertEquals('Test Description', $evento['descricao'], 'Event description is correct');
            $this->assertEquals('Test Address', $evento['endereco'], 'Event address is correct');
            $this->assertEquals('100', $evento['capacidade_maxima'], 'Event capacity is correct');
            $this->assertEquals('ativo', $evento['status'], 'Event status is ativo');
            
            // Store IDs for cleanup
            $this->test_criador_id = $criador_id;
            $this->test_evento_id = $evento['id'];
            $this->test_criador_usuario_id = $usuario_id;
            
        } catch (PDOException $e) {
            $this->test('Event insert fails: ' . $e->getMessage(), false);
        }
    }

    private function testInscricoesTable() {
        // Create a usuario for registration
        $email = 'usuario_' . time() . '@example.com';
        $senha_hash = password_hash('testpassword', PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
            $stmt->execute(['Test Usuario', $email, $senha_hash]);
            $usuario_id = $this->pdo->lastInsertId();
            
            // Create event for registration
            $data_evento = date('Y-m-d', strtotime('+30 days'));
            $stmt = $this->pdo->prepare("INSERT INTO eventos (criador_id, nome_evento, descricao, endereco, data_evento, capacidade_maxima) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$this->test_criador_id ?? 1, 'Registration Test Event', 'Test', 'Address', $data_evento, 100]);
            $evento_id = $this->pdo->lastInsertId();
            
            // Register user for event
            $stmt = $this->pdo->prepare("INSERT INTO inscricoes (usuario_id, evento_id, status) VALUES (?, ?, 'confirmada')");
            $result = $stmt->execute([$usuario_id, $evento_id]);
            $this->assertTrue($result, 'Registration insert succeeds');
            
            // Verify registration
            $stmt = $this->pdo->prepare("SELECT id, usuario_id, evento_id, status FROM inscricoes WHERE usuario_id = ? AND evento_id = ?");
            $stmt->execute([$usuario_id, $evento_id]);
            $inscricao = $stmt->fetch();
            
            $this->assertNotNull($inscricao, 'Registration can be retrieved');
            $this->assertEquals($usuario_id, $inscricao['usuario_id'], 'Registration usuario_id is correct');
            $this->assertEquals($evento_id, $inscricao['evento_id'], 'Registration evento_id is correct');
            $this->assertEquals('confirmada', $inscricao['status'], 'Registration status is confirmada');
            
            // Test duplicate registration
            try {
                $stmt = $this->pdo->prepare("INSERT INTO inscricoes (usuario_id, evento_id, status) VALUES (?, ?, 'confirmada')");
                $stmt->execute([$usuario_id, $evento_id]);
                $this->test('Duplicate registration is rejected', false);
            } catch (PDOException $e) {
                $this->test('Duplicate registration is rejected', true);
            }
            
            // Store IDs for cleanup
            $this->test_usuario_id = $usuario_id;
            $this->test_registration_evento_id = $evento_id;
            
        } catch (PDOException $e) {
            $this->test('Registration test fails: ' . $e->getMessage(), false);
        }
    }

    private function testTransactionHandling() {
        try {
            $this->pdo->beginTransaction();
            
            // Insert test data
            $email = 'transaction_' . time() . '@example.com';
            $senha_hash = password_hash('testpassword', PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
            $stmt->execute(['Transaction Test', $email, $senha_hash]);
            
            // Rollback
            $this->pdo->rollBack();
            
            // Verify data was rolled back
            $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            $this->assertNull($user, 'Transaction rollback works');
            
            // Test commit
            $this->pdo->beginTransaction();
            
            $email2 = 'commit_' . time() . '@example.com';
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
            $stmt->execute(['Commit Test', $email2, $senha_hash]);
            
            $this->pdo->commit();
            
            $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email2]);
            $user2 = $stmt->fetch();
            
            $this->assertNotNull($user2, 'Transaction commit works');
            
        } catch (PDOException $e) {
            $this->test('Transaction test fails: ' . $e->getMessage(), false);
        }
    }

    private function cleanupTestData() {
        try {
            $this->pdo->beginTransaction();
            
            // Delete test registrations
            if (isset($this->test_usuario_id)) {
                $stmt = $this->pdo->prepare("DELETE FROM inscricoes WHERE usuario_id = ?");
                $stmt->execute([$this->test_usuario_id]);
            }
            if (isset($this->test_registration_evento_id)) {
                $stmt = $this->pdo->prepare("DELETE FROM inscricoes WHERE evento_id = ?");
                $stmt->execute([$this->test_registration_evento_id]);
            }
            
            // Delete test events
            if (isset($this->test_evento_id)) {
                $stmt = $this->pdo->prepare("DELETE FROM eventos WHERE id = ?");
                $stmt->execute([$this->test_evento_id]);
            }
            if (isset($this->test_registration_evento_id)) {
                $stmt = $this->pdo->prepare("DELETE FROM eventos WHERE id = ?");
                $stmt->execute([$this->test_registration_evento_id]);
            }
            
            // Delete test criadores
            if (isset($this->test_criador_id)) {
                $stmt = $this->pdo->prepare("DELETE FROM criadores WHERE id = ?");
                $stmt->execute([$this->test_criador_id]);
            }
            
            // Delete test usuarios
            if (isset($this->test_user_id)) {
                $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$this->test_user_id]);
            }
            if (isset($this->test_criador_usuario_id)) {
                $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$this->test_criador_usuario_id]);
            }
            if (isset($this->test_usuario_id)) {
                $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$this->test_usuario_id]);
            }
            
            $this->pdo->commit();
            $this->test('Cleanup completed successfully', true);
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->test('Cleanup failed: ' . $e->getMessage(), false);
        }
    }

    private function printResults() {
        echo "\n=== EcoLink Database Integration Test Results ===\n\n";
        
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

$test = new DatabaseTest($pdo);
$test->run();
