<?php
// ============================================================
// sinistro_engine.php — Motor de Análise de Sinistros
//
// Critérios baseados em práticas reais de seguradoras:
// SUSEP Circular 256/04, CNSP 336/16, práticas de SIU
// (Special Investigation Unit) e scoring antifraude.
//
// GET  ?listar_sinistros=1            → lista todos os sinistros
// GET  ?sinistro_id=N                 → detalhes de um sinistro
// GET  ?apolices_cliente&cpf=X        → apólices ativas de um CPF
// POST JSON { ... }                   → registra novo sinistro
// PATCH JSON { id, status, decisao }  → atualiza decisão
// ============================================================
session_start();
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Sessão expirada.']);
    exit;
}

$method     = $_SERVER['REQUEST_METHOD'];
$analistaId = (int) $_SESSION['usuario_id'];

// ============================================================
// TABELAS DE REFERÊNCIA — ANÁLISE DE SINISTROS
// ============================================================

/**
 * Score antifraude — retorna 0 (sem suspeita) a 100 (alto risco)
 * Baseado em: tempo desde emissão, valor × cobertura, histórico,
 * hora do evento, tipo de sinistro × produto.
 */
function calcularScoreFraude(array $dados, array $apolice, array $historico): array {
    $score    = 0;
    $alertas  = [];
    $fatores  = [];

    // ── 1. Tempo desde emissão da apólice ────────────────────
    // Fraudes comuns: sinistro logo após contratação
    $diasApolice = (int)(
        (strtotime($dados['data_ocorrencia']) - strtotime($apolice['data_inicio'])) / 86400
    );
    if ($diasApolice < 30) {
        $score += 35;
        $alertas[] = "Sinistro registrado {$diasApolice} dias após emissão da apólice (< 30 dias).";
        $fatores['tempo_apolice'] = ['pts'=>35,'label'=>"Apólice muito recente ({$diasApolice} dias)",'nivel'=>'alto'];
    } elseif ($diasApolice < 90) {
        $score += 15;
        $alertas[] = "Apólice com menos de 90 dias na data do sinistro.";
        $fatores['tempo_apolice'] = ['pts'=>15,'label'=>"Apólice jovem ({$diasApolice} dias)",'nivel'=>'medio'];
    } else {
        $fatores['tempo_apolice'] = ['pts'=>0,'label'=>"Apólice com {$diasApolice} dias (normal)",'nivel'=>'baixo'];
    }

    // ── 2. Proporção valor sinistro × cobertura ───────────────
    $cobertura = (float)($apolice['valor_cobertura'] ?? 1);
    $valorSin  = (float)($dados['valor_estimado']    ?? 0);
    $ratio     = $cobertura > 0 ? $valorSin / $cobertura : 0;

    if ($ratio >= 0.9) {
        $score += 30;
        $alertas[] = sprintf('Valor do sinistro (%.0f%% da cobertura) muito próximo ao limite máximo.', $ratio * 100);
        $fatores['valor_ratio'] = ['pts'=>30,'label'=>sprintf('%.0f%% da cobertura', $ratio*100),'nivel'=>'alto'];
    } elseif ($ratio >= 0.6) {
        $score += 12;
        $fatores['valor_ratio'] = ['pts'=>12,'label'=>sprintf('%.0f%% da cobertura', $ratio*100),'nivel'=>'medio'];
    } else {
        $fatores['valor_ratio'] = ['pts'=>0,'label'=>sprintf('%.0f%% da cobertura', $ratio*100),'nivel'=>'baixo'];
    }

    // ── 3. Histórico de sinistros anteriores ──────────────────
    $qtdHistorico = count($historico);
    if ($qtdHistorico >= 3) {
        $score += 25;
        $alertas[] = "{$qtdHistorico} sinistros anteriores registrados para este cliente.";
        $fatores['historico'] = ['pts'=>25,'label'=>"{$qtdHistorico} sinistros anteriores",'nivel'=>'alto'];
    } elseif ($qtdHistorico >= 1) {
        $score += 10;
        $fatores['historico'] = ['pts'=>10,'label'=>"{$qtdHistorico} sinistro(s) anterior(es)",'nivel'=>'medio'];
    } else {
        $fatores['historico'] = ['pts'=>0,'label'=>'Sem histórico de sinistros','nivel'=>'baixo'];
    }

    // ── 4. Horário do evento ──────────────────────────────────
    $hora = (int) date('H', strtotime($dados['hora_ocorrencia'] ?? '12:00'));
    if ($hora >= 0 && $hora < 5) {
        $score += 10;
        $alertas[] = 'Ocorrência entre meia-noite e 5h — horário de maior risco de fraude.';
        $fatores['horario'] = ['pts'=>10,'label'=>"Madrugada ({$hora}h)",'nivel'=>'medio'];
    } else {
        $fatores['horario'] = ['pts'=>0,'label'=>'Horário normal','nivel'=>'baixo'];
    }

    // ── 5. Inconsistências de documentação ────────────────────
    $docsPendentes = (int)($dados['docs_pendentes'] ?? 0);
    if ($docsPendentes >= 3) {
        $score += 20;
        $alertas[] = "{$docsPendentes} documentos obrigatórios não entregues.";
        $fatores['documentacao'] = ['pts'=>20,'label'=>"{$docsPendentes} documentos pendentes",'nivel'=>'alto'];
    } elseif ($docsPendentes >= 1) {
        $score += 8;
        $fatores['documentacao'] = ['pts'=>8,'label'=>"{$docsPendentes} documento(s) pendente(s)",'nivel'=>'medio'];
    } else {
        $fatores['documentacao'] = ['pts'=>0,'label'=>'Documentação completa','nivel'=>'baixo'];
    }

    // ── 6. Tipo de sinistro × produto ─────────────────────────
    $tiposSuspeitos = [
        'automovel' => ['furto_simples','roubo_carga'],
        'residencial'=> ['incendio','furto_simples'],
        'vida'       => ['morte_acidental'],
        'saude'      => ['internacao_eletiva'],
    ];
    $produto    = strtolower($apolice['tipo_seguro'] ?? '');
    $tipoSin    = strtolower($dados['tipo_sinistro'] ?? '');
    $suspeitos  = $tiposSuspeitos[$produto] ?? [];
    if (in_array($tipoSin, $suspeitos)) {
        $score += 15;
        $alertas[] = "Tipo de sinistro '$tipoSin' apresenta alta incidência de fraude para $produto.";
        $fatores['tipo'] = ['pts'=>15,'label'=>"Tipo suspeito para $produto",'nivel'=>'medio'];
    } else {
        $fatores['tipo'] = ['pts'=>0,'label'=>'Tipo de sinistro dentro do padrão','nivel'=>'baixo'];
    }

    $score = min(100, $score);

    // ── Classificação de risco ────────────────────────────────
    if ($score >= 60) {
        $nivel = 'ALTO'; $cor = 'red';
        $recomendacao = 'Encaminhar para SIU (Unidade de Investigação Especial). Suspender pagamento até conclusão.';
    } elseif ($score >= 30) {
        $nivel = 'MÉDIO'; $cor = 'amber';
        $recomendacao = 'Solicitar vistoria presencial e documentação complementar antes da regulação.';
    } else {
        $nivel = 'BAIXO'; $cor = 'green';
        $recomendacao = 'Perfil de risco aceitável. Prosseguir com regulação padrão.';
    }

    return [
        'score'         => $score,
        'nivel'         => $nivel,
        'cor'           => $cor,
        'fatores'       => $fatores,
        'alertas'       => $alertas,
        'recomendacao'  => $recomendacao,
        'dias_apolice'  => $diasApolice,
        'ratio_cobertura' => round($ratio * 100, 1),
    ];
}

