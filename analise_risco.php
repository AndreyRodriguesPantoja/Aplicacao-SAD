<?php
session_start();
if (empty($_SESSION['usuario_id'])) {
    header('Location: login-screen.html?erro=sessao');
    exit;
}

$clienteId = (int)   ($_GET['cliente_id'] ?? 0);
$produto   = htmlspecialchars(strtolower(trim($_GET['produto'] ?? 'vida')));

if (!$clienteId || !$produto) {
    header('Location: lista_clientes_analise.php');
    exit;
}

$nomesProduto = ['vida'=>'Seguro de Vida','saude'=>'Seguro Saúde','automovel'=>'Seguro Auto','residencial'=>'Seguro Residencial'];
$nomeProduto  = $nomesProduto[$produto] ?? ucfirst($produto);
$analistaNome = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Analista');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Análise de Subscrição — Super Seguro</title>
  <link rel="stylesheet" href="css/apolices.css" />
  <style>
    body { background: #f1f5f9; }

    /* ── Hero da análise ── */
    .analysis-hero {
      background: linear-gradient(135deg, #1a3a5c 0%, #1e5fa8 100%);
      color: #fff; padding: 2rem 2rem 5rem;
      position: relative; overflow: hidden;
    }
    .analysis-hero::after {
      content: '🔬'; position: absolute; right: 2rem; top: 50%;
      transform: translateY(-50%); font-size: 6rem; opacity: .08;
    }
    .analysis-hero h1 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .25rem; }
    .analysis-hero p  { font-size: .88rem; opacity: .8; margin: 0; }

    /* ── Score gauge ── */
    .score-panel {
      text-align: center; padding: 2rem 1.5rem;
      background: #fff; border-radius: var(--radius-lg);
      box-shadow: var(--shadow-md);
      margin-top: -3.5rem; position: relative; z-index: 10;
    }
    .score-circle {
      width: 140px; height: 140px;
      border-radius: 50%;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      margin: 0 auto 1rem;
      border: 8px solid;
      position: relative;
    }
    .score-circle .score-num {
      font-size: 2.4rem; font-weight: 800; line-height: 1;
    }
    .score-circle .score-sub {
      font-size: .75rem; font-weight: 600;
      text-transform: uppercase; letter-spacing: .5px;
      opacity: .75; margin-top: .2rem;
    }
    .decisao-badge {
      display: inline-block;
      font-size: .85rem; font-weight: 700;
      padding: .45em 1.2em; border-radius: 25px;
      letter-spacing: .5px; text-transform: uppercase;
      margin-bottom: .75rem;
    }
    .decisao-desc { font-size: .85rem; color: var(--gray-500); max-width: 320px; margin: 0 auto; }

    /* ── Fatores ── */
    .fator-row {
      display: grid;
      grid-template-columns: 1.5rem 1fr auto auto;
      align-items: center;
      gap: .75rem;
      padding: .85rem 0;
      border-bottom: 1px solid var(--gray-100);
    }
    .fator-row:last-child { border-bottom: none; }
    .fator-dot { width: 10px; height: 10px; border-radius: 50%; }
    .fator-label { font-size: .88rem; color: var(--gray-700); }
    .fator-valor {
      font-size: .8rem; font-weight: 600;
      padding: .2em .6em; border-radius: 20px;
      white-space: nowrap;
    }
    .fator-impacto { font-size: .8rem; color: var(--gray-500); white-space: nowrap; text-align: right; min-width: 60px; }

    /* ── Barra de fator ── */
    .fator-bar-wrap { grid-column: 2; margin-top: -.2rem; }
    .fator-bar { height: 4px; background: var(--gray-200); border-radius: 99px; overflow: hidden; }
    .fator-bar-fill { height: 100%; border-radius: 99px; }

    /* ── Alertas ── */
    .alerta-item {
      display: flex; align-items: flex-start; gap: .6rem;
      padding: .65rem .9rem;
      border-radius: var(--radius-sm);
      font-size: .85rem;
      margin-bottom: .5rem;
    }
    .alerta-item:last-child { margin-bottom: 0; }
    .alerta-item.warn { background: var(--amber-light); color: #92400e; }
    .alerta-item.danger{ background: var(--red-light);   color: var(--red); }

    /* ── Formulário de condições ── */
    .condicoes-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: .5rem;
    }
    .check-item {
      display: flex; align-items: center; gap: .5rem;
      padding: .5rem .75rem;
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm);
      cursor: pointer; font-size: .85rem;
      transition: all .15s;
      user-select: none;
    }
    .check-item:hover { border-color: var(--blue-mid); background: var(--blue-light); }
    .check-item input[type=checkbox] { accent-color: var(--blue-mid); width: 15px; height: 15px; }
    .check-item.checked { border-color: var(--blue-mid); background: var(--blue-light); }

    /* ── Prêmio ── */
    .premio-box {
      background: linear-gradient(135deg, #1a3a5c, #1e5fa8);
      color: #fff; border-radius: var(--radius-md);
      padding: 1.25rem 1.5rem;
      display: flex; align-items: center; justify-content: space-between;
    }
    .premio-box .val { font-size: 1.8rem; font-weight: 800; }
    .premio-box .sub { font-size: .8rem; opacity: .75; }

    /* Skeleton */
    .skeleton { background: linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%); background-size:200%; animation:shimmer 1.2s infinite; border-radius:6px; }
    @keyframes shimmer { to { background-position:-200% center; } }

    @media(max-width:768px) {
      .fator-row { grid-template-columns: 1rem 1fr; }
      .fator-valor, .fator-impacto { grid-column: 2; }
      .analysis-hero::after { display: none; }
    }
  </style>
