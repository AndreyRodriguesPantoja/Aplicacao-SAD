<?php
// ============================================================
// apolices_cliente.php — Minhas Apólices (visão do cliente)
// ============================================================
session_start();
require 'conexao.php';

if (empty($_SESSION['cliente_id']) || $_SESSION['perfil'] !== 'cliente') {
    header('Location: login-screen.html?perfil=cliente&erro=sessao');
    exit;
}

$clienteId    = (int) $_SESSION['cliente_id'];
$nome         = htmlspecialchars($_SESSION['cliente_nome'] ?? 'Cliente');
$primeiroNome = explode(' ', $nome)[0];

// ── Filtros via GET ──────────────────────────────────────────
$filtroStatus = in_array($_GET['status'] ?? '', ['ativa','suspensa','expirada','cancelada',''])
    ? ($_GET['status'] ?? '') : '';
$filtroTipo   = in_array($_GET['tipo'] ?? '', ['Vida','Saude','Auto','Residencial',''])
    ? ($_GET['tipo'] ?? '') : '';

// ── Query de apólices ────────────────────────────────────────
try {
    $where  = ['a.usuario_id = :id'];
    $params = [':id' => $clienteId];

    if ($filtroStatus !== '') { $where[] = 'a.status = :status'; $params[':status'] = $filtroStatus; }
    if ($filtroTipo   !== '') { $where[] = 'a.tipo_seguro = :tipo'; $params[':tipo'] = $filtroTipo; }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT
            a.id, a.numero_apolice, a.tipo_seguro,
            a.valor_cobertura, a.valor_premio,
            a.data_inicio, a.data_fim, a.status, a.observacoes,
            f.nome AS analista_nome
        FROM apolices a
        LEFT JOIN funcionarios f ON f.id = a.analista_id
        $whereSql
        ORDER BY FIELD(a.status,'ativa','suspensa','expirada','cancelada'), a.data_fim ASC
    ");
    $stmt->execute($params);
    $apolices = $stmt->fetchAll();

    // KPIs sempre sem filtro de tipo/status
    $kpi = $pdo->prepare("
        SELECT
            COUNT(*)                                                      AS total,
            SUM(status = 'ativa')                                         AS ativas,
            SUM(status = 'suspensa')                                      AS suspensas,
            SUM(status = 'expirada')                                      AS expiradas,
            COALESCE(SUM(CASE WHEN status='ativa' THEN valor_cobertura END),0) AS cobertura_total,
            COALESCE(SUM(CASE WHEN status='ativa' THEN valor_premio   END),0) AS premios_total
        FROM apolices
        WHERE usuario_id = :id
    ");
    $kpi->execute([':id' => $clienteId]);
    $kpi = $kpi->fetch();

} catch (PDOException $e) {
    $apolices = [];
    $kpi      = [];
}

// ── Helpers ──────────────────────────────────────────────────
$fmtMoeda = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$fmtData  = fn($d) => $d ? date('d/m/Y', strtotime($d)) : '—';

$vigenciaPct = function(string $ini, string $fim): int {
    $s   = strtotime($ini);
    $e   = strtotime($fim);
    $now = time();
    if ($e <= $s) return 0;
    return max(0, min(100, (int)(($now - $s) / ($e - $s) * 100)));
};

$statusCfg = [
    'ativa'    => ['bg'=>'#d1fae5','cor'=>'#065f46','label'=>'Ativa'],
    'suspensa' => ['bg'=>'#fef3c7','cor'=>'#92400e','label'=>'Suspensa'],
    'expirada' => ['bg'=>'#f1f5f9','cor'=>'#64748b','label'=>'Expirada'],
    'cancelada'=> ['bg'=>'#fee2e2','cor'=>'#991b1b','label'=>'Cancelada'],
];

$tipoIcone = ['Vida'=>'❤️','Saude'=>'🏥','Auto'=>'🚗','Residencial'=>'🏠'];
$tipoCor   = ['Vida'=>'#fee2e2','Saude'=>'#dbeafe','Auto'=>'#fef3c7','Residencial'=>'#d1fae5'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Minhas Apólices — SAD Super Seguro</title>
  <link rel="stylesheet" href="css/apolices.css" />
  <style>
    body { background: #f1f5f9; }

    /* ── Welcome bar ── */
    .welcome-bar {
      background: linear-gradient(135deg, #1a3a5c 0%, #1e5fa8 100%);
      color: #fff;
      padding: 2rem 2rem 4.5rem;
      position: relative;
      overflow: hidden;
    }
    .welcome-bar::after {
      content: '📋';
      position: absolute;
      right: 2rem; top: 50%;
      transform: translateY(-50%);
      font-size: 6rem;
      opacity: .08;
      pointer-events: none;
    }
    .welcome-bar h1 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .25rem; }
    .welcome-bar p  { font-size: .88rem; opacity: .8; margin: 0; }

    /* ── Filtros de tipo (chips) ── */
    .chips {
      display: flex; flex-wrap: wrap; gap: .5rem;
      margin-bottom: 1.25rem;
    }
    .chip {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .35rem .9rem;
      border-radius: 20px;
      border: 1.5px solid var(--gray-200);
      background: #fff;
      font-size: .82rem; font-weight: 500;
      color: var(--gray-700);
      cursor: pointer;
      text-decoration: none;
      transition: all .15s ease;
    }
    .chip:hover { border-color: var(--blue-mid); color: var(--blue-mid); }
    .chip.active {
      background: var(--blue-mid); color: #fff;
      border-color: var(--blue-mid);
    }

    /* ── Cards de apólice ── */
    .apolice-card {
      background: #fff;
      border: 1.5px solid var(--gray-200);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,.06);
      transition: box-shadow .2s, transform .2s;
    }
    .apolice-card:hover {
      box-shadow: 0 6px 24px rgba(0,0,0,.11);
      transform: translateY(-2px);
    }
    .apolice-card-head {
      background: linear-gradient(135deg, #1a3a5c, #1e5fa8);
      color: #fff;
      padding: 1.1rem 1.25rem;
      display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    }
    .apolice-card-head .tipo-wrap { display: flex; align-items: center; gap: .75rem; }
    .tipo-badge-icon {
      width: 42px; height: 42px;
      border-radius: 11px;
      background: rgba(255,255,255,.18);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem;
      flex-shrink: 0;
    }
    .apolice-card-head .tipo  { font-size: 1rem; font-weight: 700; }
    .apolice-card-head .numero { font-size: .75rem; opacity: .75; font-family: monospace; margin-top: .15rem; }
    .status-badge {
      font-size: .73rem; font-weight: 600;
      padding: .25em .75em; border-radius: 20px;
      flex-shrink: 0; align-self: flex-start;
    }
    .apolice-card-body { padding: 1.1rem 1.25rem; }
    .info-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: .75rem 1.25rem;
      margin-bottom: .85rem;
    }
    .info-cell span   { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: var(--gray-500); margin-bottom: .2rem; }
    .info-cell strong { font-size: .9rem; color: #0f172a; font-weight: 600; }
    .info-cell.destaque strong { font-size: 1.05rem; color: var(--blue-deep); }

    /* Barra de vigência */
    .vigencia-wrap { margin-top: .5rem; }
    .vigencia-wrap label { font-size: .75rem; color: var(--gray-500); display: flex; justify-content: space-between; margin-bottom: .3rem; }
    .bar-track { background: var(--gray-200); border-radius: 99px; height: 6px; overflow: hidden; }
    .bar-fill  { height: 100%; border-radius: 99px; transition: width .5s ease; }

    /* Rodapé do card */
    .apolice-card-foot {
      padding: .8rem 1.25rem;
      background: var(--gray-50);
      border-top: 1px solid var(--gray-100);
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: .5rem;
    }
    .apolice-card-foot .analista { font-size: .78rem; color: var(--gray-500); }
    .apolice-card-foot .obs      { font-size: .78rem; color: var(--gray-700); font-style: italic; }

    /* Modal */
    .modal-backdrop { display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:200;align-items:center;justify-content:center;padding:1rem; }
    .modal-backdrop.open { display:flex; }
    .modal-box { background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:560px;max-height:90vh;overflow-y:auto;animation:fadeUp .2s ease; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
    .modal-head { padding:1.25rem 1.5rem;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between; }
    .modal-head h3 { font-size:1.05rem;font-weight:700;margin:0;color:var(--blue-deep); }
    .modal-close { background:none;border:none;cursor:pointer;font-size:1.3rem;color:var(--gray-500);padding:.2rem .4rem;border-radius:6px;transition:all .15s; }
    .modal-close:hover { background:var(--gray-100);color:#0f172a; }
    .modal-body { padding:1.5rem; }
    .modal-foot { padding:1rem 1.5rem;border-top:1px solid var(--gray-200);display:flex;justify-content:flex-end;gap:.75rem; }

    /* Detalhe modal */
    .detalhe-grid { display:grid;grid-template-columns:1fr 1fr;gap:.75rem 1.5rem; }
    .detalhe-item span   { font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500); }
    .detalhe-item strong { display:block;font-size:.92rem;color:#0f172a;margin-top:.15rem; }

    @media(max-width:768px) {
      .info-grid { grid-template-columns:1fr 1fr; }
      .detalhe-grid { grid-template-columns:1fr; }
      .welcome-bar::after { display:none; }
      .page-wrapper { padding:1rem; }
    }
    @media(max-width:480px) {
      .info-grid { grid-template-columns:1fr; }
      .apolice-card-head { flex-direction:column; }
    }
  </style>
</head>
<body>

<!-- ── Topbar ── -->
<header class="topbar">
  <a class="topbar-brand" href="painel_cliente.php">
    <span class="shield">🛡️</span>
    SAD | <span>Super Seguro</span>
  </a>
  <nav class="topbar-nav">
    <a href="painel_cliente.php">Início</a>
    <a href="apolices_cliente.php" class="active">Minhas Apólices</a>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<!-- ── Welcome bar ── -->
<div class="welcome-bar">
  <div style="max-width:1200px;margin:0 auto">
    <div style="font-size:.8rem;opacity:.7;margin-bottom:.4rem">👤 <?= $nome ?></div>
    <h1>Minhas Apólices</h1>
    <p>Acompanhe suas coberturas ativas, vigências e valores contratados.</p>
  </div>
</div>

<!-- ── Conteúdo ── -->
<div class="page-wrapper" style="max-width:1200px;margin:0 auto">

  <!-- KPIs -->
  <div class="kpi-strip cols-4" style="margin-top:-3rem;position:relative;z-index:10">
    <div class="kpi blue">
      <label>Total de Apólices</label>
      <strong><?= (int)($kpi['total']    ?? 0) ?></strong>
    </div>
    <div class="kpi green">
      <label>Ativas</label>
      <strong><?= (int)($kpi['ativas']   ?? 0) ?></strong>
    </div>
    <div class="kpi green">
      <label>Cobertura Total</label>
      <strong style="font-size:1.25rem"><?= $fmtMoeda($kpi['cobertura_total'] ?? 0) ?></strong>
    </div>
    <div class="kpi amber">
      <label>Prêmio Mensal</label>
      <strong style="font-size:1.25rem"><?= $fmtMoeda($kpi['premios_total']   ?? 0) ?></strong>
    </div>
  </div>

  <!-- Filtros -->
  <div style="margin:1.75rem 0 1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
    <div class="chips">
      <?php
      $statusFiltros = [''         => 'Todas',
                        'ativa'    => '✅ Ativas',
                        'suspensa' => '⏸️ Suspensas',
                        'expirada' => '🕐 Expiradas',
                        'cancelada'=> '❌ Canceladas'];
      foreach ($statusFiltros as $val => $lbl):
        $ativo = ($filtroStatus === $val) ? 'active' : '';
        $href  = '?' . http_build_query(array_filter(['status'=>$val,'tipo'=>$filtroTipo]));
      ?>
      <a href="<?= $href ?>" class="chip <?= $ativo ?>"><?= $lbl ?></a>
      <?php endforeach; ?>
    </div>

    <div class="chips">
      <?php
      $tipoFiltros = [''=>'🗂️ Todos','Vida'=>'❤️ Vida','Saude'=>'🏥 Saúde','Auto'=>'🚗 Auto','Residencial'=>'🏠 Residencial'];
      foreach ($tipoFiltros as $val => $lbl):
        $ativo = ($filtroTipo === $val) ? 'active' : '';
        $href  = '?' . http_build_query(array_filter(['status'=>$filtroStatus,'tipo'=>$val]));
      ?>
      <a href="<?= $href ?>" class="chip <?= $ativo ?>"><?= $lbl ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Resultado -->
  <?php if (empty($apolices)): ?>
  <div class="card">
    <div class="empty-state">
      <span class="icon">📋</span>
      <p>Nenhuma apólice encontrada<?= $filtroStatus || $filtroTipo ? ' para os filtros selecionados' : '' ?>.</p>
    </div>
  </div>
  <?php else: ?>

  <div style="display:flex;flex-direction:column;gap:1rem">
    <?php foreach ($apolices as $ap):
      $st  = $statusCfg[$ap['status']] ?? ['bg'=>'#f1f5f9','cor'=>'#64748b','label'=>$ap['status']];
      $pct = $vigenciaPct($ap['data_inicio'], $ap['data_fim']);
      $barCor = $pct >= 85 ? '#ef4444' : ($pct >= 65 ? '#d97706' : '#1e5fa8');
    ?>
    <div class="apolice-card">

      <!-- Cabeçalho -->
      <div class="apolice-card-head">
        <div class="tipo-wrap">
          <div class="tipo-badge-icon"><?= $tipoIcone[$ap['tipo_seguro']] ?? '📄' ?></div>
          <div>
            <div class="tipo"><?= htmlspecialchars($ap['tipo_seguro']) ?></div>
            <div class="numero"><?= htmlspecialchars($ap['numero_apolice']) ?></div>
          </div>
        </div>
        <span class="status-badge"
              style="background:<?= $st['bg'] ?>;color:<?= $st['cor'] ?>">
          <?= $st['label'] ?>
        </span>
      </div>

      <!-- Corpo -->
      <div class="apolice-card-body">
        <div class="info-grid">
          <div class="info-cell destaque">
            <span>Cobertura</span>
            <strong><?= $fmtMoeda($ap['valor_cobertura']) ?></strong>
          </div>
          <div class="info-cell">
            <span>Prêmio Mensal</span>
            <strong><?= $fmtMoeda($ap['valor_premio']) ?></strong>
          </div>
          <div class="info-cell">
            <span>Início</span>
            <strong><?= $fmtData($ap['data_inicio']) ?></strong>
          </div>
          <div class="info-cell">
            <span>Vencimento</span>
            <strong><?= $fmtData($ap['data_fim']) ?></strong>
          </div>
        </div>

        <?php if ($ap['status'] === 'ativa'): ?>
        <div class="vigencia-wrap">
          <label>
            <span>Vigência decorrida</span>
            <span><?= $pct ?>%</span>
          </label>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $barCor ?>"></div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Rodapé -->
      <div class="apolice-card-foot">
        <div>
          <?php if ($ap['observacoes']): ?>
          <div class="obs">📝 <?= htmlspecialchars(mb_strimwidth($ap['observacoes'], 0, 80, '…')) ?></div>
          <?php endif; ?>
          <?php if ($ap['analista_nome']): ?>
          <div class="analista">Analista responsável: <?= htmlspecialchars($ap['analista_nome']) ?></div>
          <?php endif; ?>
        </div>
        <button class="btn btn-outline btn-sm"
                onclick="abrirDetalhe(<?= htmlspecialchars(json_encode($ap)) ?>)">
          Ver detalhes
        </button>
      </div>

    </div><!-- /apolice-card -->
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div><!-- /page-wrapper -->

<!-- ── Modal: Detalhes da Apólice ── -->
<div class="modal-backdrop" id="modalDetalhe">
  <div class="modal-box">
    <div class="modal-head">
      <h3 id="modalTitulo">Detalhes da Apólice</h3>
      <button class="modal-close" onclick="fecharModal()">✕</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-foot">
      <button class="btn btn-outline" onclick="fecharModal()">Fechar</button>
      <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir</button>
    </div>
  </div>
</div>

<div id="toastArea"></div>

<script>
const STATUS_CFG = {
  ativa:    { bg:'#d1fae5', cor:'#065f46', label:'Ativa'     },
  suspensa: { bg:'#fef3c7', cor:'#92400e', label:'Suspensa'  },
  expirada: { bg:'#f1f5f9', cor:'#64748b', label:'Expirada'  },
  cancelada:{ bg:'#fee2e2', cor:'#991b1b', label:'Cancelada' },
};

const fmtMoeda = v => 'R$ ' + Number(v||0).toLocaleString('pt-BR', {minimumFractionDigits:2});
const fmtData  = d => d ? new Date(d+'T00:00').toLocaleDateString('pt-BR') : '—';

const vigPct = (ini, fim) => {
  const s = new Date(ini).getTime(), e = new Date(fim).getTime(), n = Date.now();
  return Math.max(0, Math.min(100, Math.round((n-s)/(e-s)*100)));
};

function abrirDetalhe(ap) {
  const st  = STATUS_CFG[ap.status] ?? {bg:'#f1f5f9',cor:'#64748b',label:ap.status};
  const pct = vigPct(ap.data_inicio, ap.data_fim);
  const barCor = pct >= 85 ? '#ef4444' : pct >= 65 ? '#d97706' : '#1e5fa8';

  document.getElementById('modalTitulo').textContent = `${ap.tipo_seguro} — ${ap.numero_apolice}`;

  document.getElementById('modalBody').innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
      <code style="font-size:.9rem;background:#f1f5f9;padding:.3em .7em;border-radius:6px;font-weight:700;color:#1a3a5c">
        ${ap.numero_apolice}
      </code>
      <span style="background:${st.bg};color:${st.cor};font-size:.78rem;font-weight:600;
                   padding:.28em .8em;border-radius:20px">${st.label}</span>
    </div>

    <div class="detalhe-grid" style="margin-bottom:1rem">
      <div class="detalhe-item"><span>Tipo de Seguro</span><strong>${ap.tipo_seguro}</strong></div>
      <div class="detalhe-item"><span>Status</span><strong>${st.label}</strong></div>
      <div class="detalhe-item"><span>Cobertura</span><strong>${fmtMoeda(ap.valor_cobertura)}</strong></div>
      <div class="detalhe-item"><span>Prêmio Mensal</span><strong>${fmtMoeda(ap.valor_premio)}</strong></div>
      <div class="detalhe-item"><span>Início da Vigência</span><strong>${fmtData(ap.data_inicio)}</strong></div>
      <div class="detalhe-item"><span>Fim da Vigência</span><strong>${fmtData(ap.data_fim)}</strong></div>
    </div>

    ${ap.status === 'ativa' ? `
    <div style="margin-bottom:1rem">
      <div style="display:flex;justify-content:space-between;font-size:.78rem;color:#64748b;margin-bottom:.35rem">
        <span>Vigência decorrida</span><span>${pct}%</span>
      </div>
      <div style="background:#e2e8f0;border-radius:99px;height:8px;overflow:hidden">
        <div style="width:${pct}%;height:100%;background:${barCor};border-radius:99px;transition:width .5s"></div>
      </div>
    </div>` : ''}

    ${ap.observacoes ? `
    <div style="background:#f8fafc;border-radius:8px;padding:.85rem 1rem;margin-top:.5rem">
      <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:.35rem">Observações</div>
      <p style="margin:0;font-size:.88rem;color:#334155">${ap.observacoes}</p>
    </div>` : ''}
  `;

  document.getElementById('modalDetalhe').classList.add('open');
}

function fecharModal() {
  document.getElementById('modalDetalhe').classList.remove('open');
}

// Fecha ao clicar no backdrop
document.getElementById('modalDetalhe').addEventListener('click', e => {
  if (e.target === e.currentTarget) fecharModal();
});

// Toast
const toast = (msg, tipo='info') => {
  const el = document.createElement('div');
  el.className = `toast ${tipo}`;
  el.textContent = msg;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => el.remove(), 3500);
};
</script>

</body>
</html>
