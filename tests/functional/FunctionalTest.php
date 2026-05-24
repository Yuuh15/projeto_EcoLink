<?php
// tests/functional/FunctionalTest.php
// Functional tests - test complete application flows

require_once __DIR__ . '/../../config/database.php';

class FunctionalTest {
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

    public function assertNull($value, $message = '') {
        $this->test($message ?: "Expected null value", $value === null);
    }

    private function generateUniqueEmail() {
        return 'func_test_' . time() . '_' . rand(1000, 9999) . '@example.com';
    }

    public function run() {
        $this->testUserRegistrationFlow();
        $this->testCreatorRegistrationFlow();
        $this->testUserLoginFlow();
        $this->testCreatorLoginFlow();
        $this->testEventCreationFlow();
        $this->testEventSubscriptionFlow();
        $this->testEventUnsubscriptionFlow();
        $this->testEventRemovalFlow();
        $this->testCapacityLimitFlow();
        $this->testDuplicateRegistrationFlow();
        $this->cleanup();
        $this->printResults();
    }

    private function testUserRegistrationFlow() {
        $email = $this->generateUniqueEmail();
        $senha = 'password123';
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
            $result = $stmt->execute(['Functional User', $email, $senha_hash]);
            $this->assertTrue($result, 'UC01: Cadastro de usuário - inserção com sucesso');

            $stmt = $this->pdo->prepare("SELECT id, nome, email, tipo FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $this->assertNotNull($user, 'UC01: Cadastro de usuário - usuário recuperado');
            $this->assertEquals('Functional User', $user['nome'], 'UC01: Nome do usuário correto');
            $this->assertEquals($email, $user['email'], 'UC01: Email do usuário correto');
            $this->assertEquals('usuario', $user['tipo'], 'UC01: Tipo do usuário correto');

            $this->func_user_id = $user['id'];
            $this->func_user_email = $email;
            $this->func_user_password = $senha;
        } catch (PDOException $e) {
            $this->test('UC01: Cadastro de usuário - erro: ' . $e->getMessage(), false);
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
            $stmt->execute(['Duplicate', $email, $senha_hash]);
            $this->test('UC01: Rejeitar email duplicado', false);
        } catch (PDOException $e) {
            $this->test('UC01: Rejeitar email duplicado', true);
        }
    }

    private function testCreatorRegistrationFlow() {
        $email = $this->generateUniqueEmail();
        $senha = 'creatorpass123';
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'criador')");
            $stmt->execute(['Functional Creator', $email, $senha_hash]);
            $usuario_id = $this->pdo->lastInsertId();
            $this->assertTrue($usuario_id > 0, 'UC03: Cadastro de criador - usuário criado');

            $stmt = $this->pdo->prepare("INSERT INTO criadores (usuario_id, cnpj, razao_social, nome_fantasia, telefone, endereco) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$usuario_id, '11222333000181', 'EcoLink Creator Ltda', 'EcoLink Criador', '11999999999', 'Rua Verde, 123']);
            $this->assertTrue($result, 'UC03: Cadastro de criador - perfil criado');

            $this->pdo->commit();

            $stmt = $this->pdo->prepare("SELECT c.*, u.nome, u.email FROM criadores c JOIN usuarios u ON c.usuario_id = u.id WHERE c.usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $creator = $stmt->fetch();
            $this->assertNotNull($creator, 'UC03: Cadastro de criador - dados recuperados');
            $this->assertEquals('EcoLink Creator Ltda', $creator['razao_social'], 'UC03: Razão social correta');
            $this->assertEquals('11222333000181', $creator['cnpj'], 'UC03: CNPJ correto');

            $this->func_creator_id = $creator['id'];
            $this->func_creator_usuario_id = $usuario_id;
            $this->func_creator_email = $email;
            $this->func_creator_password = $senha;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->test('UC03: Cadastro de criador - erro: ' . $e->getMessage(), false);
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO criadores (usuario_id, cnpj, razao_social) VALUES (?, ?, ?)");
            $stmt->execute([$usuario_id ?? 0, '11222333000181', 'Duplicate CNPJ']);
            $this->test('UC03: Rejeitar CNPJ duplicado', false);
        } catch (PDOException $e) {
            $this->test('UC03: Rejeitar CNPJ duplicado', true);
        }
    }

