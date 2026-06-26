<?php
// ============================================================
// 
// Motor de análise de risco do sistema [Super Seguro]
// Critérios baseados de análise baseados nas seguintes normas:
// SUSEP (Brasil), ISO, Actuarial Standards of Practice
//
// GET  ?cliente_id=N&produto=vida   → retorna análise completa
// GET  ?listar_clientes=1           → lista clientes para seleção
// POST JSON { cliente_id, produto, dados_extras } → salva análise
// ============================================================
session_start();
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ── Protege rota ─────────────────────────────────────────────
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Sessão expirada.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// TABELAS DE REFERÊNCIA ATUARIAL
// ============================================================

// Fatores por faixa etária (mortalidade/morbidade relativa)
// Fonte: Tábua BR-EMS 2021
function fatorIdade(int $idade, string $produto): float {
    if ($produto === 'vida') {
        if ($idade < 18)  return 0.0;   // não elegível
        if ($idade <= 25) return 0.7;
        if ($idade <= 35) return 0.9;
        if ($idade <= 45) return 1.1;
        if ($idade <= 55) return 1.5;
        if ($idade <= 65) return 2.2;
        return 3.5;                     // 65+
    }
    if ($produto === 'saude') {
        if ($idade < 18)  return 0.8;
        if ($idade <= 29) return 1.0;
        if ($idade <= 39) return 1.3;
        if ($idade <= 49) return 1.8;
        if ($idade <= 59) return 2.5;
        return 3.8;
    }
    // Auto e residencial — menor impacto da idade
    if ($idade < 25)  return 1.4;
    if ($idade <= 65) return 1.0;
    return 1.2;
}

// Fator IMC — relevante para seguro de vida e saúde
function fatorIMC(float $imc, string $produto): array {
    if (!in_array($produto, ['vida', 'saude'])) return ['fator'=>1.0, 'obs'=>''];
    if ($imc < 18.5) return ['fator'=>1.3, 'obs'=>'Abaixo do peso'];
    if ($imc < 25)   return ['fator'=>1.0, 'obs'=>'Peso normal'];
    if ($imc < 30)   return ['fator'=>1.2, 'obs'=>'Sobrepeso'];
    if ($imc < 35)   return ['fator'=>1.5, 'obs'=>'Obesidade grau I'];
    if ($imc < 40)   return ['fator'=>1.9, 'obs'=>'Obesidade grau II'];
    return                  ['fator'=>2.5, 'obs'=>'Obesidade grau III'];
}

// Comprometimento de renda (prêmio / salário)
function fatorComprometimento(float $salario, float $valorApli): array {
    if ($salario <= 0) return ['fator'=>2.0, 'obs'=>'Renda não informada', 'ratio'=>0];
    $ratio = $valorApli / $salario;
    if ($ratio <= 0.5)  return ['fator'=>0.8, 'obs'=>'Baixo comprometimento de renda',   'ratio'=>$ratio];
    if ($ratio <= 1.0)  return ['fator'=>1.0, 'obs'=>'Comprometimento moderado',          'ratio'=>$ratio];
    if ($ratio <= 2.0)  return ['fator'=>1.3, 'obs'=>'Alto comprometimento de renda',     'ratio'=>$ratio];
    if ($ratio <= 3.0)  return ['fator'=>1.7, 'obs'=>'Muito alto comprometimento',        'ratio'=>$ratio];
    return                     ['fator'=>2.2, 'obs'=>'Comprometimento crítico de renda',  'ratio'=>$ratio];
}

