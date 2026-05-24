
<?php
// public/listar_eventos.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['aviso'] = 1;
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$eventos_html = '';
$mensagem = '';

// Verificar se tem mensagem de sessão
if (isset($_SESSION['erro_inscricao'])) {
    $mensagem = '<div class="mensagem erro">' . $_SESSION['erro_inscricao'] . '</div>';
    unset($_SESSION['erro_inscricao']);
}

try {
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
    
    if (count($eventos) > 0) {
        $i = 0;
        foreach ($eventos as $evento) {
            $data_evento = date('d/m/Y H:i', strtotime($evento['data_evento']));
            $inscritos_atuais = $evento['inscritos_atuais'];
            $vagas_restantes = $evento['capacidade_maxima'] - $inscritos_atuais;

            $vagas_disponiveis = $vagas_restantes > 0;
            
            $botao = '';
            if (isset($status[$i]['meu_status'])) {
                $botao = '<button class="btn-inscrever" disabled>✓ Você já está inscrito</button>';
            } elseif (!$vagas_disponiveis) {
                $botao = '<button class="btn-inscrever" disabled> Esgotado</button>';
            } else {
                $botao = '<form method="POST" action="inscrever.php">
                            <input type="hidden" name="evento_id" value="' . $evento['id'] . '">
                            <button type="submit" name="inscrever" class="btn-inscrever">Inscrever-se</button>
                          </form>'; 
            }
            
            $eventos_html .= '
                <div class="card-evento" >
                    <h4>' . htmlspecialchars($evento['nome_evento']) . '</h4>
                    <p><strong>Realizado por:</strong> ' . $evento['razao_social'] . '</p>
                    <p class="endereco">' . htmlspecialchars($evento['endereco']) . '</p>
                    <p class="data">Data: ' . $data_evento . '</p>
                    <p style="padding: 12px; margin: 12px 0; border-radius: 8px; font-size: 14px; line-height: 1.6; color: #444; max-height: 120px; overflow-y: auto; word-wrap: break-word; word-break: break-word; white-space: pre-wrap; max-width: 100%;">' . nl2br(htmlspecialchars($evento['descricao'])) . '</p>
                    <p class="capacidade">Vagas: ' . $vagas_restantes . ' / ' . $evento['capacidade_maxima'] . '</p>
                    ' . $botao . '
                </div>
            ';
            $i++;
        }
    } else {
        $eventos_html = '<div class="sem-eventos">Não há eventos disponíveis no momento.<br>Volte em breve!</div>';
    }
} catch (PDOException $e) {
    $mensagem = '<div class="mensagem erro">Erro ao carregar eventos</div>';
}

ob_start();
include '../view/listar_eventos.html';
$html = ob_get_clean();

$html = str_replace('<!-- MENSAGEM_AREA -->', $mensagem, $html);
$html = str_replace('<!-- LISTA_EVENTOS -->', $eventos_html, $html);

echo $html;
?>