</head>
<body>

<header class="topbar">
  <a class="topbar-brand" href="painel_funcionario.php">
    <span>Super Seguro</span>
  </a>
  <nav class="topbar-nav">
    <a href="painel_funcionario.php">Painel</a>
    <a href="lista_clientes_analise.php" class="active">Análise de Risco</a>
    <a href="apolices_analista.html">Apólices</a>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<!-- Hero -->
<div class="analysis-hero">
  <div style="max-width:1100px;margin:0 auto">
    <div style="font-size:.78rem;opacity:.7;margin-bottom:.4rem">
      👔 <?= $analistaNome ?> · <?= $nomeProduto ?>
    </div>
    <h1>Análise de Subscrição</h1>
    <p>Motor atuarial — critérios SUSEP · Tábua BR-EMS 2021 · ISO</p>
  </div>
</div>

<div class="page-wrapper" style="max-width:1100px;margin:0 auto">
<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:1.25rem;align-items:start">

  <!-- ── COLUNA ESQUERDA ── -->
  <div style="display:flex;flex-direction:column;gap:1.25rem">

    <!-- Score -->
    <div class="score-panel" id="scorePanel">
      <div class="skeleton" style="width:140px;height:140px;border-radius:50%;margin:0 auto 1rem"></div>
      <div class="skeleton" style="width:140px;height:28px;margin:0 auto .5rem"></div>
      <div class="skeleton" style="width:200px;height:16px;margin:0 auto"></div>
    </div>

    <!-- Prêmio estimado -->
    <div class="card" id="premioPanel" style="display:none">
      <div class="card-header"><h2>Prêmio Estimado</h2></div>
      <div class="card-body">
        <div class="premio-box">
          <div>
            <div class="sub">Prêmio mensal</div>
            <div class="val" id="premioValor">—</div>
          </div>
          <div style="font-size:2rem">💰</div>
        </div>
        <div id="premioAviso" style="display:none;margin-top:.75rem;font-size:.82rem;color:var(--amber);font-weight:500"></div>
        <div style="margin-top:1rem;display:flex;gap:.75rem;flex-wrap:wrap">
          <button class="btn btn-primary" id="btnSalvar" onclick="salvarAnalise()">
            💾 Salvar Análise
          </button>
          <a href="lista_clientes_analise.php" class="btn btn-outline">← Voltar</a>
        </div>
      </div>
    </div>

    <!-- Info do cliente -->
    <div class="card" id="clientePanel" style="display:none">
      <div class="card-header"><h2>Cliente</h2></div>
      <div class="card-body" id="clienteInfo"></div>
    </div>

  </div>

  <!-- ── COLUNA DIREITA ── -->
  <div style="display:flex;flex-direction:column;gap:1.25rem">

    <!-- Formulário de parâmetros extras -->
    <div class="card">
      <div class="card-header"><h2>⚙️ Parâmetros da Análise</h2></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
          <div class="form-group">
            <label class="form-label">Valor a Segurar (R$) *</label>
            <input type="number" class="form-control" id="paramValorCobertura" step="0.01"
                   placeholder="Ex: 100000.00" min="0" />
          </div>
          <div class="form-group">
            <label class="form-label">IMC (kg/m²)</label>
            <input type="number" class="form-control" id="paramIMC" step="0.1"
                   placeholder="Ex: 24.5" min="10" max="60" />
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1rem">
          <div class="form-group">
            <label class="form-label">Profissão</label>
            <input type="text" class="form-control" id="paramProfissao"
                   placeholder="Ex: médico, policial…" />
          </div>
        </div>

        <div class="form-group" id="grupoCondicoes"
             style="<?= in_array($produto, ['vida','saude']) ? '' : 'display:none' ?>">
          <label class="form-label" style="margin-bottom:.6rem">
            Condições pré-existentes
          </label>
          <div class="condicoes-grid" id="condicoesGrid">
            <?php
            $condicoes = [
              'diabetes'        => ['🩸', 'Diabetes'],
              'hipertensao'     => ['💊', 'Hipertensão'],
              'doenca_cardiaca' => ['❤️‍🩹', 'Doença Cardíaca'],
              'cancer'          => ['🔬', 'Histórico de Câncer'],
              'fumante'         => ['🚬', 'Tabagismo'],
              'alcoolismo'      => ['🍺', 'Uso de Álcool'],
              'doenca_renal'    => ['🫘', 'Doença Renal'],
              'depressao'       => ['🧠', 'Depressão/Ansiedade'],
            ];
            foreach ($condicoes as $key => [$ico, $label]):
            ?>
            <label class="check-item" id="lbl_<?= $key ?>">
              <input type="checkbox" name="condicao" value="<?= $key ?>"
                     onchange="document.getElementById('lbl_<?= $key ?>').classList.toggle('checked', this.checked)" />
              <?= $ico ?> <?= $label ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <button class="btn btn-primary" style="margin-top:1rem;width:100%" onclick="rodarAnalise()">
          ⚡ Calcular Risco
        </button>
      </div>
    </div>

    <!-- Fatores de risco -->
    <div class="card" id="fatoresPanel" style="display:none">
      <div class="card-header"><h2>📊 Fatores de Risco</h2></div>
      <div class="card-body" id="fatoresBody"></div>
    </div>

    <!-- Alertas e exclusões -->
    <div id="alertasPanel" style="display:none">
      <div class="card">
        <div class="card-header"><h2>⚠️ Alertas e Restrições</h2></div>
        <div class="card-body" id="alertasBody"></div>
      </div>
    </div>

  </div>