// Fator geográfico por estado
function fatorGeo(string $estado): array {
    $tabela = [
        'SP' => ['fator'=>1.3, 'obs'=>'Alta densidade — maior sinistralidade'],
        'RJ' => ['fator'=>1.4, 'obs'=>'Alta sinistralidade urbana'],
        'MG' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'RS' => ['fator'=>1.2, 'obs'=>'Risco climático relevante'],
        'SC' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'PR' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'BA' => ['fator'=>1.2, 'obs'=>'Risco moderado-alto'],
        'PE' => ['fator'=>1.2, 'obs'=>'Risco moderado-alto'],
        'AM' => ['fator'=>1.3, 'obs'=>'Risco logístico e climático'],
        'PA' => ['fator'=>1.2, 'obs'=>'Risco logístico elevado'],
        'GO' => ['fator'=>1.0, 'obs'=>'Risco médio'],
        'DF' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'MT' => ['fator'=>1.1, 'obs'=>'Risco agropecuário'],
        'MS' => ['fator'=>1.0, 'obs'=>'Risco médio'],
        'ES' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'CE' => ['fator'=>1.2, 'obs'=>'Risco climático — seca'],
        'MA' => ['fator'=>1.2, 'obs'=>'Risco moderado-alto'],
        'PI' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'RN' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'PB' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'AL' => ['fator'=>1.2, 'obs'=>'Risco moderado-alto'],
        'SE' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'TO' => ['fator'=>1.1, 'obs'=>'Risco moderado'],
        'RO' => ['fator'=>1.2, 'obs'=>'Risco logístico'],
        'AC' => ['fator'=>1.3, 'obs'=>'Risco logístico elevado'],
        'RR' => ['fator'=>1.2, 'obs'=>'Risco fronteiriço'],
        'AP' => ['fator'=>1.2, 'obs'=>'Risco logístico'],
    ];
    $uf = strtoupper(trim($estado));
    return $tabela[$uf] ?? ['fator'=>1.0, 'obs'=>'Estado não mapeado — fator neutro'];
}

// Fator gênero por produto (atuarial, conforme SUSEP)
function fatorGenero(string $genero, string $produto): float {
    $g = strtolower($genero);
    if ($produto === 'vida') {
        return $g === 'masculino' ? 1.15 : 1.0; // homens maior mortalidade
    }
    if ($produto === 'automovel') {
        return $g === 'masculino' ? 1.25 : 1.0; // homens mais acidentes
    }
    return 1.0;
}