/**
 * Calcula indenização devida com base nos critérios SUSEP/CNSP
 */
function calcularIndenizacao(array $dados, array $apolice, float $scoreFraude): array {
    $valorSolicitado = (float)($dados['valor_estimado']   ?? 0);
    $cobertura       = (float)($apolice['valor_cobertura'] ?? 0);
    $franquia        = (float)($dados['franquia']          ?? 0);
    $depreciacao     = (float)($dados['depreciacao_pct']   ?? 0) / 100;

    // Limita ao teto de cobertura
    $baseCalculo = min($valorSolicitado, $cobertura);

    // Aplica depreciação (principalmente auto e residencial)
    $baseCalculo -= $baseCalculo * $depreciacao;

    // Deduz franquia
    $baseCalculo -= $franquia;

    // Redutor por suspeita de fraude (análise parcial pendente)
    $redutor = 0;
    if ($scoreFraude >= 60) {
        $redutor     = 1.0;  // suspende pagamento
        $obs         = 'Pagamento suspenso — investigação de fraude em andamento.';
    } elseif ($scoreFraude >= 30) {
        $redutor     = 0.0;  // paga, mas anota ressalva
        $obs         = 'Pagamento liberado com ressalva — vistoria confirmada.';
    } else {
        $obs         = 'Pagamento liberado — risco baixo.';
    }

    $valorFinal = max(0, $baseCalculo * (1 - $redutor));

    return [
        'valor_solicitado' => $valorSolicitado,
        'cobertura_maxima' => $cobertura,
        'franquia'         => $franquia,
        'depreciacao_pct'  => $depreciacao * 100,
        'valor_aprovado'   => round($valorFinal, 2),
        'suspenso'         => $redutor === 1.0,
        'obs'              => $obs,
    ];
}