</div>
</div>

<div id="toastArea"></div>

<script>
const CLIENTE_ID = <?= $clienteId ?>;
const PRODUTO    = '<?= $produto ?>';
let ultimaAnalise = null;

const toast = (msg, tipo='info') => {
  const el = document.createElement('div');
  el.className = `toast ${tipo}`; el.textContent = msg;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => el.remove(), 4000);
};

const fmtMoeda = v => 'R$ ' + Number(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2});
const fmtCPF   = v => v ? v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,'$1.$2.$3-$4') : '—';

// ── Cores por decisão ──────────────────────────────────────
const COR = {
  green: { border:'#10b981', bg:'#d1fae5', txt:'#065f46', badgeBg:'#10b981', badgeTxt:'#fff' },
  amber: { border:'#d97706', bg:'#fef3c7', txt:'#92400e', badgeBg:'#d97706', badgeTxt:'#fff' },
  red:   { border:'#ef4444', bg:'#fee2e2', txt:'#991b1b', badgeBg:'#ef4444', badgeTxt:'#fff' },
};

async function rodarAnalise() {
  const valorCobertura = document.getElementById('paramValorCobertura').value;
  const imc       = document.getElementById('paramIMC').value;
  const profissao = document.getElementById('paramProfissao').value;
  const condicoes = [...document.querySelectorAll('input[name=condicao]:checked')]
                      .map(c => c.value).join(',');

  const params = new URLSearchParams({
    cliente_id: CLIENTE_ID,
    produto:    PRODUTO,
    t:          Date.now(),
  });
  if (valorCobertura) params.set('valor_cobertura', valorCobertura);
  if (imc)       params.set('imc',       imc);
  if (profissao) params.set('profissao', profissao);
  if (condicoes) params.set('condicoes', condicoes);

  // Loading state
  document.getElementById('scorePanel').innerHTML = `
    <div class="skeleton" style="width:140px;height:140px;border-radius:50%;margin:0 auto 1rem"></div>
    <div class="skeleton" style="width:140px;height:28px;margin:0 auto .5rem"></div>
    <div class="skeleton" style="width:200px;height:16px;margin:0 auto"></div>`;

  try {
    const res  = await fetch(`analise_risco_engine.php?${params}`);
    const raw  = await res.text();

    let data;
    try {
      data = JSON.parse(raw);
    } catch (parseErr) {
      // Servidor retornou algo que não é JSON (erro fatal PHP, HTML, etc.)
      console.error('Resposta não-JSON do servidor:', raw);
      document.getElementById('scorePanel').innerHTML = `
        <div style="text-align:center;padding:1rem">
          <div style="font-size:2.5rem;margin-bottom:.75rem">⚠️</div>
          <div style="font-size:1rem;font-weight:700;color:#991b1b">Erro no servidor</div>
          <p style="font-size:.82rem;color:#64748b;margin:.5rem 0 0">
            A análise falhou ao processar. Veja o console (F12) para detalhes técnicos.
          </p>
        </div>`;
      toast('Erro no servidor ao calcular a análise. Veja o console.', 'error');
      return;
    }

    if (data.erro) { toast(data.erro, 'error'); return; }

    if (!data.elegivel) {
      document.getElementById('scorePanel').innerHTML = `
        <div style="text-align:center;padding:1rem">
          <div style="font-size:2.5rem;margin-bottom:.75rem">🚫</div>
          <div style="font-size:1rem;font-weight:700;color:#991b1b">Não Elegível</div>
          <p style="font-size:.85rem;color:#64748b;margin:.5rem 0 0">${data.motivo}</p>
        </div>`;
      return;
    }

    // Auto-preenche o campo com o valor sugerido (apólice ativa existente)
    if (data.valor_cobertura_usado && !valorCobertura) {
      document.getElementById('paramValorCobertura').value = data.valor_cobertura_usado;
    }

    ultimaAnalise = data;
    renderScore(data);
    renderFatores(data.fatores);
    renderAlertas(data.alertas, data.exclusoes);
    renderCliente(data.cliente);
    renderPremio(data);

  } catch(e) {
    toast('Falha de conexão ao calcular análise.', 'error');
    console.error(e);
  }
}