// LÓGICA PRINCIPAL
function calcularRisco(array $cliente, string $produto, array $extras = []): array {

    $alertas    = [];
    $fatores    = [];
    $exclusoes  = [];
    $pontuacao  = 100; // começa em 100 (risco máximo aprovável)

    $idade  = (int)   ($cliente['idade']   ?? 0);
    $imc    = (float) ($extras['imc']      ?? $cliente['imc'] ?? 0);
    $salario= (float) ($cliente['salario'] ?? 0);
    $valor  = (float) ($cliente['valorapli']?? 0);
    $genero = strtolower($cliente['genero'] ?? '');
    $estado = $cliente['estado'] ?? '';

    // ── 1. ELEGIBILIDADE MÍNIMA ──────────────────────────────
    if ($idade < 18) {
        return ['elegivel' => false, 'motivo' => 'Idade mínima não atingida (18 anos).'];
    }
    $limiteIdade = ['vida'=>70,'saude'=>65,'automovel'=>80,'residencial'=>80];
    if (isset($limiteIdade[$produto]) && $idade > $limiteIdade[$produto]) {
        return ['elegivel' => false, 'motivo' => "Produto $produto não disponível acima de {$limiteIdade[$produto]} anos."];
    }

    // ── 2. FATOR IDADE ──────────────────────────────────────
    $fId = fatorIdade($idade, $produto);
    $fatores['idade'] = [
        'valor'  => $fId,
        'peso'   => 30,
        'label'  => "Faixa etária ($idade anos)",
        'impacto'=> round(($fId - 1) * 30, 1),
    ];
    $pontuacao -= ($fId - 1) * 30;

    if ($fId >= 2.2) $alertas[] = 'Idade avançada — agravamento significativo de prêmio.';
    if ($fId >= 3.5) $exclusoes[]= 'Produto Vida: análise manual obrigatória para idade > 65 anos.';

    // ── 3. FATOR IMC (Vida e Saúde) ─────────────────────────
    if ($imc > 0 && in_array($produto, ['vida','saude'])) {
        $fIMC = fatorIMC($imc, $produto);
        $fatores['imc'] = [
            'valor'  => $fIMC['fator'],
            'peso'   => 20,
            'label'  => "IMC ({$imc} — {$fIMC['obs']})",
            'impacto'=> round(($fIMC['fator'] - 1) * 20, 1),
        ];
        $pontuacao -= ($fIMC['fator'] - 1) * 20;

        if ($fIMC['fator'] >= 1.9) $alertas[]  = "IMC elevado ({$fIMC['obs']}) — considerar exame médico.";
        if ($fIMC['fator'] >= 2.5) $exclusoes[] = 'Obesidade grau III — exige laudo médico obrigatório.';
    }

    // ── 4. FATOR FINANCEIRO (comprometimento de renda) ──────
    $fFin = fatorComprometimento($salario, $valor);
    $fatores['financeiro'] = [
        'valor'  => $fFin['fator'],
        'peso'   => 20,
        'label'  => sprintf("Comprometimento de renda (%.0f%% do salário) — %s", $fFin['ratio']*100, $fFin['obs']),
        'impacto'=> round(($fFin['fator'] - 1) * 20, 1),
    ];
    $pontuacao -= ($fFin['fator'] - 1) * 20;

    if ($fFin['fator'] >= 1.7) $alertas[]  = 'Valor segurado muito alto em relação à renda — risco moral elevado.';
    if ($fFin['fator'] >= 2.2) $exclusoes[] = 'Comprometimento crítico: cobertura limitada a 2× o salário anual.';

    // ── 5. FATOR GEOGRÁFICO ──────────────────────────────────
    $fGeo = fatorGeo($estado);
    $fatores['geografico'] = [
        'valor'  => $fGeo['fator'],
        'peso'   => 15,
        'label'  => strtoupper($estado) . " — " . $fGeo['obs'],
        'impacto'=> round(($fGeo['fator'] - 1) * 15, 1),
    ];
    $pontuacao -= ($fGeo['fator'] - 1) * 15;

    if ($fGeo['fator'] >= 1.3) $alertas[] = 'Região de alta sinistralidade — verificar histórico local.';

    // ── 6. FATOR GÊNERO (atuarial) ───────────────────────────
    $fGen = fatorGenero($genero, $produto);
    if ($fGen !== 1.0) {
        $fatores['genero'] = [
            'valor'  => $fGen,
            'peso'   => 10,
            'label'  => ucfirst($genero) . " — fator atuarial para $produto",
            'impacto'=> round(($fGen - 1) * 10, 1),
        ];
        $pontuacao -= ($fGen - 1) * 10;
    }

    // ── 7. CONDIÇÕES PRÉ-EXISTENTES (extras) ─────────────────
    $condicoes = $extras['condicoes'] ?? [];
    $tabCondicoes = [
        'diabetes'         => ['fator'=>1.4, 'label'=>'Diabetes mellitus'],
        'hipertensao'      => ['fator'=>1.3, 'label'=>'Hipertensão arterial'],
        'doenca_cardiaca'  => ['fator'=>2.0, 'label'=>'Doença cardíaca'],
        'cancer'           => ['fator'=>3.0, 'label'=>'Histórico de câncer'],
        'fumante'          => ['fator'=>1.6, 'label'=>'Tabagismo'],
        'alcoolismo'       => ['fator'=>1.5, 'label'=>'Uso de álcool'],
        'doenca_renal'     => ['fator'=>1.8, 'label'=>'Doença renal crônica'],
        'depressao'        => ['fator'=>1.3, 'label'=>'Depressão/ansiedade'],
    ];

    $fatorCondicoes = 1.0;
    $labelsCondicoes = [];
    foreach ($condicoes as $c) {
        if (isset($tabCondicoes[$c])) {
            $fatorCondicoes *= $tabCondicoes[$c]['fator'];
            $labelsCondicoes[] = $tabCondicoes[$c]['label'];
        }
    }
    if ($fatorCondicoes > 1.0 && in_array($produto, ['vida','saude'])) {
        $fatores['condicoes'] = [
            'valor'  => round($fatorCondicoes, 2),
            'peso'   => 25,
            'label'  => 'Condições pré-existentes: ' . implode(', ', $labelsCondicoes),
            'impacto'=> round(($fatorCondicoes - 1) * 25, 1),
        ];
        $pontuacao -= ($fatorCondicoes - 1) * 25;

        if ($fatorCondicoes >= 2.0) $alertas[]  = 'Múltiplas condições pré-existentes — carência especial aplicável.';
        if ($fatorCondicoes >= 3.0) $exclusoes[] = 'Condições críticas — cobertura parcial ou recusa técnica.';
    }

    // ── 8. PROFISSÃO|ATIVIDADE DE RISCO ────────────────────
    $profissao = strtolower($extras['profissao'] ?? '');
    $profAlto = ['militar','policial','bombeiro','minerador','piloto','segurança privada','mergulhador','motociclista profissional'];
    $profMedio= ['médico','enfermeiro','construção civil','eletricista','caminhoneiro','administrador'];
    if ($profissao && in_array($produto, ['vida','saude'])) {
        $fProf = 1.0; $lProf = '';
        foreach ($profAlto  as $p) if (str_contains($profissao, $p)) { $fProf = 1.5; $lProf = "Profissão de alto risco: $profissao"; break; }
        foreach ($profMedio as $p) if (str_contains($profissao, $p)) { $fProf = max($fProf, 1.2); $lProf = $lProf ?: "Profissão de risco médio: $profissao"; }
        if ($fProf > 1.0) {
            $fatores['profissao'] = ['valor'=>$fProf,'peso'=>10,'label'=>$lProf,'impacto'=>round(($fProf-1)*10,1)];
            $pontuacao -= ($fProf - 1) * 10;
            if ($fProf >= 1.5) $alertas[] = 'Profissão de alto risco — agravamento de prêmio aplicado.';
        }
    }

    // ── 9. NORMALIZAÇÃO DO SCORE ─────────────────────────────
    $score = (int) max(0, min(100, round($pontuacao)));

    // ── 10. DECISÃO DO RISCO ────────────────────────────
    if (!empty($exclusoes) || $score < 25) {
        $decisao     = 'RECUSADO';
        $decisaoDesc = 'Risco técnico fora dos limites de aceitação. Não é possível emitir apólice.';
        $decisaoCor  = 'red';
        $scoreLabel  = 'Muito Alto';
    } elseif ($score < 50) {
        $decisao     = 'ANÁLISE MANUAL';
        $decisaoDesc = 'Perfil requer avaliação do subscritor sênior e pode exigir documentação adicional.';
        $decisaoCor  = 'amber';
        $scoreLabel  = 'Alto';
    } elseif ($score < 70) {
        $decisao     = 'APROVADO COM AGRAVAMENTO';
        $decisaoDesc = 'Apólice emitida com prêmio adicional proporcional ao perfil de risco.';
        $decisaoCor  = 'amber';
        $scoreLabel  = 'Moderado';
    } else {
        $decisao     = 'APROVADO';
        $decisaoDesc = 'Perfil dentro dos parâmetros de aceitação standard.';
        $decisaoCor  = 'green';
        $scoreLabel  = 'Baixo';
    }

    // ── 11. CÁLCULO DO PRÊMIO ESTIMADO ───────────────────────
    $taxasBase = ['vida'=>0.003,'saude'=>0.005,'automovel'=>0.008,'residencial'=>0.004];
    $taxaBase  = $taxasBase[$produto] ?? 0.004;
    $fatorTotal= array_reduce($fatores, fn($c, $f) => $c * $f['valor'], 1.0);
    $premioMensal = ($valor * $taxaBase * max($fatorTotal, 1.0));

    // Limitador de prêmio (prática SUSEP: máx 20% renda mensal)
    $premioMaximo  = $salario * 0.20;
    $premioLimitado = ($premioMaximo > 0 && $premioMensal > $premioMaximo);

    return [
        'elegivel'        => true,
        'score'           => $score,
        'score_label'     => $scoreLabel,
        'decisao'         => $decisao,
        'decisao_desc'    => $decisaoDesc,
        'decisao_cor'     => $decisaoCor,
        'fatores'         => $fatores,
        'alertas'         => $alertas,
        'exclusoes'       => $exclusoes,
        'premio_mensal'   => round($premioMensal, 2),
        'premio_limitado' => $premioLimitado,
        'premio_maximo'   => round($premioMaximo, 2),
        'produto'         => $produto,
        'analisado_em'    => date('d/m/Y H:i'),
        'analista_id'     => $_SESSION['usuario_id'] ?? null,
    ];
}