    private function testUserLoginFlow() {
        if (!isset($this->func_user_email) || !isset($this->func_user_password)) {
            $this->test('UC02: Login de usuário - pulo (sem dados)', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?");
            $stmt->execute([$this->func_user_email]);
            $user = $stmt->fetch();

            $this->assertNotNull($user, 'UC02: Login - usuário encontrado');
            if ($user) {
                $valid = password_verify($this->func_user_password, $user['senha']);
                $this->assertTrue($valid, 'UC02: Login - senha verificada com sucesso');
                $this->assertEquals('usuario', $user['tipo'], 'UC02: Login - tipo usuario correto');

                $invalid = password_verify('wrongpassword', $user['senha']);
                $this->assertFalse($invalid, 'UC02: Login - senha incorreta rejeitada');
            }
        } catch (PDOException $e) {
            $this->test('UC02: Login - erro: ' . $e->getMessage(), false);
        }
    }

    private function testCreatorLoginFlow() {
        if (!isset($this->func_creator_email) || !isset($this->func_creator_password)) {
            $this->test('UC04: Login de criador - pulo (sem dados)', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.nome, u.email, u.senha, u.tipo, c.id as criador_id, c.cnpj
                FROM usuarios u
                JOIN criadores c ON c.usuario_id = u.id
                WHERE u.email = ?
            ");
            $stmt->execute([$this->func_creator_email]);
            $user = $stmt->fetch();

            $this->assertNotNull($user, 'UC04: Login criador - usuário encontrado');
            if ($user) {
                $valid = password_verify($this->func_creator_password, $user['senha']);
                $this->assertTrue($valid, 'UC04: Login criador - senha verificada');
                $this->assertEquals('criador', $user['tipo'], 'UC04: Login criador - tipo criador correto');
                $this->assertNotNull($user['criador_id'], 'UC04: Login criador - perfil criador existe');
                $this->assertNotNull($user['cnpj'], 'UC04: Login criador - CNPJ existe');
            }
        } catch (PDOException $e) {
            $this->test('UC04: Login criador - erro: ' . $e->getMessage(), false);
        }
    }

    private function testEventCreationFlow() {
        if (!isset($this->func_creator_id)) {
            $this->test('UC05: Criar evento - pulo (sem criador)', false);
            return;
        }

        $data_evento = date('Y-m-d H:i:s', strtotime('+60 days'));

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO eventos (criador_id, nome_evento, descricao, endereco, data_evento, capacidade_maxima)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$this->func_creator_id, 'Evento Teste Funcional', 'Descricao do evento funcional', 'Rua Teste, 456', $data_evento, 50]);
            $this->assertTrue($result, 'UC05: Criar evento - inserção com sucesso');
            $this->func_evento_id = $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("SELECT * FROM eventos WHERE id = ?");
            $stmt->execute([$this->func_evento_id]);
            $evento = $stmt->fetch();

            $this->assertNotNull($evento, 'UC05: Criar evento - evento recuperado');
            $this->assertEquals('Evento Teste Funcional', $evento['nome_evento'], 'UC05: Nome do evento correto');
            $this->assertEquals(50, $evento['capacidade_maxima'], 'UC05: Capacidade correta');
            $this->assertEquals('ativo', $evento['status'], 'UC05: Status padrão é ativo');
            $this->assertEquals(0, $evento['inscritos'], 'UC05: Inscritos inicial é zero');

            $this->testFullEvent = $evento;
        } catch (PDOException $e) {
            $this->test('UC05: Criar evento - erro: ' . $e->getMessage(), false);
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO eventos (criador_id, nome_evento, descricao, endereco, data_evento, capacidade_maxima)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$this->func_creator_id, '', 'Desc', 'Addr', $data_evento, 10]);
            $this->test('UC05: Criar evento - nome vazio rejeitado (validação)', false);
        } catch (PDOException $e) {
            $this->test('UC05: Criar evento - nome vazio rejeitado (validação)', true);
        }
    }

    private function testEventSubscriptionFlow() {
        if (!isset($this->func_user_id) || !isset($this->func_evento_id)) {
            $this->test('UC07: Inscrever em evento - pulo (sem dados)', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO inscricoes (usuario_id, evento_id, status) VALUES (?, ?, 'confirmada')");
            $result = $stmt->execute([$this->func_user_id, $this->func_evento_id]);
            $this->assertTrue($result, 'UC07: Inscrever em evento - inscrição realizada');
            $this->func_inscricao_id = $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("UPDATE eventos SET inscritos = inscritos + 1 WHERE id = ?");
            $stmt->execute([$this->func_evento_id]);

            $stmt = $this->pdo->prepare("SELECT * FROM inscricoes WHERE id = ?");
            $stmt->execute([$this->func_inscricao_id]);
            $inscricao = $stmt->fetch();
            $this->assertNotNull($inscricao, 'UC07: Inscrição recuperada');
            $this->assertEquals($this->func_user_id, $inscricao['usuario_id'], 'UC07: usuario_id correto');
            $this->assertEquals($this->func_evento_id, $inscricao['evento_id'], 'UC07: evento_id correto');
            $this->assertEquals('confirmada', $inscricao['status'], 'UC07: Status confirmada');

            $stmt = $this->pdo->prepare("SELECT inscritos FROM eventos WHERE id = ?");
            $stmt->execute([$this->func_evento_id]);
            $evento = $stmt->fetch();
            $this->assertEquals(1, $evento['inscritos'], 'UC07: Contador de inscritos incrementado');
        } catch (PDOException $e) {
            $this->test('UC07: Inscrever em evento - erro: ' . $e->getMessage(), false);
        }
    }

    private function testDuplicateRegistrationFlow() {
        if (!isset($this->func_user_id) || !isset($this->func_evento_id)) {
            $this->test('UC07: Rejeitar inscrição duplicada - pulo', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO inscricoes (usuario_id, evento_id, status) VALUES (?, ?, 'confirmada')");
            $stmt->execute([$this->func_user_id, $this->func_evento_id]);
            $this->test('UC07: Rejeitar inscrição duplicada', false);
        } catch (PDOException $e) {
            $this->test('UC07: Rejeitar inscrição duplicada', true);
        }
    }

    private function testCapacityLimitFlow() {
        $data_evento = date('Y-m-d H:i:s', strtotime('+90 days'));

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO eventos (criador_id, nome_evento, descricao, endereco, data_evento, capacidade_maxima)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$this->func_creator_id ?? 1, 'Evento Capacidade Teste', 'Teste de limite', 'Rua Limite', $data_evento, 2]);
            $cap_evento_id = $this->pdo->lastInsertId();

            $email1 = $this->generateUniqueEmail();
            $email2 = $this->generateUniqueEmail();
            $email3 = $this->generateUniqueEmail();
            $hash = password_hash('pass', PASSWORD_DEFAULT);

            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
            $stmt->execute(['CapUser1', $email1, $hash]);
            $uid1 = $this->pdo->lastInsertId();
            $stmt->execute(['CapUser2', $email2, $hash]);
            $uid2 = $this->pdo->lastInsertId();
            $stmt->execute(['CapUser3', $email3, $hash]);
            $uid3 = $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("INSERT INTO inscricoes (usuario_id, evento_id, status) VALUES (?, ?, 'confirmada')");
            $stmt->execute([$uid1, $cap_evento_id]);
            $stmt->execute([$uid2, $cap_evento_id]);

            $stmt = $this->pdo->prepare("UPDATE eventos SET inscritos = 2 WHERE id = ?");
            $stmt->execute([$cap_evento_id]);

            $stmt = $this->pdo->prepare("SELECT capacidade_maxima, inscritos FROM eventos WHERE id = ?");
            $stmt->execute([$cap_evento_id]);
            $evento = $stmt->fetch();
            $this->assertTrue($evento['inscritos'] >= $evento['capacidade_maxima'], 'UC07: Limite de capacidade atingido');

            try {
                $stmt = $this->pdo->prepare("INSERT INTO inscricoes (usuario_id, evento_id, status) VALUES (?, ?, 'confirmada')");
                $stmt->execute([$uid3, $cap_evento_id]);
                $this->test('UC07: Exceder capacidade rejeitado (lógica)', false);
            } catch (PDOException $e) {
                $this->test('UC07: Exceder capacidade rejeitado (DB)', true);
            }

            $this->pdo->prepare("DELETE FROM inscricoes WHERE evento_id = ?")->execute([$cap_evento_id]);
            $this->pdo->prepare("DELETE FROM eventos WHERE id = ?")->execute([$cap_evento_id]);
            $this->pdo->prepare("DELETE FROM usuarios WHERE id IN (?, ?, ?)")->execute([$uid1, $uid2, $uid3]);
        } catch (PDOException $e) {
            $this->test('UC07: Teste de capacidade - erro: ' . $e->getMessage(), false);
        }
    }

    private function testEventUnsubscriptionFlow() {
        if (!isset($this->func_inscricao_id) || !isset($this->func_evento_id) || !isset($this->func_user_id)) {
            $this->test('UC08: Retirar inscrição - pulo (sem dados)', false);
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE eventos SET inscritos = inscritos - 1 WHERE id = ?");
            $stmt->execute([$this->func_evento_id]);

            $stmt = $this->pdo->prepare("DELETE FROM inscricoes WHERE evento_id = ? AND usuario_id = ?");
            $result = $stmt->execute([$this->func_evento_id, $this->func_user_id]);
            $this->assertTrue($result, 'UC08: Retirar inscrição - remoção com sucesso');

            $this->pdo->commit();

            $stmt = $this->pdo->prepare("SELECT id FROM inscricoes WHERE id = ?");
            $stmt->execute([$this->func_inscricao_id]);
            $this->assertNull($stmt->fetch(), 'UC08: Inscrição removida do banco');

            $stmt = $this->pdo->prepare("SELECT inscritos FROM eventos WHERE id = ?");
            $stmt->execute([$this->func_evento_id]);
            $evento = $stmt->fetch();
            $this->assertEquals(0, $evento['inscritos'], 'UC08: Contador de inscritos decrementado');
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->test('UC08: Retirar inscrição - erro: ' . $e->getMessage(), false);
        }
    }

    private function testEventRemovalFlow() {
        if (!isset($this->func_evento_id) || !isset($this->func_creator_id)) {
            $this->test('UC11: Remover evento - pulo (sem dados)', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id FROM eventos WHERE id = ? AND criador_id = ?");
            $stmt->execute([$this->func_evento_id, $this->func_creator_id]);
            $evento = $stmt->fetch();
            $this->assertNotNull($evento, 'UC11: Remover evento - evento pertence ao criador');

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("DELETE FROM inscricoes WHERE evento_id = ?");
            $stmt->execute([$this->func_evento_id]);

            $stmt = $this->pdo->prepare("DELETE FROM eventos WHERE id = ? AND criador_id = ?");
            $result = $stmt->execute([$this->func_evento_id, $this->func_creator_id]);
            $this->assertTrue($result, 'UC11: Remover evento - exclusão com sucesso');

            $this->pdo->commit();

            $stmt = $this->pdo->prepare("SELECT id FROM eventos WHERE id = ?");
            $stmt->execute([$this->func_evento_id]);
            $this->assertNull($stmt->fetch(), 'UC11: Evento removido do banco');

            unset($this->func_evento_id);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->test('UC11: Remover evento - erro: ' . $e->getMessage(), false);
        }
    }

    private function cleanup() {
        try {
            $this->pdo->beginTransaction();

            if (isset($this->func_inscricao_id)) {
                $this->pdo->prepare("DELETE FROM inscricoes WHERE id = ?")->execute([$this->func_inscricao_id]);
            }

            if (isset($this->func_evento_id)) {
                $this->pdo->prepare("DELETE FROM inscricoes WHERE evento_id = ?")->execute([$this->func_evento_id]);
                $this->pdo->prepare("DELETE FROM eventos WHERE id = ?")->execute([$this->func_evento_id]);
            }

            if (isset($this->func_creator_id)) {
                $this->pdo->prepare("DELETE FROM criadores WHERE id = ?")->execute([$this->func_creator_id]);
            }

            if (isset($this->func_creator_usuario_id)) {
                $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$this->func_creator_usuario_id]);
            }

            if (isset($this->func_user_id)) {
                $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$this->func_user_id]);
            }

            $this->pdo->commit();
            $this->test('Cleanup: Dados de teste removidos', true);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->test('Cleanup: Erro: ' . $e->getMessage(), false);
        }
    }

    private function printResults() {
        echo "\n=== EcoLink Functional Test Results ===\n\n";

        foreach ($this->tests as $test) {
            $status = $test['status'] === 'PASS' ? 'PASSOU' : 'FALHOU';
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

$test = new FunctionalTest($pdo);
$test->run();