function renderScore(data) {
  const c = COR[data.decisao_cor] || COR.amber;
  document.getElementById('scorePanel').innerHTML = `
    <div class="score-circle" style="border-color:${c.border};color:${c.txt}">
      <div class="score-num" style="color:${c.txt}">${data.score}</div>
      <div class="score-sub" style="color:${c.txt}">${data.score_label}</div>
    </div>
    <div class="decisao-badge"
         style="background:${c.badgeBg};color:${c.badgeTxt}">${data.decisao}</div>
    <p class="decisao-desc">${data.decisao_desc}</p>
    <div style="font-size:.75rem;color:#94a3b8;margin-top:.75rem">
      Analisado em ${data.analisado_em}
    </div>`;
}

function renderFatores(fatores) {
  const el = document.getElementById('fatoresBody');
  el.innerHTML = Object.entries(fatores).map(([key, f]) => {
    const bom    = f.valor <= 1.1;
    const medio  = f.valor <= 1.5;
    const barCor = bom ? '#10b981' : medio ? '#d97706' : '#ef4444';
    const pct    = Math.min(100, ((f.valor - 1) / 2) * 100);

    return `
    <div class="fator-row">
      <div class="fator-dot" style="background:${barCor}"></div>
      <div>
        <div class="fator-label">${f.label}</div>
        <div class="fator-bar" style="margin-top:.3rem">
          <div class="fator-bar-fill" style="width:${pct}%;background:${barCor}"></div>
        </div>
      </div>
      <span class="fator-valor"
            style="background:${bom?'#d1fae5':medio?'#fef3c7':'#fee2e2'};
                   color:${bom?'#065f46':medio?'#92400e':'#991b1b'}">
        ×${f.valor.toFixed(2)}
      </span>
      <div class="fator-impacto">${f.impacto > 0 ? '−'+f.impacto+' pts' : '0 pts'}</div>
    </div>`;
  }).join('');

  document.getElementById('fatoresPanel').style.display = 'block';
}

