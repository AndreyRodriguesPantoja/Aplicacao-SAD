<?php
session_start();
if (empty($_SESSION['usuario_id'])) {
    header('Location: login-screen.html?erro=sessao');
    exit;
}
$nome = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Analista');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Análise de Risco — SAD Super Seguro</title>
  <link rel="stylesheet" href="css/apolices.css" />
  <style>
    body { background: #f1f5f9; }

    .welcome-bar {
      background: linear-gradient(135deg, #1a3a5c 0%, #1e5fa8 100%);
      color: #fff; padding: 2rem 2rem 4.5rem;
      position: relative; overflow: hidden;
    }
    .welcome-bar::after {
      content: '⚡'; position: absolute; right: 2rem; top: 50%;
      transform: translateY(-50%); font-size: 6rem; opacity: .08; pointer-events: none;
    }
    .welcome-bar h1 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .25rem; }
    .welcome-bar p  { font-size: .88rem; opacity: .8; margin: 0; }

    /* Search bar */
    .search-wrap { position: relative; max-width: 380px; }
    .search-wrap input {
      width: 100%; padding: .5rem .75rem .5rem 2.4rem;
      border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm);
      font-size: .9rem; background: #fff;
      transition: border-color .15s, box-shadow .15s;
    }
    .search-wrap input:focus {
      outline: none; border-color: var(--blue-mid);
      box-shadow: 0 0 0 3px rgba(30,95,168,.13);
    }
    .search-wrap .s-icon { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); font-size: .95rem; }

    /* Cliente row */
    .cli-row {
      display: grid;
      grid-template-columns: 2.5rem 1fr 1fr auto;
      align-items: center; gap: 1rem;
      padding: .85rem 1.25rem;
      border-bottom: 1px solid var(--gray-100);
      transition: background .13s;
    }
    .cli-row:last-child { border-bottom: none; }
    .cli-row:hover { background: var(--blue-light); }
    .cli-avatar {
      width: 38px; height: 38px; border-radius: 50%;
      background: linear-gradient(135deg, #1a3a5c, #1e5fa8);
      color: #fff; font-weight: 700; font-size: .95rem;
      display: flex; align-items: center; justify-content: center;
    }
    .cli-nome    { font-weight: 600; font-size: .9rem; color: var(--gray-900); }
    .cli-sub     { font-size: .77rem; color: var(--gray-500); margin-top: .1rem; }
    .cli-badges  { display: flex; gap: .4rem; flex-wrap: wrap; }
    .cli-actions { display: flex; gap: .5rem; flex-shrink: 0; }

    /* Score pill pequeno */
    .score-sm {
      display: inline-flex; align-items: center; gap: .3rem;
      font-size: .73rem; font-weight: 600;
      padding: .2em .6em; border-radius: 20px;
    }

    /* Select de produto */
    .prod-select {
      padding: .35rem .6rem; border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm); font-size: .82rem;
      font-family: inherit; cursor: pointer;
      transition: border-color .15s;
    }
    .prod-select:focus { outline: none; border-color: var(--blue-mid); }

    /* Loading skeleton */
    .skeleton { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200%; animation: shimmer 1.2s infinite; border-radius: 6px; }
    @keyframes shimmer { to { background-position: -200% center; } }

    @media(max-width:768px) {
      .cli-row { grid-template-columns: 2.2rem 1fr; }
      .cli-badges, .cli-actions { grid-column: 1/-1; }
      .welcome-bar::after { display: none; }
    }
  </style>
</head>
<body>

<header class="topbar">
  <a class="topbar-brand" href="painel_funcionario.php">
    <span class="shield">🛡️</span> SAD | <span>Super Seguro</span>
  </a>
  <nav class="topbar-nav">
    <a href="painel_funcionario.php">Painel</a>
    <a href="lista_clientes_analise.php" class="active">Análise de Risco</a>
    <a href="apolices_analista.html">Apólices</a>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<div class="welcome-bar">
  <div style="max-width:1200px;margin:0 auto">
    <div style="font-size:.78rem;opacity:.7;margin-bottom:.4rem">👔 <?= $nome ?></div>
    <h1>Análise de Risco — Subscrição</h1>
    <p>Selecione um cliente e o produto para iniciar a análise atuarial completa.</p>
  </div>
</div>

