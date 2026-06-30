<?php
// ============================================================
// get_dados.php — API de dados para o dashboard (corrigido)
// ============================================================
include 'conexao.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: application/json; charset=utf-8');

try {
    $idadeMin      = isset($_GET['idade_min']) && $_GET['idade_min'] !== '' ? (int)$_GET['idade_min'] : null;
    $idadeMax      = isset($_GET['idade_max']) && $_GET['idade_max'] !== '' ? (int)$_GET['idade_max'] : null;
    $classificacao = trim($_GET['classificacao'] ?? '');

    // Validação cruzada
    if ($idadeMin !== null && $idadeMax !== null && $idadeMin > $idadeMax) {
        throw new Exception('Idade mínima não pode ser maior que a máxima.');
    }

    $where  = ["origem = 'dashboard'"]; // isola do motor de Análise de Risco/Sinistro (escalas diferentes)
    $params = [];

    if ($idadeMin !== null) {
        $where[]            = 'Ins_Age >= :idade_min';
        $params[':idade_min'] = $idadeMin;
    }
    if ($idadeMax !== null) {
        $where[]            = 'Ins_Age <= :idade_max';
        $params[':idade_max'] = $idadeMax;
    }
    if ($classificacao !== '') {
        match ($classificacao) {
            'baixo'    => $where[] = 'Response <= 2',
            'moderado' => $where[] = 'Response > 2 AND Response <= 5',
            'alto'     => $where[] = 'Response > 5',
            default    => null
        };
    }

    $whereSql = ' WHERE ' . implode(' AND ', $where);

    // ── KPIs ──
    $sqlSummary = "
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN Response <= 2           THEN 1 ELSE 0 END), 0) AS baixo,
            COALESCE(SUM(CASE WHEN Response > 2 AND Response <= 5 THEN 1 ELSE 0 END), 0) AS moderado,
            COALESCE(SUM(CASE WHEN Response > 5            THEN 1 ELSE 0 END), 0) AS alto,
            COALESCE(ROUND(AVG(Response), 2), 0) AS media_risco
        FROM clientes $whereSql
    ";
    $stmt = $pdo->prepare($sqlSummary);
    $stmt->execute($params);
    $summary = $stmt->fetch();

    // ── Distribuição por classificação ──
    $sqlClass = "
        SELECT
            CASE
                WHEN Response <= 2           THEN 'Baixo'
                WHEN Response > 2 AND Response <= 5 THEN 'Moderado'
                ELSE 'Alto'
            END AS label,
            COUNT(*) AS total
        FROM clientes $whereSql
        GROUP BY label
        ORDER BY total DESC
    ";
    $stmt = $pdo->prepare($sqlClass);
    $stmt->execute($params);
    $classification = $stmt->fetchAll();

    // ── Faixas etárias ──
    $sqlAge = "
        SELECT
            CASE
                WHEN Ins_Age < 26            THEN '18-25'
                WHEN Ins_Age BETWEEN 26 AND 35 THEN '26-35'
                WHEN Ins_Age BETWEEN 36 AND 45 THEN '36-45'
                WHEN Ins_Age BETWEEN 46 AND 60 THEN '46-60'
                ELSE '61+'
            END AS faixa,
            COUNT(*) AS total
        FROM clientes $whereSql
        GROUP BY faixa
        ORDER BY FIELD(faixa,'18-25','26-35','36-45','46-60','61+')
    ";
    $stmt = $pdo->prepare($sqlAge);
    $stmt->execute($params);
    $ageGroups = $stmt->fetchAll();

    // ── Últimos 10 registros ──
    $sqlRecent = "
        SELECT Id AS id, Ins_Age AS idade, BMI AS imc, Response AS risco
        FROM clientes $whereSql
        ORDER BY Id DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sqlRecent);
    $stmt->execute($params);
    $recent = $stmt->fetchAll();

    // ── Tendência mensal (nova funcionalidade) ──
    $sqlTrend = "
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') AS mes,
            COUNT(*) AS total,
            ROUND(AVG(Response), 2) AS media_risco
        FROM clientes
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          AND origem = 'dashboard'
        GROUP BY mes
        ORDER BY mes ASC
    ";
    $trend = [];
    try {
        $stmtT = $pdo->query($sqlTrend);
        $trend = $stmtT->fetchAll();
    } catch (Exception $e) {
        // coluna created_at pode não existir em BDs legados
        $trend = [];
    }

    echo json_encode([
        'summary'        => $summary        ?: ['total'=>0,'baixo'=>0,'moderado'=>0,'alto'=>0,'media_risco'=>0],
        'classification' => $classification ?: [],
        'age_groups'     => $ageGroups      ?: [],
        'recent'         => $recent         ?: [],
        'trend'          => $trend,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}