// ============================================================
// ROTEAMENTO
// ============================================================
try {

    // ── Listar clientes ──────────────────────────────────────
    if (isset($_GET['listar_clientes'])) {
        $stmt = $pdo->query("
            SELECT u.id, u.nome, u.cpf, u.cidade, u.estado,
                   u.datanascimento, u.genero, u.salario, u.valorapli,
                   ROUND(COALESCE(AVG(c.Response),0),1) AS ultimo_score,
                   COUNT(a.id) AS total_apolices
            FROM   usuarios u
            LEFT JOIN clientes c ON c.usuario_id = u.id
            LEFT JOIN apolices  a ON a.usuario_id = u.id AND a.status = 'ativa'
            WHERE  u.ativo = 1
            GROUP  BY u.id
            ORDER  BY u.nome ASC
        ");
        echo json_encode(['clientes' => $stmt->fetchAll()]);
        exit;
    }

    // ── Analisar cliente ─────────────────────────────────────
    if ($method === 'GET' && isset($_GET['cliente_id'])) {
        $id      = (int) $_GET['cliente_id'];
        $produto = strtolower(trim($_GET['produto'] ?? 'vida'));
        $extras  = [
            'imc'        => (float) ($_GET['imc']        ?? 0),
            'condicoes'  => array_filter(explode(',', $_GET['condicoes'] ?? '')),
            'profissao'  => trim($_GET['profissao'] ?? ''),
        ];

        $stmt = $pdo->prepare("
            SELECT u.*,
                   TIMESTAMPDIFF(YEAR, u.datanascimento, CURDATE()) AS idade
            FROM   usuarios u WHERE u.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch();

        if (!$cliente) throw new Exception('Cliente não encontrado.');

        $resultado = calcularRisco($cliente, $produto, $extras);
        $resultado['cliente'] = [
            'id'    => $cliente['id'],
            'nome'  => $cliente['nome'],
            'cpf'   => $cliente['cpf'],
            'idade' => $cliente['idade'],
        ];

        echo json_encode($resultado);
        exit;
    }

    // ── Salvar análise ───────────────────────────────────────
    if ($method === 'POST') {
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $clienteId = (int)   ($body['cliente_id'] ?? 0);
        $produto   = strtolower(trim($body['produto'] ?? ''));
        $score     = (int)   ($body['score']      ?? 0);
        $decisao   = trim($body['decisao']         ?? '');
        $premioMensal = (float) ($body['premio_mensal'] ?? 0);

        if (!$clienteId || !$produto) throw new Exception('Dados incompletos.');

        // Salva na tabela clientes para integrar com o dashboard
        $stmt = $pdo->prepare("
            INSERT INTO clientes (usuario_id, Ins_Age, BMI, Response)
            SELECT :uid,
                   TIMESTAMPDIFF(YEAR, datanascimento, CURDATE()),
                   :imc,
                   :score
            FROM usuarios WHERE id = :uid2
        ");
        $stmt->execute([
            ':uid'   => $clienteId,
            ':imc'   => (float)($body['imc'] ?? 0),
            ':score' => min(10, round($score / 10)),  // normaliza 0-100 → 0-10
            ':uid2'  => $clienteId,
        ]);

        echo json_encode(['status' => 'sucesso', 'mensagem' => 'Análise salva no histórico.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()]);
}
