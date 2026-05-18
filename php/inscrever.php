<?php
// public/inscrever.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['aviso'] = 1;
    header('Location: index.php');
    exit;
}
elseif ($_SESSION['usuario_tipo'] === 'criador') {
    $_SESSION['aviso'] = 2;
    header('Location: index.php');
    exit;
}


$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrever'])) {
    $evento_id = $_POST['evento_id'] ?? 0;
    
    try {
        // Verificar se evento existe e tem vaga
        $stmt = $pdo->prepare("
            SELECT *, 
                   (SELECT COUNT(*) FROM inscricoes WHERE evento_id = e.id AND status = 'confirmada') as inscritos_atuais
            FROM eventos e 
            WHERE e.id = ? AND e.status = 'ativo'
        ");
        $stmt->execute([$evento_id]);
        $evento = $stmt->fetch();
        
        if (!$evento) {
            $_SESSION['erro_inscricao'] = 'Evento não encontrado ou cancelado.';
            header('Location: listar_eventos.php');
            exit;
        }
        
        // Verificar se já está inscrito
        $stmt = $pdo->prepare("SELECT id FROM inscricoes WHERE usuario_id = ? AND evento_id = ? AND status = 'confirmada'");
        $stmt->execute([$usuario_id, $evento_id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['erro_inscricao'] = 'Você já está inscrito neste evento!';
            header('Location: listar_eventos.php');
            exit;
        }
        
        // Verificar vagas
        $inscritos_atuais = $evento['inscritos_atuais'];
        if ($inscritos_atuais >= $evento['capacidade_maxima']) {
            $_SESSION['erro_inscricao'] = 'Desculpe, as vagas para este evento estão esgotadas!';
            header('Location: listar_eventos.php');
            exit;
        }
        
        // Fazer inscrição
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO inscricoes (usuario_id, evento_id) VALUES (?, ?)");
        $stmt->execute([$usuario_id, $evento_id]);
        
        $stmt = $pdo->prepare("UPDATE eventos SET inscritos = inscritos + 1 WHERE id = ?");
        $stmt->execute([$evento_id]);
        
        $pdo->commit();
        
        $_SESSION['sucesso_inscricao'] = 'Inscrição realizada com sucesso!';
        header('Location: index.php');
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['erro_inscricao'] = 'Erro ao realizar inscrição. Tente novamente.';
        header('Location: index.php');
        exit;
    }
}

header('Location: index.php');
exit;
?>