// ============================================================
// ROTEAMENTO
// ============================================================
try {

    /* ── Listar todos os sinistros ──────────────────────────── */
    if ($method === 'GET' && isset($_GET['listar_sinistros'])) {
        $status = trim($_GET['status'] ?? '');
        $busca  = trim($_GET['busca']  ?? '');

        $where  = [];
        $params = [];

        if ($status !== '') { $where[] = 's.status = :status'; $params[':status'] = $status; }
        if ($busca  !== '') {
            $where[] = '(u.nome LIKE :busca OR u.cpf LIKE :busca2 OR a.numero_apolice LIKE :busca3)';
            $params[':busca'] = $params[':busca2'] = $params[':busca3'] = "%$busca%";
        }

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("
            SELECT
                s.id, s.numero_sinistro, s.tipo_sinistro,
                s.data_ocorrencia, s.valor_estimado, s.valor_aprovado,
                s.status, s.score_fraude, s.nivel_fraude,
                s.created_at,
                u.nome  AS cliente,
                u.cpf,
                a.numero_apolice,
                a.tipo_seguro,
                f.nome  AS analista
            FROM sinistros s
            JOIN apolices    a ON a.id = s.apolice_id
            JOIN usuarios    u ON u.id = a.usuario_id
            LEFT JOIN funcionarios f ON f.id = s.analista_id
            $whereSql
            ORDER BY s.id DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $sinistros = $stmt->fetchAll();

        // KPIs
        $kpi = $pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(status='aberto')      AS abertos,
                SUM(status='em_analise')  AS em_analise,
                SUM(status='aprovado')    AS aprovados,
                SUM(status='recusado')    AS recusados,
                SUM(status='suspenso')    AS suspensos,
                COALESCE(SUM(valor_aprovado),0) AS total_pago
            FROM sinistros
        ")->fetch();

        echo json_encode(['sinistros' => $sinistros, 'kpi' => $kpi]);
        exit;
    }

    /* ── Detalhe de um sinistro ─────────────────────────────── */
    if ($method === 'GET' && isset($_GET['sinistro_id'])) {
        $id   = (int)$_GET['sinistro_id'];
        $stmt = $pdo->prepare("
            SELECT s.*,
                   u.nome AS cliente_nome, u.cpf, u.email, u.telefone,
                   a.numero_apolice, a.tipo_seguro,
                   a.valor_cobertura, a.data_inicio, a.data_fim,
                   f.nome AS analista_nome
            FROM sinistros s
            JOIN apolices    a ON a.id = s.apolice_id
            JOIN usuarios    u ON u.id = a.usuario_id
            LEFT JOIN funcionarios f ON f.id = s.analista_id
            WHERE s.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $sin = $stmt->fetch();
        if (!$sin) throw new Exception('Sinistro não encontrado.');
        echo json_encode(['sinistro' => $sin]);
        exit;
    }

    /* ── Apólices ativas de um CPF (para abrir sinistro) ────── */
    if ($method === 'GET' && isset($_GET['apolices_cliente'])) {
        $cpf  = preg_replace('/\D/', '', $_GET['cpf'] ?? '');
        if (strlen($cpf) !== 11) throw new Exception('CPF inválido.');

        $stmt = $pdo->prepare("
            SELECT a.id, a.numero_apolice, a.tipo_seguro,
                   a.valor_cobertura, a.data_inicio, a.data_fim,
                   u.id AS usuario_id, u.nome, u.cpf AS cpf_fmt
            FROM apolices a
            JOIN usuarios u ON u.id = a.usuario_id
            WHERE u.cpf = :cpf AND a.status = 'ativa'
            ORDER BY a.tipo_seguro
        ");
        $stmt->execute([':cpf' => $cpf]);
        $apolices = $stmt->fetchAll();

        if (!$apolices) {
            echo json_encode(['apolices' => [], 'erro' => 'Nenhuma apólice ativa para este CPF.']);
            exit;
        }
        echo json_encode(['apolices' => $apolices, 'cliente' => [
            'nome' => $apolices[0]['nome'],
            'cpf'  => $apolices[0]['cpf_fmt'],
        ]]);
        exit;
    }

    /* ── POST: registrar novo sinistro ──────────────────────── */
    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $apoliceId      = (int)   ($body['apolice_id']      ?? 0);
        $tipoSinistro   = trim(    $body['tipo_sinistro']   ?? '');
        $dataOcorrencia = trim(    $body['data_ocorrencia'] ?? '');
        $horaOcorrencia = trim(    $body['hora_ocorrencia'] ?? '12:00');
        $valorEstimado  = (float) ($body['valor_estimado']  ?? 0);
        $descricao      = trim(    $body['descricao']       ?? '');
        $franquia       = (float) ($body['franquia']        ?? 0);
        $depreciacaoPct = (float) ($body['depreciacao_pct'] ?? 0);
        $docsPendentes  = (int)   ($body['docs_pendentes']  ?? 0);
        $condicoes      = $body['condicoes'] ?? [];

        if (!$apoliceId || !$tipoSinistro || !$dataOcorrencia || $valorEstimado <= 0) {
            throw new Exception('Campos obrigatórios ausentes.');
        }

        // Busca apólice
        $stmtAp = $pdo->prepare("SELECT * FROM apolices WHERE id = :id AND status = 'ativa' LIMIT 1");
        $stmtAp->execute([':id' => $apoliceId]);
        $apolice = $stmtAp->fetch();
        if (!$apolice) throw new Exception('Apólice não encontrada ou não está ativa.');

        // Valida data dentro da vigência
        if ($dataOcorrencia < $apolice['data_inicio'] || $dataOcorrencia > $apolice['data_fim']) {
            throw new Exception('Data do sinistro fora do período de vigência da apólice.');
        }

        // Histórico de sinistros do cliente
        $stmtHist = $pdo->prepare("
            SELECT s.id FROM sinistros s
            JOIN apolices a ON a.id = s.apolice_id
            WHERE a.usuario_id = (SELECT usuario_id FROM apolices WHERE id = :id)
              AND s.status NOT IN ('recusado')
        ");
        $stmtHist->execute([':id' => $apoliceId]);
        $historico = $stmtHist->fetchAll();

        // Motor antifraude
        $dadosMotor = [
            'data_ocorrencia' => $dataOcorrencia,
            'hora_ocorrencia' => $horaOcorrencia,
            'valor_estimado'  => $valorEstimado,
            'tipo_sinistro'   => $tipoSinistro,
            'docs_pendentes'  => $docsPendentes,
        ];
        $analise       = calcularScoreFraude($dadosMotor, $apolice, $historico);
        $indenizacao   = calcularIndenizacao(
            array_merge($dadosMotor, ['franquia' => $franquia, 'depreciacao_pct' => $depreciacaoPct]),
            $apolice,
            $analise['score']
        );

        // Status inicial baseado no score
        $statusInicial = $analise['score'] >= 60 ? 'suspenso' : 'em_analise';

        // Número único de sinistro
        $numero = 'SIN-' . strtoupper(substr($apolice['tipo_seguro'], 0, 3)) . '-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO sinistros
                (numero_sinistro, apolice_id, analista_id, tipo_sinistro,
                 data_ocorrencia, hora_ocorrencia, valor_estimado, valor_aprovado,
                 franquia, depreciacao_pct, descricao, status,
                 score_fraude, nivel_fraude, alertas_fraude,
                 recomendacao, docs_pendentes)
            VALUES
                (:num, :ap, :ana, :tipo,
                 :data, :hora, :vest, :vaprov,
                 :franq, :dep, :desc, :status,
                 :score, :nivel, :alertas,
                 :rec, :docs)
        ");
        $stmt->execute([
            ':num'    => $numero,
            ':ap'     => $apoliceId,
            ':ana'    => $analistaId,
            ':tipo'   => $tipoSinistro,
            ':data'   => $dataOcorrencia,
            ':hora'   => $horaOcorrencia,
            ':vest'   => $valorEstimado,
            ':vaprov' => $indenizacao['valor_aprovado'],
            ':franq'  => $franquia,
            ':dep'    => $depreciacaoPct,
            ':desc'   => $descricao,
            ':status' => $statusInicial,
            ':score'  => $analise['score'],
            ':nivel'  => $analise['nivel'],
            ':alertas'=> json_encode($analise['alertas'], JSON_UNESCAPED_UNICODE),
            ':rec'    => $analise['recomendacao'],
            ':docs'   => $docsPendentes,
        ]);

        $sinistroId = (int)$pdo->lastInsertId();

        // ── Atualiza status da apólice com base no score de fraude ──
        // Score alto (≥60) → suspende a apólice
        // Score médio (30-59) → mantém ativa mas registra ocorrência
        $novoStatusApolice = $analise['score'] >= 60 ? 'suspensa' : 'ativa';
        $pdo->prepare("UPDATE apolices SET status = :st WHERE id = :id")
            ->execute([':st' => $novoStatusApolice, ':id' => $apoliceId]);

        // ── Salva score de risco real na tabela clientes ──
        // Usa a escala 0-10 (score 0-100 → 0-10) com origem 'sinistro'
        // para diferenciar do score antigo do dashboard
        $scoreNormalizado = round($analise['score'] / 10, 1); // 0-100 → 0-10
        $pdo->prepare("
            INSERT INTO clientes (usuario_id, Ins_Age, BMI, Response)
            SELECT :uid,
                   TIMESTAMPDIFF(YEAR, u.datanascimento, CURDATE()),
                   0,
                   :score
            FROM usuarios u
            WHERE u.id = (SELECT usuario_id FROM apolices WHERE id = :apid)
        ")->execute([
            ':uid'   => $apolice['usuario_id'],
            ':score' => $scoreNormalizado,
            ':apid'  => $apoliceId,
        ]);

        echo json_encode([
            'status'             => 'sucesso',
            'numero_sinistro'    => $numero,
            'id'                 => $sinistroId,
            'analise_fraude'     => $analise,
            'indenizacao'        => $indenizacao,
            'apolice_atualizada' => [
                'id'     => $apoliceId,
                'status' => $novoStatusApolice,
            ],
        ]);
        exit;
    }

    /* ── PATCH: atualizar status/decisão ────────────────────── */
    if ($method === 'PATCH') {
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $id      = (int)  ($body['id']      ?? 0);
        $novoSt  = trim(   $body['status']  ?? '');
        $obs     = trim(   $body['obs']     ?? '');
        $allowed = ['aberto','em_analise','aprovado','recusado','suspenso','pago'];

        if (!$id || !in_array($novoSt, $allowed)) throw new Exception('Dados inválidos.');

        // Atualiza sinistro
        $stmt = $pdo->prepare("
            UPDATE sinistros
            SET status = :st,
                observacao_analista = CONCAT(COALESCE(observacao_analista,''), :obs),
                analista_id = :ana
            WHERE id = :id
        ");
        $stmt->execute([':st'=>$novoSt, ':obs'=>$obs ? "\n[$novoSt] $obs" : '', ':ana'=>$analistaId, ':id'=>$id]);

        if ($stmt->rowCount() === 0) throw new Exception('Sinistro não encontrado.');

        // ── Atualiza status da apólice vinculada ──────────────
        // Busca o apolice_id deste sinistro
        $stmtAp = $pdo->prepare("SELECT apolice_id FROM sinistros WHERE id = :id LIMIT 1");
        $stmtAp->execute([':id' => $id]);
        $row = $stmtAp->fetch();

        if ($row) {
            // Mapeamento: decisão do sinistro → status da apólice
            $mapa = [
                'aprovado'   => 'ativa',      // aprovado → apólice volta ao normal
                'pago'       => 'ativa',       // pago → apólice continua ativa
                'recusado'   => 'ativa',       // recusado → sem impacto, volta ativa
                'suspenso'   => 'suspensa',    // suspenso → apólice suspensa
                'em_analise' => 'suspensa',    // em análise → mantém suspensa
                'aberto'     => 'suspensa',    // reaberto → suspensa por precaução
            ];

            if (isset($mapa[$novoSt])) {
                $pdo->prepare("UPDATE apolices SET status = :st WHERE id = :id")
                    ->execute([':st' => $mapa[$novoSt], ':id' => $row['apolice_id']]);
            }
        }

        echo json_encode(['status' => 'sucesso']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()]);
}
