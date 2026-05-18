<?php

session_start();
require_once '../config/database.php';

$erro = '';

if(isset($_SESSION['aviso'])){
    session_destroy();
    echo "<script>
    alert('Email de criador, faça o login colocando o CPNJ e email');
    window.location.href='login_criador.php';
    </script>";
    $_SESSION['aviso']=null;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($cnpj) || empty($senha)) {
        $erro = 'Preencha todos os campos!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, razao_social, nome_fantasia, telefone, endereco, cnpj FROM criadores WHERE cnpj = ?");
            $stmt->execute([$cnpj]);
            $criador = $stmt->fetch();

            $stmt = $pdo->prepare("SELECT id, nome, email, senha, data_cadastro, tipo FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($criador && password_verify($senha, $usuario['senha'])) {
                $_SESSION['criador_id'] = $criador['id'];
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_tipo'] = $usuario['tipo'];
                $_SESSION['criador_email'] = $usuario['email'];
                $_SESSION['criador_nome'] = $usuario['nome'];
                $_SESSION['criador_data_cadastro'] = $usuario['data_cadastro'];
                $_SESSION['criador_razao_social'] = $criador['razao_social'];
                $_SESSION['criador_nome_fantasia'] = $criador['nome_fantasia'];
                $_SESSION['criador_endereco'] = $criador['endereco'];
                $_SESSION['criador_telefone'] = $criador['telefone'];
                $_SESSION['criador_cnpj'] = $criador['cnpj'];

                header('Location: index.php');

                exit;
            } else {
                $erro = 'Email, cnpj ou senha inválidos!';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no banco de dados!';
        }
    }
}

ob_start();
include '../view/login_criador.html';
$html = ob_get_clean();

if ($erro) {
    $html = str_replace('<!-- MENSAGEM_AREA -->', '<div class="mensagem erro">' . $erro .'</div>', $html);
} else {
    $html = str_replace('<!-- MENSAGEM_AREA -->', '', $html);
}

echo $html;
?>