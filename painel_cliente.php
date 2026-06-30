<?php
// ============================================================
// painel_cliente.php — Dashboard do cliente pós-login
// ============================================================
session_start();
require 'conexao.php';

if (empty($_SESSION['cliente_id']) || $_SESSION['perfil'] !== 'cliente') {
    header('Location: login-screen.html?perfil=cliente&erro=sessao');
    exit;
}

$clienteId = (int) $_SESSION['cliente_id'];
$nome      = htmlspecialchars($_SESSION['cliente_nome'] ?? 'Cliente');
$primeiroNome = explode(' ', $nome)[0];

// Busca dados resumidos do cliente
try {
    $stmt = $pdo->prepare("
        SELECT
            u.nome, u.cpf, u.email, u.telefone,
            u.cidade, u.estado, u.datanascimento,
            (SELECT COUNT(*) FROM apolices a
             WHERE a.usuario_id = u.id AND a.status = 'ativa')      AS qtd_ativas,
            (SELECT COALESCE(SUM(a.valor_cobertura),0) FROM apolices a
             WHERE a.usuario_id = u.id AND a.status = 'ativa')      AS total_cobertura,
            (SELECT COALESCE(SUM(a.valor_premio),0)   FROM apolices a
             WHERE a.usuario_id = u.id AND a.status = 'ativa')      AS total_premios,
            (SELECT MIN(a.data_fim) FROM apolices a
             WHERE a.usuario_id = u.id AND a.status = 'ativa'
               AND a.data_fim >= CURDATE())                         AS proximo_venc,
            -- Score real do motor de Análise de Risco/Sinistro, escala 0-100
            ROUND((
                SELECT AVG(c.Response)*10 FROM clientes c
                WHERE c.usuario_id = u.id AND c.origem IN ('analise_risco','sinistro')
            ), 1) AS media_risco
        FROM usuarios u
        WHERE u.id = :id
    ");
    $stmt->execute([':id' => $clienteId]);
    $dados = $stmt->fetch();

    // Últimas 3 apólices
    $ultimas = $pdo->prepare("
        SELECT numero_apolice, tipo_seguro, valor_cobertura,
               valor_premio, data_inicio, data_fim, status
        FROM   apolices
        WHERE  usuario_id = :id
        ORDER  BY id DESC
        LIMIT  3
    ");
    $ultimas->execute([':id' => $clienteId]);
    $ultimas = $ultimas->fetchAll();

} catch (PDOException $e) {
    $dados  = [];
    $ultimas = [];
}

// Helpers
$fmtMoeda = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$fmtData  = fn($d) => $d ? date('d/m/Y', strtotime($d)) : '—';

$riscoLabel = '—';
$riscoCor   = '#64748b';
if ($dados['media_risco'] !== null) {
    $r = (float)$dados['media_risco'];
    // Escala 0-100 (score real do motor de Análise de Risco)
    if ($r < 30)      { $riscoLabel = 'Baixo';    $riscoCor = '#057a55'; }
    elseif ($r < 60)  { $riscoLabel = 'Moderado'; $riscoCor = '#d97706'; }
    else              { $riscoLabel = 'Alto';      $riscoCor = '#991b1b'; }
}

$statusBadge = [
    'ativa'    => ['bg'=>'#d1fae5','cor'=>'#065f46','txt'=>'Ativa'],
    'suspensa' => ['bg'=>'#fef3c7','cor'=>'#92400e','txt'=>'Suspensa'],
    'expirada' => ['bg'=>'#f1f5f9','cor'=>'#64748b','txt'=>'Expirada'],
    'cancelada'=> ['bg'=>'#fee2e2','cor'=>'#991b1b','txt'=>'Cancelada'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Meu Painel — Super Seguro</title>
  <link rel="stylesheet" href="css/apolices.css" />
  <style>
    body { background: #f1f5f9; }

    /* ── Welcome bar idêntica ao painel do funcionário ── */
    .welcome-bar {
      background: linear-gradient(135deg, #1a3a5c 0%, #1e5fa8 100%);
      color: #fff;
      padding: 2.5rem 2rem 5.5rem;
      position: relative;
      overflow: hidden;
    }
  
    .welcome-bar h1   { font-size: 1.6rem; font-weight: 700; margin: 0 0 .3rem; }
    .welcome-bar p    { font-size: .9rem;  opacity: .8; margin: 0; }
    .perfil-tag {
      display: inline-block;
      background: rgba(255,255,255,.18);
      border-radius: 20px;
      padding: .2em .9em;
      font-size: .78rem;
      font-weight: 600;
      letter-spacing: .5px;
      text-transform: uppercase;
      margin-bottom: .75rem;
    }

    /* ── Grade de ações rápidas ── */
    .modulos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 1.25rem;
      margin-top: -4rem;
      position: relative;
      z-index: 10;
    }
    .modulo-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 4px 20px rgba(0,0,0,.1);
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: .6rem;
      text-decoration: none;
      color: inherit;
      border: 2px solid transparent;
      transition: all .2s ease;
      cursor: pointer;
    }
    .modulo-card:hover {
      border-color: var(--blue-mid);
      transform: translateY(-3px);
      box-shadow: 0 8px 28px rgba(0,0,0,.13);
    }
    .modulo-icon {
      width: 52px; height: 52px;
      border-radius: 13px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem;
    }
    .modulo-card h3 { font-size: .98rem; font-weight: 700; margin: 0; color: #0f172a; }
    .modulo-card p  { font-size: .82rem; color: var(--gray-500); margin: 0; line-height: 1.5; }
    .modulo-arrow   { margin-top: auto; font-size: .82rem; font-weight: 600; color: var(--blue-mid); }

    /* ── Section title ── */
    .section-title {
      font-size: .78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .7px;
      color: var(--gray-500);
      margin: 2rem 0 .75rem;
    }

    /* ── KPI strip do cliente ── */
    .kpi-strip { display: grid; gap: 1rem; margin-bottom: 1.5rem; }
    .kpi-strip.cols-4 { grid-template-columns: repeat(4,1fr); }

    /* ── Tabela de últimas apólices ── */
    .ap-row {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: .9rem 1.1rem;
      border-bottom: 1px solid var(--gray-100);
      transition: background .15s;
    }
    .ap-row:last-child { border-bottom: none; }
    .ap-row:hover { background: var(--blue-light); }
    .ap-tipo-icon {
      width: 38px; height: 38px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.15rem;
      flex-shrink: 0;
    }
    .ap-info { flex: 1; min-width: 0; }
    .ap-info strong { display: block; font-size: .9rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ap-info span   { font-size: .78rem; color: var(--gray-500); }
    .ap-valor { text-align: right; flex-shrink: 0; }
    .ap-valor strong { display: block; font-size: .9rem; font-weight: 700; color: var(--blue-deep); }
    .ap-valor span   { font-size: .75rem; color: var(--gray-500); }
    .ap-badge {
      font-size: .72rem; font-weight: 600;
      padding: .25em .65em; border-radius: 20px;
      flex-shrink: 0;
    }

    /* ── Card de perfil ── */
    .perfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem 2rem; }
    .perfil-item span    { font-size: .75rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: .5px; }
    .perfil-item strong  { display: block; font-size: .88rem; color: #0f172a; margin-top: .1rem; }

    /* ── Risco badge ── */
    .risco-pill {
      display: inline-flex; align-items: center; gap: .35rem;
      padding: .3em .85em; border-radius: 20px;
      font-size: .82rem; font-weight: 600;
    }
    .risco-dot { width: 8px; height: 8px; border-radius: 50%; }

    @media (max-width: 768px) {
      .kpi-strip.cols-4 { grid-template-columns: 1fr 1fr; }
      .perfil-grid { grid-template-columns: 1fr; }
      .welcome-bar::after { display: none; }
      .page-wrapper { padding: 1rem; }
      .modulos-grid { margin-top: -3rem; }
    }
  </style>
</head>
<body>

<!-- ── Topbar ── -->
<header class="topbar">
  <a class="topbar-brand" href="painel_cliente.php">
  <span>Super Seguro</span>
  </a>
  <nav class="topbar-nav">
    <a href="painel_cliente.php" class="active">Início</a>
    <a href="apolices_cliente.php">Minhas Apólices</a>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<!-- ── Welcome bar ── -->
<div class="welcome-bar">
  <div style="max-width:1200px;margin:0 auto">
    <div class="perfil-tag">Cliente</div>
    <h1>Olá, <?= $primeiroNome ?> 👋</h1>
    <p>Bem-vindo(a) ao seu painel. Gerencie suas apólices e acompanhe suas coberturas.</p>
  </div>
</div>

<!-- ── Conteúdo ── -->
<div class="page-wrapper" style="max-width:1200px;margin:0 auto">

  <!-- Grade de ações rápidas -->
  <div class="modulos-grid">

    <a href="apolices_cliente.php" class="modulo-card">
      <div class="modulo-icon" style="background:#dbeafe">📋</div>
      <h3>Minhas Apólices</h3>
      <p>Visualize todas as suas apólices, prazos de vigência e coberturas contratadas.</p>
      <div class="modulo-arrow">Acessar →</div>
    </a>

    <a href="apolices_cliente.php?status=ativa" class="modulo-card">
      <div class="modulo-icon" style="background:#d1fae5">✅</div>
      <h3>Apólices Ativas</h3>
      <p>Veja apenas as apólices com cobertura vigente no momento.</p>
      <div class="modulo-arrow">Ver ativas →</div>
    </a>

    <a href="apolices_cliente.php?status=expirada" class="modulo-card">
      <div class="modulo-icon" style="background:#fef3c7">🔔</div>
      <h3>Renovações</h3>
      <p>Apólices próximas do vencimento ou que precisam de renovação.</p>
      <div class="modulo-arrow">Ver renovações →</div>
    </a>

  </div>

  <!-- KPIs -->
  <div class="section-title">Resumo das suas coberturas</div>
  <div class="kpi-strip cols-4">
    <div class="kpi blue">
      <label>Apólices Ativas</label>
      <strong><?= (int)($dados['qtd_ativas'] ?? 0) ?></strong>
    </div>
    <div class="kpi green">
      <label>Total em Cobertura</label>
      <strong style="font-size:1.3rem"><?= $fmtMoeda($dados['total_cobertura'] ?? 0) ?></strong>
    </div>
    <div class="kpi amber">
      <label>Prêmio Mensal Total</label>
      <strong style="font-size:1.3rem"><?= $fmtMoeda($dados['total_premios'] ?? 0) ?></strong>
    </div>
    <div class="kpi <?= $riscoLabel === 'Baixo' ? 'green' : ($riscoLabel === 'Alto' ? 'red' : 'amber') ?>">
      <label>Classificação de Risco</label>
      <strong><?= $riscoLabel ?></strong>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;align-items:start">

    <!-- Últimas apólices -->
    <div class="card">
      <div class="card-header">
        <h2>Últimas Apólices</h2>
        <a href="apolices_cliente.php" class="btn btn-outline btn-sm">Ver todas</a>
      </div>
      <div>
        <?php
        $icones = ['Vida'=>'❤️','Saude'=>'🏥','Auto'=>'🚗','Residencial'=>'🏠'];
        $cores  = ['Vida'=>'#fee2e2','Saude'=>'#dbeafe','Auto'=>'#fef3c7','Residencial'=>'#d1fae5'];
        if ($ultimas):
          foreach ($ultimas as $ap):
            $st = $statusBadge[$ap['status']] ?? ['bg'=>'#f1f5f9','cor'=>'#64748b','txt'=>ucfirst($ap['status'])];
        ?>
        <div class="ap-row">
          <div class="ap-tipo-icon" style="background:<?= $cores[$ap['tipo_seguro']] ?? '#f1f5f9' ?>">
            <?= $icones[$ap['tipo_seguro']] ?? '📄' ?>
          </div>
          <div class="ap-info">
            <strong><?= htmlspecialchars($ap['tipo_seguro']) ?></strong>
            <span><?= htmlspecialchars($ap['numero_apolice']) ?> · vence <?= $fmtData($ap['data_fim']) ?></span>
          </div>
          <span class="ap-badge" style="background:<?= $st['bg'] ?>;color:<?= $st['cor'] ?>">
            <?= $st['txt'] ?>
          </span>
          <div class="ap-valor">
            <strong><?= $fmtMoeda($ap['valor_cobertura']) ?></strong>
            <span>cobertura</span>
          </div>
        </div>
        <?php endforeach; else: ?>
        <div class="empty-state"><span class="icon">📋</span><p>Nenhuma apólice encontrada.</p></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Dados do perfil -->
    <div class="card">
      <div class="card-header">
        <h2>Meu Perfil</h2>
      </div>
      <div class="card-body">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem">
          <div style="width:52px;height:52px;border-radius:50%;
                      background:linear-gradient(135deg,#1a3a5c,#1e5fa8);
                      display:flex;align-items:center;justify-content:center;
                      font-size:1.4rem;color:#fff;flex-shrink:0">
            <?= mb_strtoupper(mb_substr($primeiroNome, 0, 1)) ?>
          </div>
          <div>
            <div style="font-weight:700;font-size:1rem;color:#0f172a"><?= $nome ?></div>
            <div style="font-size:.82rem;color:var(--gray-500)"><?= htmlspecialchars($dados['email'] ?? '') ?></div>
          </div>
        </div>

        <div class="perfil-grid">
          <div class="perfil-item">
            <span>CPF</span>
            <strong><?= preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $dados['cpf'] ?? '') ?></strong>
          </div>
          <div class="perfil-item">
            <span>Telefone</span>
            <strong><?= htmlspecialchars($dados['telefone'] ?? '—') ?></strong>
          </div>
          <div class="perfil-item">
            <span>Cidade / Estado</span>
            <strong><?= htmlspecialchars(($dados['cidade'] ?? '—') . ' / ' . ($dados['estado'] ?? '')) ?></strong>
          </div>
          <div class="perfil-item">
            <span>Data de Nascimento</span>
            <strong><?= $fmtData($dados['datanascimento'] ?? '') ?></strong>
          </div>
          <div class="perfil-item">
            <span>Próximo vencimento</span>
            <strong><?= $fmtData($dados['proximo_venc'] ?? '') ?></strong>
          </div>
          <div class="perfil-item">
            <span>Risco atual</span>
            <strong>
              <?php if ($riscoLabel !== '—'): ?>
              <span class="risco-pill" style="background:<?= ['Baixo'=>'#d1fae5','Moderado'=>'#fef3c7','Alto'=>'#fee2e2'][$riscoLabel] ?>;color:<?= $riscoCor ?>">
                <span class="risco-dot" style="background:<?= $riscoCor ?>"></span>
                <?= $riscoLabel ?>
              </span>
              <?php else: ?>—<?php endif; ?>
            </strong>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /grid 2 colunas -->

</div><!-- /page-wrapper -->

<div id="toastArea"></div>

<script>
const toast = (msg, tipo='info') => {
  const el = document.createElement('div');
  el.className = `toast ${tipo}`;
  el.textContent = msg;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => el.remove(), 3500);
};
const p = new URLSearchParams(location.search);
if (p.get('aviso') === 'sessao') toast('Sessão expirada. Faça login novamente.', 'error');
</script>

</body>
</html>