<div class="page-wrapper" style="max-width:1200px;margin:0 auto">

  <!-- KPIs rápidos -->
  <div class="kpi-strip cols-3" style="margin-top:-3rem;position:relative;z-index:10;margin-bottom:1.5rem">
    <div class="kpi blue"><label>Clientes Cadastrados</label><strong id="kpiTotal">—</strong></div>
    <div class="kpi green"><label>Com Apólice Ativa</label><strong id="kpiComApolice">—</strong></div>
    <div class="kpi amber"><label>Sem Análise de Risco</label><strong id="kpiSemAnalise">—</strong></div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2>Clientes Disponíveis para Análise</h2>
      <div class="search-wrap">
        <span class="s-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Buscar por nome ou CPF…" oninput="filtrarTabela()" />
      </div>
    </div>

    <div id="corpoLista">
      <!-- skeleton -->
      <div style="padding:1rem 1.25rem;display:flex;flex-direction:column;gap:.75rem">
        <div class="skeleton" style="height:38px"></div>
        <div class="skeleton" style="height:38px"></div>
        <div class="skeleton" style="height:38px"></div>
      </div>
    </div>
  </div>

</div>

<div id="toastArea"></div>

<script>
const toast = (msg, tipo='info') => {
  const el = document.createElement('div');
  el.className = `toast ${tipo}`; el.textContent = msg;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => el.remove(), 3500);
};

const fmtCPF   = v => v ? v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : '—';
const fmtMoeda = v => 'R$ ' + Number(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2});

let todosClientes = [];

async function carregar() {
  try {
    const res  = await fetch('analise_risco_engine.php?listar_clientes=1');
    const data = await res.json();
    todosClientes = data.clientes || [];

    // KPIs
    document.getElementById('kpiTotal').textContent       = todosClientes.length;
    document.getElementById('kpiComApolice').textContent  = todosClientes.filter(c => c.total_apolices > 0).length;
    document.getElementById('kpiSemAnalise').textContent  = todosClientes.filter(c => !c.ultimo_score || c.ultimo_score == 0).length;

    renderizar(todosClientes);
  } catch(e) {
    document.getElementById('corpoLista').innerHTML =
      '<div class="empty-state"><span class="icon">❌</span><p>Erro ao carregar clientes.</p></div>';
  }
}

function renderizar(clientes) {
  const el = document.getElementById('corpoLista');
  if (!clientes.length) {
    el.innerHTML = '<div class="empty-state"><span class="icon">👤</span><p>Nenhum cliente encontrado.</p></div>';
    return;
  }

  const scoreColor = s => {
    const v = Number(s);
    if (!v) return { bg:'#f1f5f9', cor:'#64748b', txt:'Sem análise' };
    if (v <= 3) return { bg:'#d1fae5', cor:'#065f46', txt:`Score ${v}` };
    if (v <= 6) return { bg:'#fef3c7', cor:'#92400e', txt:`Score ${v}` };
    return             { bg:'#fee2e2', cor:'#991b1b', txt:`Score ${v}` };
  };

  el.innerHTML = clientes.map(c => {
    const sc = scoreColor(c.ultimo_score);
    const ini = c.nome ? c.nome.charAt(0).toUpperCase() : '?';
    return `
    <div class="cli-row" data-nome="${c.nome.toLowerCase()}" data-cpf="${c.cpf}">
      <div class="cli-avatar">${ini}</div>
      <div>
        <div class="cli-nome">${c.nome}</div>
        <div class="cli-sub">${fmtCPF(c.cpf)} · ${c.cidade || '—'}/${c.estado || '—'}</div>
      </div>
      <div class="cli-badges">
        <span class="score-sm" style="background:${sc.bg};color:${sc.cor}">${sc.txt}</span>
        ${c.total_apolices > 0
          ? `<span class="badge badge-ativa">${c.total_apolices} apólice(s) ativa(s)</span>`
          : '<span class="badge badge-expirada">Sem apólice</span>'}
      </div>
      <div class="cli-actions">
        <select class="prod-select" id="prod_${c.id}">
          <option value="">Produto…</option>
          <option value="vida">❤️ Vida</option>
          <option value="saude">🏥 Saúde</option>
          <option value="automovel">🚗 Auto</option>
          <option value="residencial">🏠 Residencial</option>
        </select>
        <button class="btn btn-primary btn-sm" onclick="iniciarAnalise(${c.id})">
          Analisar →
        </button>
      </div>
    </div>`;
  }).join('');
}

function filtrarTabela() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const filtrados = todosClientes.filter(c =>
    c.nome.toLowerCase().includes(q) || c.cpf.includes(q)
  );
  renderizar(filtrados);
}

function iniciarAnalise(clienteId) {
  const prod = document.getElementById('prod_' + clienteId).value;
  if (!prod) { toast('Selecione o produto antes de analisar.', 'error'); return; }
  window.location.href = `analise_risco.php?cliente_id=${clienteId}&produto=${prod}`;
}

document.addEventListener('DOMContentLoaded', carregar);
</script>

</body>
</html>