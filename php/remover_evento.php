<?php

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_criador.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Buscar ID do criador
$stmt = $pdo->prepare("SELECT id FROM criadores WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$criador = $stmt->fetch();
if (!$criador) {
    header('Location: meus_eventos.php');
    exit;
}
$criador_id = $criador['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_eve'])) {
    $evento_id = $_POST['evento_id'] ?? 0;

    if ($evento_id <= 0) {
        $_SESSION['erro_remocao'] = 'Evento inválido.';
        header('Location: meus_eventos.php');
        exit;
    }

    try {
        // Verificar se o evento pertence ao criador
        $stmt = $pdo->prepare("SELECT id FROM eventos WHERE id = ? AND criador_id = ?");
        $stmt->execute([$evento_id, $criador_id]);
        if (!$stmt->fetch()) {
            $_SESSION['erro_remocao'] = 'Evento não encontrado ou não pertence a você.';
            header('Location: meus_eventos.php');
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM inscricoes WHERE evento_id = ?");
        $stmt->execute([$evento_id]);

        $stmt = $pdo->prepare("DELETE FROM eventos WHERE id = ? AND criador_id = ?");
        $stmt->execute([$evento_id, $criador_id]);

        $pdo->commit();

        $_SESSION['sucesso_remocao'] = 'Remoção realizada';
        header('Location: meus_eventos.php');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['erro_remocao'] = 'Erro ao remover evento. Tente novamente.';
        header('Location: meus_eventos.php');
        exit;
    }
}

header('Location: meus_eventos.php');
exit;

?>