<?php
include 'conexao.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Type: application/json; charset=utf-8");

try {
    $idadeMin = isset($_GET['idade_min']) && $_GET['idade_min'] !== '' ? (int)$_GET['idade_min'] : null;
    $idadeMax = isset($_GET['idade_max']) && $_GET['idade_max'] !== '' ? (int)$_GET['idade_max'] : null;
    $classificacao = isset($_GET['classificacao']) ? trim($_GET['classificacao']) : '';

    $where = [];
    $params = [];

    if ($idadeMin !== null && $idadeMax !== null && $idadeMin > $idadeMax) {
    throw new Exception("Idade mínima não pode ser maior que a máxima.");
    }

    if ($idadeMax !== null) {
        $where[] = "Ins_Age <= :idade_max";
        $params[':idade_max'] = $idadeMax;
    }

    if ($classificacao !== '') {
        if ($classificacao === 'baixo') {
            $where[] = "Response <= 2";
        } elseif ($classificacao === 'moderado') {
            $where[] = "Response > 2 AND Response <= 5";
        } elseif ($classificacao === 'alto') {
            $where[] = "Response > 5";
        }
    }

    $whereSql = '';
    if (count($where) > 0) {
        $whereSql = ' WHERE ' . implode(' AND ', $where);
    }

    // Resumo / KPIs
    $sqlSummary = "
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN Response <= 2 THEN 1 ELSE 0 END), 0) AS baixo,
            COALESCE(SUM(CASE WHEN Response > 2 AND Response <= 5 THEN 1 ELSE 0 END), 0) AS moderado,
            COALESCE(SUM(CASE WHEN Response > 5 THEN 1 ELSE 0 END), 0) AS alto,
            COALESCE(ROUND(AVG(Response), 2), 0) AS media_risco
        FROM clientes
        $whereSql
    ";
    $stmtSummary = $pdo->prepare($sqlSummary);
    $stmtSummary->execute($params);
    $summary = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    // Distribuição por classificação
    $sqlClassification = "
        SELECT
            CASE
                WHEN Response <= 2 THEN 'Baixo'
                WHEN Response > 2 AND Response <= 5 THEN 'Moderado'
                ELSE 'Alto'
            END AS label,
            COUNT(*) AS total
        FROM clientes
        $whereSql
        GROUP BY label
        ORDER BY total DESC
    ";
    $stmtClassification = $pdo->prepare($sqlClassification);
    $stmtClassification->execute($params);
    $classification = $stmtClassification->fetchAll(PDO::FETCH_ASSOC);

    // Faixas etárias
    $sqlAgeGroups = "
        SELECT
            CASE
                WHEN Ins_Age < 26 THEN '18-25'
                WHEN Ins_Age BETWEEN 26 AND 35 THEN '26-35'
                WHEN Ins_Age BETWEEN 36 AND 45 THEN '36-45'
                WHEN Ins_Age BETWEEN 46 AND 60 THEN '46-60'
                ELSE '61+'
            END AS faixa,
            COUNT(*) AS total
        FROM clientes
        $whereSql
        GROUP BY faixa
        ORDER BY
            CASE faixa
                WHEN '18-25' THEN 1
                WHEN '26-35' THEN 2
                WHEN '36-45' THEN 3
                WHEN '46-60' THEN 4
                WHEN '61+' THEN 5
                ELSE 6
            END
    ";
    $stmtAgeGroups = $pdo->prepare($sqlAgeGroups);
    $stmtAgeGroups->execute($params);
    $ageGroups = $stmtAgeGroups->fetchAll(PDO::FETCH_ASSOC);

    // Últimos registros
    $sqlRecent = "
        SELECT
            Id AS id,
            Ins_Age AS idade,
            BMI AS imc,
            Response AS risco
        FROM clientes
        $whereSql
        ORDER BY Id DESC
        LIMIT 10
    ";
    $stmtRecent = $pdo->prepare($sqlRecent);
    $stmtRecent->execute($params);
    $recent = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "summary" => $summary ?: [
            "total" => 0,
            "baixo" => 0,
            "moderado" => 0,
            "alto" => 0,
            "media_risco" => 0
        ],
        "classification" => $classification ?: [],
        "age_groups" => $ageGroups ?: [],
        "recent" => $recent ?: []
    ]);
} http_response_code(500);
    echo json_encode([
    "erro" => "Erro no servidor: " . $e->getMessage()
]);
?>





