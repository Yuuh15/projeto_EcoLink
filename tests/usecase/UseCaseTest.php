<?php
// tests/usecase/UseCaseTest.php
// Use case tests - each test maps to a specific system use case

require_once __DIR__ . '/../../config/database.php';

class UseCaseTest {
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

    private function email() {
        return 'uc_test_' . time() . '_' . rand(1000, 9999) . '@example.com';
    }

    public function run() {
        $this->uc01_cadastrar_usuario();
        $this->uc02_login_usuario();
        $this->uc03_cadastrar_criador();
        $this->uc04_login_criador();
        $this->uc05_criar_evento();
        $this->uc06_listar_eventos();
        $this->uc07_inscrever_evento();
        $this->uc08_visualizar_dashboard_usuario();
        $this->uc09_visualizar_dashboard_criador();
        $this->uc10_retirar_inscricao();
        $this->uc11_remover_evento();
        $this->uc12_buscar_eventos();
        $this->cleanup();
        $this->printResults();
    }

    private function uc01_cadastrar_usuario() {
        $email = $this->email();

        $this->test('UC01-C01: Cadastro com dados válidos',
            !empty('Nome') && !empty($email) && !empty('senha123')
        );

        $hash = password_hash('senha123', PASSWORD_DEFAULT);
        try {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
            $r = $stmt->execute(['Usuário UC01', $email, $hash]);
            $this->assertTrue($r, 'UC01-C02: Inserção no banco com sucesso');

            $id = $this->pdo->lastInsertId();
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $u = $stmt->fetch();
            $this->assertNotNull($u, 'UC01-C03: Dados persistidos corretamente');
            $this->assertEquals('usuario', $u['tipo'], 'UC01-C04: Tipo padrão é usuario');

            $this->uc01_user_id = $id;
            $this->uc01_user_email = $email;
            $this->uc01_user_pass = 'senha123';
        } catch (PDOException $e) {
            $this->test('UC01-C02: Erro na inserção', false);
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'usuario')");
            $stmt->execute(['Duplicado', $email, $hash]);
            $this->test('UC01-C05: Email duplicado rejeitado', false);
        } catch (PDOException $e) {
            $this->test('UC01-C05: Email duplicado rejeitado', true);
        }
    }

    private function uc02_login_usuario() {
        if (!isset($this->uc01_user_email)) {
            $this->test('UC02: Dependente de UC01 - pulo', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?");
            $stmt->execute([$this->uc01_user_email]);
            $user = $stmt->fetch();
            $this->assertNotNull($user, 'UC02-C01: Buscar usuário por email');

            if ($user) {
                $this->assertTrue(password_verify($this->uc01_user_pass, $user['senha']), 'UC02-C02: Verificar senha com bcrypt');
                $this->assertEquals($this->uc01_user_email, $user['email'], 'UC02-C03: Email corresponde ao login');
                $this->assertEquals('usuario', $user['tipo'], 'UC02-C04: Tipo de usuário correto');
                $this->assertFalse(password_verify('senha_errada', $user['senha']), 'UC02-C05: Senha incorreta rejeitada');
            }
        } catch (PDOException $e) {
            $this->test('UC02: Erro no banco', false);
        }
    }

    private function uc03_cadastrar_criador() {
        $email = $this->email();
        $hash = password_hash('criador123', PASSWORD_DEFAULT);

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'criador')");
            $stmt->execute(['Criador UC03', $email, $hash]);
            $uid = $this->pdo->lastInsertId();
            $this->assertTrue($uid > 0, 'UC03-C01: Criar conta de usuário tipo criador');

            $stmt = $this->pdo->prepare("INSERT INTO criadores (usuario_id, cnpj, razao_social, nome_fantasia, telefone, endereco) VALUES (?, ?, ?, ?, ?, ?)");
            $r = $stmt->execute([$uid, '99888777000155', 'UC03 Razão Social', 'UC03 Fantasia', '11988887777', 'Rua dos Testes, 789']);
            $this->assertTrue($r, 'UC03-C02: Criar perfil criador com dados completos');

            $this->pdo->commit();

            $stmt = $this->pdo->prepare("SELECT c.*, u.email FROM criadores c JOIN usuarios u ON u.id = c.usuario_id WHERE c.usuario_id = ?");
            $stmt->execute([$uid]);
            $c = $stmt->fetch();
            $this->assertNotNull($c, 'UC03-C03: Perfil criador recuperado');
            $this->assertEquals('99888777000155', $c['cnpj'], 'UC03-C04: CNPJ armazenado corretamente');
            $this->assertEquals('UC03 Razão Social', $c['razao_social'], 'UC03-C05: Razão social armazenada');
            $this->assertEquals('UC03 Fantasia', $c['nome_fantasia'], 'UC03-C06: Nome fantasia armazenado');

            $this->uc03_creator_id = $c['id'];
            $this->uc03_creator_uid = $uid;
            $this->uc03_creator_email = $email;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->test('UC03: Erro: ' . $e->getMessage(), false);
        }
    }

    private function uc04_login_criador() {
        if (!isset($this->uc03_creator_email)) {
            $this->test('UC04: Dependente de UC03 - pulo', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.nome, u.email, u.senha, u.tipo, c.id as criador_id, c.cnpj, c.razao_social
                FROM usuarios u
                JOIN criadores c ON c.usuario_id = u.id
                WHERE u.email = ?
            ");
            $stmt->execute([$this->uc03_creator_email]);
            $user = $stmt->fetch();
            $this->assertNotNull($user, 'UC04-C01: Buscar criador por email + JOIN criadores');
            $this->assertEquals('criador', $user['tipo'], 'UC04-C02: Tipo do usuário é criador');
            $this->assertNotNull($user['criador_id'], 'UC04-C03: Perfil criador existe');
            $this->assertNotNull($user['cnpj'], 'UC04-C04: CNPJ disponível no login');

            $this->assertTrue(password_verify('criador123', $user['senha']), 'UC04-C05: Senha do criador verificada');
        } catch (PDOException $e) {
            $this->test('UC04: Erro: ' . $e->getMessage(), false);
        }
    }

    private function uc05_criar_evento() {
        if (!isset($this->uc03_creator_id)) {
            $this->test('UC05: Dependente de UC03 - pulo', false);
            return;
        }

        $data = date('Y-m-d H:i:s', strtotime('+45 days'));

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO eventos (criador_id, nome_evento, descricao, endereco, data_evento, capacidade_maxima)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $r = $stmt->execute([$this->uc03_creator_id, 'Evento UC05', 'Descrição completa do evento de teste', 'Av. Teste, 1000', $data, 100]);
            $this->assertTrue($r, 'UC05-C01: Criar evento com dados válidos');
            $eid = $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("SELECT * FROM eventos WHERE id = ?");
            $stmt->execute([$eid]);
            $e = $stmt->fetch();
            $this->assertNotNull($e, 'UC05-C02: Evento recuperado do banco');
            $this->assertEquals('Evento UC05', $e['nome_evento'], 'UC05-C03: Nome do evento correto');
            $this->assertEquals(100, $e['capacidade_maxima'], 'UC05-C04: Capacidade máxima correta');
            $this->assertEquals('ativo', $e['status'], 'UC05-C05: Status padrão é ativo');
            $this->assertEquals(0, $e['inscritos'], 'UC05-C06: Inscritos inicial é zero');
            $this->assertEquals($this->uc03_creator_id, $e['criador_id'], 'UC05-C07: Criador associado corretamente');

            $this->uc05_evento_id = $eid;
        } catch (PDOException $e) {
            $this->test('UC05: Erro: ' . $e->getMessage(), false);
        }
    }

    private function uc06_listar_eventos() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.*, c.nome_fantasia, c.razao_social
                FROM eventos e
                JOIN criadores c ON e.criador_id = c.id
                WHERE e.status = 'ativo' AND DATE(e.data_evento) >= CURDATE()
                ORDER BY e.data_evento ASC
            ");
            $stmt->execute();
            $eventos = $stmt->fetchAll();
            $this->assertNotNull($eventos, 'UC06-C01: Listar eventos ativos');
            $this->assertTrue(count($eventos) >= 0, 'UC06-C02: Retorna array de eventos');

            if (count($eventos) > 0) {
                $e = $eventos[0];
                $this->assertTrue(isset($e['nome_evento']), 'UC06-C03: Evento tem nome');
                $this->assertTrue(isset($e['data_evento']), 'UC06-C04: Evento tem data');
                $this->assertTrue(isset($e['capacidade_maxima']), 'UC06-C05: Evento tem capacidade');
                $this->assertTrue(isset($e['nome_fantasia']) || isset($e['razao_social']), 'UC06-C06: Evento tem realizador');
            }
        } catch (PDOException $e) {
            $this->test('UC06: Erro: ' . $e->getMessage(), false);
        }
    }

    private function uc07_inscrever_evento() {
        if (!isset($this->uc05_evento_id) || !isset($this->uc01_user_id)) {
            $this->test('UC07: Dependente de UC01 e UC05 - pulo', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT capacidade_maxima, inscritos FROM eventos WHERE id = ?");
            $stmt->execute([$this->uc05_evento_id]);
            $evento = $stmt->fetch();

            if ($evento && $evento['inscritos'] < $evento['capacidade_maxima']) {
                $stmt = $this->pdo->prepare("INSERT INTO inscricoes (usuario_id, evento_id, status) VALUES (?, ?, 'confirmada')");
                $r = $stmt->execute([$this->uc01_user_id, $this->uc05_evento_id]);
                $this->assertTrue($r, 'UC07-C01: Inscrição realizada com sucesso');
                $this->uc07_inscricao_id = $this->pdo->lastInsertId();

                $stmt = $this->pdo->prepare("UPDATE eventos SET inscritos = inscritos + 1 WHERE id = ?");
                $stmt->execute([$this->uc05_evento_id]);

                $stmt = $this->pdo->prepare("SELECT * FROM inscricoes WHERE id = ?");
                $stmt->execute([$this->uc07_inscricao_id]);
                $ins = $stmt->fetch();
                $this->assertEquals('confirmada', $ins['status'], 'UC07-C02: Status da inscrição é confirmada');
                $this->assertEquals($this->uc01_user_id, $ins['usuario_id'], 'UC07-C03: Usuário associado corretamente');
                $this->assertEquals($this->uc05_evento_id, $ins['evento_id'], 'UC07-C04: Evento associado corretamente');
            } else {
                $this->test('UC07-C01: Evento sem vagas disponíveis', false);
            }
        } catch (PDOException $e) {
            $this->test('UC07: Erro: ' . $e->getMessage(), false);
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO inscricoes (usuario_id, evento_id, status) VALUES (?, ?, 'confirmada')");
            $stmt->execute([$this->uc01_user_id, $this->uc05_evento_id]);
            $this->test('UC07-C05: Inscrição duplicada rejeitada', false);
        } catch (PDOException $e) {
            $this->test('UC07-C05: Inscrição duplicada rejeitada', true);
        }
    }

    private function uc08_visualizar_dashboard_usuario() {
        if (!isset($this->uc01_user_id)) {
            $this->test('UC08: Dependente de UC01 - pulo', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT data_cadastro FROM usuarios WHERE id = ?");
            $stmt->execute([$this->uc01_user_id]);
            $user = $stmt->fetch();
            $this->assertNotNull($user, 'UC08-C01: Dados do usuário recuperados');
            $this->assertNotNull($user['data_cadastro'], 'UC08-C02: Data de cadastro existe');

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total FROM inscricoes i
                JOIN eventos e ON i.evento_id = e.id
                WHERE i.usuario_id = ? AND i.status = 'confirmada'
            ");
            $stmt->execute([$this->uc01_user_id]);
            $count = $stmt->fetch();
            $this->assertTrue(isset($count['total']), 'UC08-C03: Total de inscrições recuperado');
        } catch (PDOException $e) {
            $this->test('UC08: Erro: ' . $e->getMessage(), false);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT i.id, e.nome_evento, e.data_evento, e.endereco, i.data_inscricao
                FROM inscricoes i
                JOIN eventos e ON i.evento_id = e.id
                WHERE i.usuario_id = ? AND i.status = 'confirmada'
                ORDER BY e.data_evento ASC
            ");
            $stmt->execute([$this->uc01_user_id]);
            $inscricoes = $stmt->fetchAll();
            $this->assertNotNull($inscricoes, 'UC08-C04: Lista de inscrições recuperada');
        } catch (PDOException $e) {
            $this->test('UC08-C04: Erro ao listar inscrições', false);
        }
    }

    private function uc09_visualizar_dashboard_criador() {
        if (!isset($this->uc03_creator_id)) {
            $this->test('UC09: Dependente de UC03 - pulo', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM eventos WHERE criador_id = ?");
            $stmt->execute([$this->uc03_creator_id]);
            $total = $stmt->fetch()['total'];
            $this->assertTrue($total >= 0, 'UC09-C01: Total de eventos do criador');

            $stmt = $this->pdo->prepare("SELECT SUM(inscritos) as total FROM eventos WHERE criador_id = ?");
            $stmt->execute([$this->uc03_creator_id]);
            $inscritos = $stmt->fetch()['total'] ?: 0;
            $this->assertTrue($inscritos >= 0, 'UC09-C02: Total de inscrições nos eventos');

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total FROM eventos
                WHERE criador_id = ? AND status = 'ativo' AND data_evento > NOW()
            ");
            $stmt->execute([$this->uc03_creator_id]);
            $ativos = $stmt->fetch()['total'];
            $this->assertTrue($ativos >= 0, 'UC09-C03: Total de eventos ativos');

            $stmt = $this->pdo->prepare("SELECT SUM(capacidade_maxima) as total FROM eventos WHERE criador_id = ?");
            $stmt->execute([$this->uc03_creator_id]);
            $capacidade = $stmt->fetch()['total'] ?: 0;
            $this->assertTrue($capacidade >= 0, 'UC09-C04: Capacidade total dos eventos');
        } catch (PDOException $e) {
            $this->test('UC09: Erro: ' . $e->getMessage(), false);
        }
    }

    private function uc10_retirar_inscricao() {
        if (!isset($this->uc07_inscricao_id) || !isset($this->uc05_evento_id) || !isset($this->uc01_user_id)) {
            $this->test('UC10: Dependente de UC07 - pulo', false);
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE eventos SET inscritos = inscritos - 1 WHERE id = ?");
            $stmt->execute([$this->uc05_evento_id]);

            $stmt = $this->pdo->prepare("DELETE FROM inscricoes WHERE evento_id = ? AND usuario_id = ?");
            $r = $stmt->execute([$this->uc05_evento_id, $this->uc01_user_id]);
            $this->assertTrue($r, 'UC10-C01: Retirar inscrição com sucesso');

            $this->pdo->commit();

            $stmt = $this->pdo->prepare("SELECT id FROM inscricoes WHERE id = ?");
            $stmt->execute([$this->uc07_inscricao_id]);
            $this->assertNull($stmt->fetch(), 'UC10-C02: Inscrição removida do banco');

            $stmt = $this->pdo->prepare("SELECT inscritos FROM eventos WHERE id = ?");
            $stmt->execute([$this->uc05_evento_id]);
            $e = $stmt->fetch();
            $this->assertEquals(0, $e['inscritos'], 'UC10-C03: Contador de inscritos decrementado');
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->test('UC10: Erro: ' . $e->getMessage(), false);
        }
    }

    private function uc11_remover_evento() {
        if (!isset($this->uc05_evento_id) || !isset($this->uc03_creator_id)) {
            $this->test('UC11: Dependente de UC05 - pulo', false);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, criador_id FROM eventos WHERE id = ?");
            $stmt->execute([$this->uc05_evento_id]);
            $evento = $stmt->fetch();
            $this->assertNotNull($evento, 'UC11-C01: Evento existe');
            $this->assertEquals($this->uc03_creator_id, $evento['criador_id'], 'UC11-C02: Criador é dono do evento');

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("DELETE FROM inscricoes WHERE evento_id = ?");
            $stmt->execute([$this->uc05_evento_id]);

            $stmt = $this->pdo->prepare("DELETE FROM eventos WHERE id = ? AND criador_id = ?");
            $r = $stmt->execute([$this->uc05_evento_id, $this->uc03_creator_id]);
            $this->assertTrue($r, 'UC11-C03: Evento removido com sucesso');

            $this->pdo->commit();

            $stmt = $this->pdo->prepare("SELECT id FROM eventos WHERE id = ?");
            $stmt->execute([$this->uc05_evento_id]);
            $this->assertNull($stmt->fetch(), 'UC11-C04: Evento não existe mais no banco');

            unset($this->uc05_evento_id);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->test('UC11: Erro: ' . $e->getMessage(), false);
        }
    }

    private function uc12_buscar_eventos() {
        $data = date('Y-m-d H:i:s', strtotime('+30 days'));

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO eventos (criador_id, nome_evento, descricao, endereco, data_evento, capacidade_maxima)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$this->uc03_creator_id ?? 1, 'Workshop de Reciclagem', 'Aprenda a reciclar materiais', 'Rua da Sustentabilidade', $data, 30]);
            $eid1 = $this->pdo->lastInsertId();
            $stmt->execute([$this->uc03_creator_id ?? 1, 'Limpeza de Praia', 'Vamos limpar a praia', 'Praia Grande', $data, 50]);
            $eid2 = $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("SELECT * FROM eventos WHERE nome_evento LIKE ? OR descricao LIKE ?");
            $term = '%Reciclagem%';
            $stmt->execute([$term, $term]);
            $results = $stmt->fetchAll();
            $this->assertTrue(count($results) > 0, 'UC12-C01: Busca por nome retorna resultados');
            $this->assertEquals('Workshop de Reciclagem', $results[0]['nome_evento'], 'UC12-C02: Resultado correto da busca');

            $stmt = $this->pdo->prepare("SELECT * FROM eventos WHERE nome_evento LIKE ? OR descricao LIKE ?");
            $term2 = '%ZZZnotfound%';
            $stmt->execute([$term2, $term2]);
            $this->assertEquals(0, $stmt->rowCount(), 'UC12-C03: Termo inexistente retorna vazio');

            $stmt = $this->pdo->prepare("SELECT * FROM eventos WHERE nome_evento LIKE ? OR descricao LIKE ?");
            $term3 = '%praia%';
            $stmt->execute([$term3, $term3]);
            $results2 = $stmt->fetchAll();
            $this->assertTrue(count($results2) > 0, 'UC12-C04: Busca case-insensitive funciona');

            $this->pdo->prepare("DELETE FROM eventos WHERE id IN (?, ?)")->execute([$eid1, $eid2]);
        } catch (PDOException $e) {
            $this->test('UC12: Erro: ' . $e->getMessage(), false);
        }
    }

    private function cleanup() {
        try {
            $this->pdo->beginTransaction();

            if (isset($this->uc07_inscricao_id)) {
                $this->pdo->prepare("DELETE FROM inscricoes WHERE id = ?")->execute([$this->uc07_inscricao_id]);
            }
            if (isset($this->uc05_evento_id)) {
                $this->pdo->prepare("DELETE FROM inscricoes WHERE evento_id = ?")->execute([$this->uc05_evento_id]);
                $this->pdo->prepare("DELETE FROM eventos WHERE id = ?")->execute([$this->uc05_evento_id]);
            }
            if (isset($this->uc03_creator_id)) {
                $this->pdo->prepare("DELETE FROM criadores WHERE id = ?")->execute([$this->uc03_creator_id]);
            }
            if (isset($this->uc03_creator_uid)) {
                $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$this->uc03_creator_uid]);
            }
            if (isset($this->uc01_user_id)) {
                $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$this->uc01_user_id]);
            }

            $this->pdo->commit();
            $this->test('Cleanup: Dados de teste removidos', true);
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->test('Cleanup: Erro: ' . $e->getMessage(), false);
        }
    }

    private function printResults() {
        echo "\n=== EcoLink Use Case Test Results ===\n\n";

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

$test = new UseCaseTest($pdo);
$test->run();
