<?php
// ============================================================
// painel_funcionario.php — Painel interno pós-login
// Acesso: analistas e gerentes
// ============================================================
session_start();

// Protege a rota: apenas funcionários autenticados
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['perfil'])) {
    header('Location: login-screen.html');
    exit;
}

$nome   = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Funcionário');
$login  = htmlspecialchars($_SESSION['usuario_login'] ?? '');
$perfil = $_SESSION['perfil'] ?? 'analista';   // 'analista' | 'gerente'
$isGerente = ($perfil === 'gerente');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Painel — Super Seguro</title>
  <link rel="stylesheet" href="css/apolices.css" />
  <style>
    /* ── Sobrescritas locais ── */
    body { background: #f1f5f9; }

    .welcome-bar {
      background: linear-gradient(135deg, #1a3a5c 0%, #1e5fa8 100%);
      color: #fff;
      padding: 2.5rem 2rem 5rem;
      position: relative;
      overflow: hidden;
    }
    .welcome-bar::after {
      content: '🛡️';
      position: absolute;
      right: 2rem; top: 50%;
      transform: translateY(-50%);
      font-size: 6rem;
      opacity: .08;
      pointer-events: none;
    }
    .welcome-bar h1 { font-size: 1.6rem; font-weight: 700; margin: 0 0 .3rem; }
    .welcome-bar p  { font-size: .9rem; opacity: .8; margin: 0; }
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

    /* ── Cards de módulo ── */
    .modulos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.25rem;
      margin-top: -3.5rem;        /* sobe sobre o welcome-bar */
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
      gap: .75rem;
      text-decoration: none;
      color: inherit;
      border: 2px solid transparent;
      transition: all .2s ease;
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
    .modulo-card h3 { font-size: 1rem; font-weight: 700; margin: 0; color: #0f172a; }
    .modulo-card p  { font-size: .83rem; color: var(--gray-500); margin: 0; line-height: 1.5; }
    .modulo-arrow   { margin-top: auto; font-size: .82rem; font-weight: 600; color: var(--blue-mid); }

    /* ── Seção de atividade recente ── */
    .section-title {
      font-size: .78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .7px;
      color: var(--gray-500);
      margin: 2rem 0 .75rem;
    }

    @media (max-width: 600px) {
      .welcome-bar { padding: 2rem 1rem 4.5rem; }
      .welcome-bar::after { display: none; }
      .page-wrapper { padding: 1rem; }
    }
  </style>
</head>
<body>

<header class="topbar">
  <a class="topbar-brand" href="painel_funcionario.php">
    <span class="shield">🛡️</span>
    <span>Super Seguro</span>
  </a>
  <nav class="topbar-nav">
    <a href="painel_funcionario.php" class="active">Painel</a>
    <a href="apolices_analista.html">Apólices</a>
    <?php if ($isGerente): ?>
    <a href="index.html">Dashboard</a>
    <?php endif; ?>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<div class="welcome-bar">
  <div style="max-width:1200px;margin:0 auto">
    <div class="perfil-tag"><?= ucfirst($perfil) ?></div>
    <h1>Olá, <?= $nome ?> 👋</h1>
    <p>Bem-vindo(a) ao painel interno. Selecione um módulo para começar.</p>
  </div>
</div>

<div class="page-wrapper" style="max-width:1200px;margin:0 auto">

  <div class="modulos-grid">

    <a href="apolices_analista.html" class="modulo-card">
      <div class="modulo-icon" style="background:#dbeafe">📋</div>
      <h3>Gestão de Apólices</h3>
      <p>Visualize, filtre e crie novas apólices para os clientes cadastrados. Altere status e exporte relatórios.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>

    <?php if ($isGerente): ?>
    <a href="index.html" class="modulo-card">
      <div class="modulo-icon" style="background:#d1fae5">📊</div>
      <h3>Dashboard BI</h3>
      <p>Acompanhe KPIs de risco, distribuição por faixa etária, gráficos e histórico de análises.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>

    <a href="index.html#relatorio" class="modulo-card">
      <div class="modulo-icon" style="background:#fef3c7">📈</div>
      <h3>Relatórios de Decisão</h3>
      <p>Gere relatórios automáticos de apoio à decisão com taxas de aprovação, negação e recomendações.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>
    <?php endif; ?>

    <a href="cadastro_cliente.php" class="modulo-card">
      <div class="modulo-icon" style="background:#ede9fe">👤</div>
      <h3>Cadastro de Clientes</h3>
      <p>Registre novos clientes no sistema com dados pessoais, endereço e situação financeira.</p>
      <div class="modulo-arrow">Abrir Tela de Cadastro →</div>
    </a>

    <a href="index.html" class="modulo-card">
      <div class="modulo-icon" style="background:#fee2e2">⚡</div>
      <h3>Simulador de Risco</h3>
      <p>Analise perfis de clientes por idade e IMC para classificar o risco e apoiar decisões de aprovação.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>

  </div><div class="section-title">Informações da Sessão</div>
  <div class="card" style="background:#fff; border-radius:14px; padding:1.5rem; box-shadow:0 4px 20px rgba(0,0,0,.05);">
    <div class="card-body" style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap">
      <div style="font-size:2.5rem">🔐</div>
      <div style="display:grid;gap:.25rem">
        <div style="font-size:.82rem;color:var(--gray-500)">Logado como</div>
        <div style="font-weight:700;font-size:.95rem"><?= $nome ?></div>
        <div style="font-size:.82rem;color:var(--gray-500)">@<?= $login ?> —
          <span style="color:var(--blue-mid);font-weight:600"><?= ucfirst($perfil) ?></span>
        </div>
      </div>
      <div style="margin-left:auto">
        <a href="logout.php" class="btn btn-outline btn-sm" style="text-decoration:none; color:inherit; border:1px solid #cbd5e1; padding:0.5rem 1rem; border-radius:8px; font-size:0.85rem;">Encerrar sessão</a>
      </div>
    </div>
  </div>

</div><div id="toastArea"></div>

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