<?php
// public/login.php
session_start();
require_once '../config/database.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_tipo'] = $usuario['tipo'];
                

                header('Location: index.php');
                
                exit;
            } else {
                $erro = ' Email ou senha inválidos!';
            }
        } catch (PDOException $e) {
            $erro = ' Erro no banco de dados!';
        }
    }
}

ob_start();
include '../view/login.html';
$html = ob_get_clean();

if ($erro) {
    $html = str_replace('<!-- MENSAGEM_AREA -->', '<div class="mensagem erro">' . $erro . '</div>', $html);
} else {
    $html = str_replace('<!-- MENSAGEM_AREA -->', '', $html);
}

echo $html;
?>