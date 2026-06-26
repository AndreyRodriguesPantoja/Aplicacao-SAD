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
  <link rel="stylesheet" href="css/analise_risco.css" />
</head>
<body>

<header class="topbar">
  <a class="topbar-brand" href="painel_funcionario.php">
    <span class="shield"></span> <span>Super Seguro</span>
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
    <p>Normas: critérios SUSEP · Tábua BR-EMS 2021 · ISO</p>
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
          <div style="font-size:2rem"></div>
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
            <label class="form-label">IMC (kg/m²)</label>
            <input type="number" class="form-control" id="paramIMC" step="0.1"
                   placeholder="Ex: 24.5" min="10" max="60" />
          </div>
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
              'doenca_cardiaca' => ['❤️', 'Doença Cardíaca'],
              'cancer'          => ['❤️', 'Histórico de Câncer'],
              'fumante'         => ['🚬', 'Tabagismo'],
              'alcoolismo'      => ['🍺', 'Uso de Álcool'],
              'doenca_renal'    => ['❤️', 'Doença Renal'],
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
           Calcular Risco
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
  const imc       = document.getElementById('paramIMC').value;
  const profissao = document.getElementById('paramProfissao').value;
  const condicoes = [...document.querySelectorAll('input[name=condicao]:checked')]
                      .map(c => c.value).join(',');

  const params = new URLSearchParams({
    cliente_id: CLIENTE_ID,
    produto:    PRODUTO,
    t:          Date.now(),
  });
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
    const data = await res.json();

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

    ultimaAnalise = data;
    renderScore(data);
    renderFatores(data.fatores);
    renderAlertas(data.alertas, data.exclusoes);
    renderCliente(data.cliente);
    renderPremio(data);

  } catch(e) {
    toast('Erro ao calcular análise.', 'error');
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