function renderAlertas(alertas, exclusoes) {
  const el = document.getElementById('alertasBody');
  if (!alertas.length && !exclusoes.length) {
    document.getElementById('alertasPanel').style.display = 'none';
    return;
  }
  el.innerHTML =
    alertas.map(a  => `<div class="alerta-item warn">⚠️ ${a}</div>`).join('') +
    exclusoes.map(e => `<div class="alerta-item danger">🚫 ${e}</div>`).join('');
  document.getElementById('alertasPanel').style.display = 'block';
}

function renderCliente(cli) {
  document.getElementById('clienteInfo').innerHTML = `
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem">
      <div style="width:42px;height:42px;border-radius:50%;
                  background:linear-gradient(135deg,#1a3a5c,#1e5fa8);
                  color:#fff;font-weight:700;display:flex;align-items:center;
                  justify-content:center;font-size:1.1rem">
        ${cli.nome.charAt(0)}
      </div>
      <div>
        <div style="font-weight:700">${cli.nome}</div>
        <div style="font-size:.78rem;color:#64748b">${fmtCPF(cli.cpf)} · ${cli.idade} anos</div>
      </div>
    </div>`;
  document.getElementById('clientePanel').style.display = 'block';
}

function renderPremio(data) {
  document.getElementById('premioValor').textContent = fmtMoeda(data.premio_mensal) + '/mês';
  if (data.premio_limitado) {
    const av = document.getElementById('premioAviso');
    av.textContent = `⚠️ Prêmio limitado ao máximo de 20% da renda (${fmtMoeda(data.premio_maximo)}/mês — SUSEP).`;
    av.style.display = 'block';
  }
  document.getElementById('premioPanel').style.display = 'block';
}

async function salvarAnalise() {
  if (!ultimaAnalise) { toast('Execute a análise primeiro.', 'error'); return; }

  const btn = document.getElementById('btnSalvar');
  btn.disabled = true; btn.innerHTML = '<span class="spinner" style="border-top-color:#fff;border-color:rgba(255,255,255,.3)"></span> Salvando…';

  try {
    const res  = await fetch('analise_risco_engine.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        cliente_id:    CLIENTE_ID,
        produto:       PRODUTO,
        score:         ultimaAnalise.score,
        decisao:       ultimaAnalise.decisao,
        premio_mensal: ultimaAnalise.premio_mensal,
        imc:           parseFloat(document.getElementById('paramIMC').value) || 0,
      }),
    });
    const data = await res.json();
    if (data.status === 'sucesso') {
      toast('Análise salva no histórico do cliente!', 'success');
      btn.innerHTML = '✅ Salvo';
    } else {
      toast('Erro ao salvar: ' + (data.erro||''), 'error');
      btn.disabled = false; btn.innerHTML = '💾 Salvar Análise';
    }
  } catch(e) {
    toast('Falha de conexão.', 'error');
    btn.disabled = false; btn.innerHTML = '💾 Salvar Análise';
  }
}

// Roda automaticamente ao carregar
document.addEventListener('DOMContentLoaded', rodarAnalise);

// Spinner CSS inline para o botão
document.head.insertAdjacentHTML('beforeend',`<style>
.spinner{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.3);
border-top-color:#fff;border-radius:50%;animation:spin .5s linear infinite;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
</style>`);
</script>
</body>
</html>