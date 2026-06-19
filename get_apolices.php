<?php
// ============================================================
// get_apolices.php — API unificada de Apólices (versão final)
//
// O JS do analista envia:
//   POST  application/json  { usuario_id, tipo_seguro, valor_cobertura,
//                             valor_premio, data_inicio, data_fim,
//                             status, observacoes }
//   GET   ?buscar_cliente=1&cpf=X   → busca cliente por CPF
//   GET   (sem usuario_id)          → listagem do analista
//   GET   ?usuario_id=N             → apólices de um cliente (portal)
//   PATCH JSON { id, status }       → altera status
// ============================================================
session_start();
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$method = $_SERVER['REQUEST_METHOD'];

// ── Exige sessão de funcionário ──────────────────────────────
function exigirFuncionario(): int {
    if (empty($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['erro' => 'Sessão expirada. Faça login novamente.']);
        exit;
    }
    return (int) $_SESSION['usuario_id'];
}

// ── Lê body JSON (compatível com application/json) ──────────
function lerBodyJson(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

try {

    /* ════════════════════════════════════════════════════════
       POST — criar nova apólice
       O JS envia: { usuario_id, tipo_seguro, valor_cobertura,
                     valor_premio, data_inicio, data_fim,
                     status, observacoes }
    ════════════════════════════════════════════════════════ */
    if ($method === 'POST') {
        $analistaId = exigirFuncionario();

        // Lê JSON (o JS envia Content-Type: application/json)
        $body = lerBodyJson();

        // Campos recebidos do JS
        $usuarioId     = (int)   ($body['usuario_id']      ?? 0);
        $tipoSeguro    = trim(    $body['tipo_seguro']     ?? '');
        $valorCobert   = (float) ($body['valor_cobertura'] ?? 0);
        $valorPremio   = (float) ($body['valor_premio']    ?? 0);
        $dataInicio    = trim(    $body['data_inicio']     ?? '');
        $dataFim       = trim(    $body['data_fim']        ?? '');
        $statusAp      = trim(    $body['status']          ?? 'ativa');
        $obs           = trim(    $body['observacoes']     ?? '');

        // Validações
        if (!$usuarioId)           throw new Exception('Cliente não selecionado. Busque pelo CPF primeiro.');
        if (!$tipoSeguro)          throw new Exception('Tipo de seguro obrigatório.');
        if ($valorCobert <= 0)     throw new Exception('Valor de cobertura inválido.');
        if (!$dataInicio || !$dataFim) throw new Exception('Datas de vigência obrigatórias.');
        if ($dataInicio >= $dataFim)   throw new Exception('Data de início deve ser anterior ao fim.');

        $tiposOk  = ['Vida', 'Saude', 'Auto', 'Residencial'];
        $statusOk = ['ativa', 'suspensa', 'Ativa', 'Suspensa'];   // aceita maiúscula/minúscula
        if (!in_array($tipoSeguro, $tiposOk))  throw new Exception("Tipo de seguro inválido: $tipoSeguro.");
        $statusAp = strtolower($statusAp);   // normaliza para minúscula
        if (!in_array($statusAp, ['ativa','suspensa'])) $statusAp = 'ativa';

        // Confirma que o usuario_id existe na tabela usuarios
        $chk = $pdo->prepare('SELECT id FROM usuarios WHERE id = :id LIMIT 1');
        $chk->execute([':id' => $usuarioId]);
        if (!$chk->fetch()) throw new Exception('Cliente não encontrado no sistema.');

        // Gera número de apólice único
        $sufixo = strtoupper(substr($tipoSeguro, 0, 3));
        $numero = "AP-{$sufixo}-" . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        // INSERT usando as colunas reais do banco (conforme sad_superseguro.sql)
        $stmt = $pdo->prepare("
            INSERT INTO apolices
                (numero_apolice, usuario_id, analista_id, tipo_seguro,
                 valor_cobertura, valor_premio, data_inicio, data_fim,
                 status, observacoes)
            VALUES
                (:numero, :uid, :ana, :tipo,
                 :cobert, :premio, :ini, :fim,
                 :status, :obs)
        ");
        $stmt->execute([
            ':numero'  => $numero,
            ':uid'     => $usuarioId,
            ':ana'     => $analistaId,
            ':tipo'    => $tipoSeguro,
            ':cobert'  => $valorCobert,
            ':premio'  => $valorPremio,
            ':ini'     => $dataInicio,
            ':fim'     => $dataFim,
            ':status'  => $statusAp,
            ':obs'     => $obs,
        ]);

        echo json_encode([
            'status'         => 'sucesso',     // verificado pelo JS: data.status === "sucesso"
            'sucesso'        => true,
            'numero_apolice' => $numero,
            'id'             => (int) $pdo->lastInsertId(),
            'mensagem'       => "Apólice $numero criada com sucesso!",
        ]);
        exit;
    }

    /* ════════════════════════════════════════════════════════
       PATCH — alterar status de apólice
    ════════════════════════════════════════════════════════ */
    if ($method === 'PATCH') {
        exigirFuncionario();

        $body   = lerBodyJson();
        $id     = (int)  ($body['id']     ?? 0);
        $novoSt = strtolower(trim($body['status'] ?? ''));

        if (!$id || !in_array($novoSt, ['ativa','suspensa','cancelada','expirada'])) {
            throw new Exception('Dados inválidos para atualização de status.');
        }

        $stmt = $pdo->prepare('UPDATE apolices SET status = :st WHERE id = :id');
        $stmt->execute([':st' => $novoSt, ':id' => $id]);

        if ($stmt->rowCount() === 0) throw new Exception('Apólice não encontrada.');

        echo json_encode(['status' => 'sucesso']);
        exit;
    }

    /* ════════════════════════════════════════════════════════
       GET ?buscar_cliente=1&cpf=X
       Busca cliente por CPF — chamado ao clicar "Buscar"
       Retorna: { cliente: { id, nome, cpf, email, media_risco } }
    ════════════════════════════════════════════════════════ */
    if ($method === 'GET' && isset($_GET['buscar_cliente'])) {
        exigirFuncionario();

        $cpf = preg_replace('/\D/', '', $_GET['cpf'] ?? '');
        if (strlen($cpf) !== 11) throw new Exception('CPF inválido. Informe os 11 dígitos.');

        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.nome,
                u.cpf,
                u.email,
                ROUND(COALESCE(AVG(c.Response), 0), 2) AS media_risco
            FROM usuarios u
            LEFT JOIN clientes c ON c.usuario_id = u.id
            WHERE u.cpf = :cpf
            GROUP BY u.id
            LIMIT 1
        ");
        $stmt->execute([':cpf' => $cpf]);
        $cliente = $stmt->fetch();

        if (!$cliente) {
            echo json_encode(['cliente' => null, 'erro' => 'Cliente não encontrado.']);
            exit;
        }

        echo json_encode(['cliente' => $cliente]);
        exit;
    }

    /* ════════════════════════════════════════════════════════
       GET ?usuario_id=N — apólices de um cliente (portal)
    ════════════════════════════════════════════════════════ */
    if ($method === 'GET' && isset($_GET['usuario_id'])) {
        $usuarioId = (int) $_GET['usuario_id'];
        $status    = trim($_GET['status'] ?? '');
        $tipo      = trim($_GET['tipo']   ?? '');

        $where  = ['a.usuario_id = :uid'];
        $params = [':uid' => $usuarioId];

        if ($status !== '') { $where[] = 'a.status = :status'; $params[':status'] = $status; }
        if ($tipo   !== '') { $where[] = 'a.tipo_seguro = :tipo'; $params[':tipo'] = $tipo; }

        $stmt = $pdo->prepare("
            SELECT a.id, a.numero_apolice, a.tipo_seguro,
                   a.valor_cobertura, a.valor_premio,
                   a.data_inicio, a.data_fim, a.status, a.observacoes
            FROM apolices a
            WHERE " . implode(' AND ', $where) . "
            ORDER BY FIELD(a.status,'ativa','suspensa','expirada','cancelada'), a.data_fim ASC
        ");
        $stmt->execute($params);

        echo json_encode(['apolices' => $stmt->fetchAll()]);
        exit;
    }

    /* ════════════════════════════════════════════════════════
       GET (padrão) — listagem completa para o analista
       Chamado sem parâmetros especiais
    ════════════════════════════════════════════════════════ */
    if ($method === 'GET') {
        exigirFuncionario();

        $busca  = trim($_GET['busca']  ?? '');
        $status = trim($_GET['status'] ?? '');
        $tipo   = trim($_GET['tipo']   ?? '');

        $where  = [];
        $params = [];

        if ($busca !== '') {
            $where[] = '(u.nome LIKE :busca OR u.cpf LIKE :busca2)';
            $params[':busca'] = $params[':busca2'] = "%$busca%";
        }
        if ($status !== '') { $where[] = 'a.status = :status'; $params[':status'] = $status; }
        if ($tipo   !== '') { $where[] = 'a.tipo_seguro = :tipo'; $params[':tipo'] = $tipo; }

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("
            SELECT
                a.id,
                a.numero_apolice,
                u.nome  AS cliente,
                u.cpf,
                a.tipo_seguro,
                a.valor_cobertura,
                a.valor_premio,
                a.data_inicio,
                a.data_fim,
                a.status,
                a.observacoes,
                ROUND(COALESCE((
                    SELECT AVG(c.Response) FROM clientes c WHERE c.usuario_id = u.id
                ), 0), 2) AS media_risco
            FROM apolices a
            JOIN usuarios u ON u.id = a.usuario_id
            $whereSql
            ORDER BY FIELD(a.status,'ativa','suspensa','expirada','cancelada'), a.id DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $apolices = $stmt->fetchAll();

        // KPIs por status
        $totais = $pdo->query(
            "SELECT status, COUNT(*) AS total FROM apolices GROUP BY status"
        )->fetchAll();

        echo json_encode(['apolices' => $apolices, 'totais' => $totais]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()]);
}