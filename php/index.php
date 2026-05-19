<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// public/listar_eventos.php
session_start();
require_once '../config/database.php';

if(isset($_SESSION['aviso']) && $_SESSION['aviso']=== 1){
    echo "<script>
    alert('faça login para se inscrever em um evento');
    window.location.href='index.php';
    </script>";
    $_SESSION['aviso']=null;
}elseif(isset($_SESSION['aviso']) && $_SESSION['aviso']=== 2){
    echo "<script>
    alert('criadores não podem se inscrever em um evento');
    window.location.href='index.php';
    </script>";
    $_SESSION['aviso']=null;
}

$eventos_html = '';
$mensagem = '';

// Verificar se tem mensagem de sessão
if (isset($_SESSION['erro_inscricao'])) {
    $mensagem = '<div class="mensagem erro">' . $_SESSION['erro_inscricao'] . '</div>';
    unset($_SESSION['erro_inscricao']);
}

try {

    if (isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['usuario_id'];

        $stmt2 = $pdo -> prepare("
        SELECT (SELECT status 
        FROM inscricoes 
        WHERE usuario_id = ? AND evento_id=e.id) as meu_status 
        FROM eventos e 
        WHERE DATE(e.data_evento) >= CURDATE()
        ORDER BY e.data_evento ASC
        ");

        $stmt2->execute([$usuario_id]);

        $status = $stmt2->fetchAll();

        $stmt3 = $pdo->prepare("
        SELECT u.nome, u.tipo
        FROM usuarios u
        WHERE u.id = ?
        ");

        $stmt3->execute([$_SESSION['usuario_id']]);

        $login_text = $stmt3->fetch();
    }

    $stmt = $pdo->prepare("
        SELECT e.*, 
               c.nome_fantasia, c.razao_social,
               (SELECT COUNT(*) FROM inscricoes WHERE evento_id = e.id AND status = 'confirmada') as inscritos_atuais
        FROM eventos e
        JOIN criadores c ON e.criador_id = c.id
        WHERE e.status = 'ativo' AND DATE(data_evento) >= CURDATE()
        ORDER BY e.data_evento ASC
    ");
    $stmt->execute();
    $eventos = $stmt->fetchAll();
    


    if (count($eventos) > 0) {
        $i = 0;
        foreach ($eventos as $evento) {
            $data_evento = date('d/m/Y H:i', strtotime($evento['data_evento']));
            $inscritos_atuais = $evento['inscritos_atuais'];
            $vagas_restantes = $evento['capacidade_maxima'] - $inscritos_atuais;
            $vagas_disponiveis = $vagas_restantes > 0;
            
            $eventos_html .= '
                <article class="card">
                    <div class="card-conteudo">
                        <p class="data-evento">' . $data_evento . '</p>
                        <h3>' . htmlspecialchars($evento['nome_evento']) . '</h3>
                        <p class="realizador"><strong>Realizado por:</strong> ' . htmlspecialchars($evento['nome_fantasia'] ?: $evento['razao_social']) . '</p>
                        <div class="desc">' . nl2br(htmlspecialchars($evento['descricao'])) . '</div>
                        <p class="capacidade">Vagas: ' . $vagas_restantes . ' / ' . $evento['capacidade_maxima'] . '</p>
                        <div class="card-actions">';
            
            if (isset($status[$i]['meu_status'])) {
                $eventos_html .= '<button class="btn-inscrever" disabled>✓ Você já está inscrito</button>';
            } elseif (!$vagas_disponiveis) {
                $eventos_html .= '<button class="btn-inscrever" disabled>Esgotado</button>';
            } else {
                $eventos_html .= '<form method="POST" action="inscrever.php">
                                    <input type="hidden" name="evento_id" value="' . $evento['id'] . '">
                                    <button type="submit" name="inscrever" class="btn-inscrever">Inscrever-se</button>
                                 </form>';
            }
            
            $eventos_html .= '      </div>
                    </div>
                </article>';
            $i++;
        }
    } else {
        $eventos_html = '<div class="sem-eventos">Não há eventos disponíveis no momento.<br>Volte em breve!</div>';
    }
} catch (PDOException $e) {
    $mensagem = '<div class="mensagem erro">Erro ao carregar eventos</div>';
}

ob_start();
include '../view/index.html';
$html = ob_get_clean();


$html = str_replace('<!-- MENSAGEM_AREA -->', $mensagem, $html);
$html = str_replace('<!-- CARDS -->', $eventos_html, $html);
if(isset($login_text) && $login_text['tipo'] === 'usuario'){
    $html = str_replace('<a href="../php/login.php"><img class="img" src="../files/avatar_usuario.jpeg"></a>', '<a href="dashboard_usuario.php"><img class="img" src="../files/avatar_usuario.jpeg"></a>', $html);
}
elseif(isset($login_text) && $login_text['tipo'] === 'criador'){
    $html = str_replace('<a href="../php/login.php"><img class="img" src="../files/avatar_usuario.jpeg"></a>', '<a href="/php/dashboard_criador.php"><img class="img" src="../files/avatar_criador.jpeg"></a>', $html);
}

echo $html;
?>
