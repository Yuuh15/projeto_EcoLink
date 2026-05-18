<?php
// public/meus_eventos.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'criador') {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Buscar ID do criador
$criador_id = null;
$stmt = $pdo->prepare("SELECT id FROM criadores WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$criador = $stmt->fetch();
if ($criador) {
    $criador_id = $criador['id'];
}

$eventos_html = '';
$mensagem = '';

if (isset($_SESSION['sucesso_remocao'])) {
    $mensagem = '<div class="mensagem sucesso">' . $_SESSION['sucesso_remocao'] . '</div>';
    unset($_SESSION['sucesso_remocao']);
}
if (isset($_SESSION['erro_remocao'])) {
    $mensagem = '<div class="mensagem erro">' . $_SESSION['erro_remocao'] . '</div>';
    unset($_SESSION['erro_remocao']);
}

try {
    $stmt = $pdo->prepare("
        SELECT e.*, 
               (SELECT COUNT(*) FROM inscricoes WHERE evento_id = e.id AND status = 'confirmada') as inscritos_atuais
        FROM eventos e 
        WHERE e.criador_id = ? 
        ORDER BY e.data_evento ASC
    ");
    $stmt->execute([$criador_id]);
    $eventos = $stmt->fetchAll();
    
    if (count($eventos) > 0) {
        foreach ($eventos as $evento) {
            $data_evento = date('d/m/Y H:i', strtotime($evento['data_evento']));
            $status_class = $evento['status'] === 'ativo' ? 'sucesso' : 'erro';
            $inscritos_atuais = $evento['inscritos_atuais'];
            $vagas_restantes = $evento['capacidade_maxima'] - $inscritos_atuais;
            
            // Buscar inscritos deste evento
            $stmt2 = $pdo->prepare("
                SELECT u.nome, u.email, i.data_inscricao 
                FROM inscricoes i
                JOIN usuarios u ON i.usuario_id = u.id
                WHERE i.evento_id = ? AND i.status = 'confirmada'
            ");
            $stmt2->execute([$evento['id']]);
            $inscritos = $stmt2->fetchAll();
            
            $inscritos_lista = '';
            foreach ($inscritos as $inscrito) {
                $inscritos_lista .= '<li>' . htmlspecialchars($inscrito['nome']) . ' - ' . htmlspecialchars($inscrito['email']) . '</li>';
            }
            
            $botao = '<form method = "POST" action="remover_evento.php">
                <input type="hidden" name="evento_id" values="'. $evento['id'] .'">
                <button type="submit" name="remover_eve" class="retirar-inscricao">Remover evento</button>
                </form>';

            $eventos_html .= '
                <div class="card-evento">
                    <h4>' . htmlspecialchars($evento['nome_evento']) . '</h4>
                    <p class="endereco">' . htmlspecialchars($evento['endereco']) . '</p>
                    <p class="data">Data: ' . $data_evento . '</p>
                    <p><strong>Capacidade:</strong> ' . $evento['capacidade_maxima'] . ' pessoas</p>
                    <p><strong>Inscritos:</strong> ' . $inscritos_atuais . ' | <strong>Vagas:</strong> ' . $vagas_restantes . '</p>
                    <p><strong>Status:</strong> <span class="' . $status_class . '">' . $evento['status'] . '</span></p>
                    
                    <details>
                        <summary>Lista de Inscritos (' . count($inscritos) . ')</summary>
                        <ul style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
                            ' . ($inscritos_lista ?: '<li>Nenhum inscrito ainda</li>') . '
                        </ul>
                    </details>

                    '. $botao .'

                </div>
            ';
        }
    } else {
        $eventos_html = '<div class="sem-eventos">Você ainda não criou nenhum evento.<br><a href="criar_evento.php">Criar meu primeiro evento</a></div>';
    }
} catch (PDOException $e) {
    $mensagem = '<div class="mensagem erro">Erro ao carregar eventos</div>';
}

ob_start();
include '../view/meus_eventos.html';
$html = ob_get_clean();

$html = str_replace('<!-- MENSAGEM_AREA -->', $mensagem, $html);
$html = str_replace('<!-- LISTA_EVENTOS -->', $eventos_html, $html);
$html = str_replace('<!-- MENSAGEM_AREA -->', $mensagem, $html);



echo $html;
?>