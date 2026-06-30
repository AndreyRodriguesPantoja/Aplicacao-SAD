<?php
// ============================================================
// relatorio.php — Relatório
// ============================================================
include 'conexao.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    // Período selecionável (padrão: últimos 30 dias)
    $dias = max(1, min(365, (int)($_GET['dias'] ?? 30)));

    // Totais gerais (isolado ao dashboard legado — não mistura escalas com Análise de Risco/Sinistro)
    $resumo = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN Response <= 2 THEN 1 ELSE 0 END) AS aprovados,
            SUM(CASE WHEN Response > 2 AND Response <= 5 THEN 1 ELSE 0 END) AS moderados,
            SUM(CASE WHEN Response > 5 THEN 1 ELSE 0 END) AS negados,
            ROUND(AVG(Response), 2) AS media_risco,
            ROUND(MIN(Response), 2) AS min_risco,
            ROUND(MAX(Response), 2) AS max_risco,
            ROUND(AVG(BMI), 2) AS media_imc,
            ROUND(AVG(Ins_Age), 1) AS media_idade
        FROM clientes
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL :dias DAY)
          AND origem = 'dashboard'
    ");
    $resumo->execute([':dias' => $dias]);
    $r = $resumo->fetch();

    // Taxa de aprovação
    $total = (int)($r['total'] ?? 0);
    $r['taxa_aprovacao'] = $total > 0 ? round(($r['aprovados'] / $total) * 100, 1) : 0;
    $r['taxa_negacao']   = $total > 0 ? round(($r['negados']   / $total) * 100, 1) : 0;

    // Distribuição diária (últimos N dias)
    $diario = $pdo->prepare("
        SELECT
            DATE(created_at) AS data,
            COUNT(*) AS total,
            SUM(CASE WHEN Response <= 2 THEN 1 ELSE 0 END) AS baixo,
            SUM(CASE WHEN Response > 5  THEN 1 ELSE 0 END) AS alto
        FROM clientes
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL :dias DAY)
          AND origem = 'dashboard'
        GROUP BY DATE(created_at)
        ORDER BY data ASC
    ");
    $diario->execute([':dias' => $dias]);
    $distribuicaoDiaria = $diario->fetchAll();

    // Top faixas etárias de risco
    $faixas = $pdo->query("
        SELECT
            CASE
                WHEN Ins_Age < 26 THEN '18-25'
                WHEN Ins_Age BETWEEN 26 AND 35 THEN '26-35'
                WHEN Ins_Age BETWEEN 36 AND 45 THEN '36-45'
                WHEN Ins_Age BETWEEN 46 AND 60 THEN '46-60'
                ELSE '61+'
            END AS faixa,
            COUNT(*) AS total,
            ROUND(AVG(Response), 2) AS media_risco
        FROM clientes
        WHERE origem = 'dashboard'
        GROUP BY faixa
        ORDER BY media_risco DESC
    ")->fetchAll();

    // Recomendações automáticas baseadas nos dados
    $recomendacoes = [];
    if (($r['media_risco'] ?? 0) > 5) {
        $recomendacoes[] = 'Média de risco elevada. Revisar critérios de aprovação.';
    }
    if (($r['taxa_negacao'] ?? 0) > 30) {
        $recomendacoes[] = 'Taxa de negação acima de 30%. Considerar análise manual intermediária.';
    }
    if (($r['media_imc'] ?? 0) > 28) {
        $recomendacoes[] = 'IMC médio da carteira acima do ideal. Avaliar política de prêmios.';
    }
    if (empty($recomendacoes)) {
        $recomendacoes[] = 'Carteira dentro dos parâmetros normais de risco.';
    }

    echo json_encode([
        'periodo_dias'       => $dias,
        'gerado_em'          => date('d/m/Y H:i'),
        'resumo'             => $r,
        'distribuicao_diaria'=> $distribuicaoDiaria,
        'faixas_etarias'     => $faixas,
        'recomendacoes'      => $recomendacoes,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}