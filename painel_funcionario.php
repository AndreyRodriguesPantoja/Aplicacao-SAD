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
  <link rel="stylesheet" href="css/painel_funcionario.css" />
</head>
<body>

<!-- ── Topbar ── -->
<header class="topbar">
  <a class="topbar-brand" href="painel_funcionario.php">
      <span>Super Seguro</span>
  </a>
  <nav class="topbar-nav">
    <a href="painel_funcionario.php" class="active">Painel</a>
    <a href="lista_clientes_analise.php">Risco</a>
    <a href="sinistros.php">Sinistros</a>
    <a href="apolices_analista.html">Apólices</a>
    <?php if ($isGerente): ?>
    <a href="index.html">Dashboard</a>
    <?php endif; ?>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<!-- ── Barra de boas-vindas ── -->
<div class="welcome-bar">
  <div style="max-width:1200px;margin:0 auto">
    <div class="perfil-tag"><?= ucfirst($perfil) ?></div>
    <h1>Olá, <?= $nome ?> 👋</h1>
    <p>Bem-vindo(a) ao painel interno. Selecione um módulo para começar.</p>
  </div>
</div>

<!-- ── Conteúdo ── -->
<div class="page-wrapper" style="max-width:1200px;margin:0 auto">

  <!-- Grade de módulos -->
  <div class="modulos-grid">

      <!-- Cadastro de clientes — [analista|gerente] -->
    <a href="cadastro_cliente.php" class="modulo-card">
      <div class="modulo-icon" style="background:#ede9fe">👤</div>
      <h3>Cadastro de Clientes</h3>
      <p>Registre novos clientes no sistema com dados pessoais, endereço e situação financeira.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>

      <!-- Análise de Risco — [analista|gerente] -->
    <a href="lista_clientes_analise.php" class="modulo-card">
      <div class="modulo-icon" style="background:#fee2e2">🔍</div>
      <h3>Análise de Risco</h3>
      <p>Motor atuarial completo: score por idade, IMC, renda, geolocalização, condições pré-existentes e profissão.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>

    <!-- Apólices — [analista|gerente] -->
    <a href="apolices_analista.html" class="modulo-card">
      <div class="modulo-icon" style="background:#dbeafe">📋</div>
      <h3>Gestão de Apólices</h3>
      <p>Visualize, filtre e crie novas apólices para os clientes cadastrados. Altere status e exporte relatórios.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>

    <!-- Sinistros — [analista|gerente] -->
    <a href="sinistros.php" class="modulo-card">
      <div class="modulo-icon" style="background:#fef3c7">🔎</div>
      <h3>Análise de Sinistros</h3>
      <p>Registre ocorrências, execute análise antifraude automática e tome decisões de indenização com base em critérios SUSEP.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>

    <?php if ($isGerente): ?>
    <!-- Dashboard — [gerente] -->
    <a href="index.html" class="modulo-card">
      <div class="modulo-icon" style="background:#d1fae5">📊</div>
      <h3>Dashboard</h3>
      <p>Acompanhe KPIs de risco, distribuição por faixa etária, gráficos e histórico de análises.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>

    <!-- Relatório — [gerente] -->
    <a href="index.html#relatorio" class="modulo-card">
      <div class="modulo-icon" style="background:#fef3c7">📈</div>
      <h3>Relatórios de Decisão</h3>
      <p>Gere relatórios automáticos de apoio à decisão com taxas de aprovação, negação e recomendações.</p>
      <div class="modulo-arrow">Acessar módulo →</div>
    </a>
    <?php endif; ?>

    
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
        <a href="logout.php" class="btn btn-outline btn-sm">Encerrar sessão</a>
      </div>
    </div>
  </div>

</div><!-- /page-wrapper -->

<div id="toastArea"></div>

<script>
// Toast reutilizável
const toast = (msg, tipo='info') => {
  const el = document.createElement('div');
  el.className = `toast ${tipo}`;
  el.textContent = msg;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => el.remove(), 3500);
};

// Mostra mensagem de erro de login redirecionado (se houver)
const p = new URLSearchParams(location.search);
if (p.get('aviso') === 'sessao') toast('Sessão expirada. Faça login novamente.', 'error');
</script>

</body>
</html>
