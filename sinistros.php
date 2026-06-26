<?php
session_start();
if (empty($_SESSION['usuario_id'])) {
    header('Location: login-screen.html?erro=sessao');
    exit;
}
$analistaNome = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Analista');
$perfil       = $_SESSION['perfil'] ?? 'analista';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sinistros — Super Seguro</title>
  <link rel="stylesheet" href="css/apolices.css" />
  <link rel="stylesheet" href="css/sinistro.css"/>
  <style>
    
  </style>
</head>
<body>

<header class="topbar">
  <a class="topbar-brand" href="painel_funcionario.php">
    <span>Super Seguro</span>
  </a>
  <nav class="topbar-nav">
    <a href="painel_funcionario.php">Painel</a>
    <a href="lista_clientes_analise.php">Análise de Risco</a>
    <a href="sinistros.php" class="active">Sinistros</a>
    <a href="apolices_analista.html">Apólices</a>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<div class="welcome-bar">
  <div style="max-width:1200px;margin:0 auto">
    <div style="font-size:.78rem;opacity:.7;margin-bottom:.4rem">👔 <?= $analistaNome ?></div>
    <h1>Gestão de Sinistros</h1>
    <p>Registre ocorrências, analise fraudes e tome decisões de indenização com base em critérios SUSEP/CNSP.</p>
  </div>
</div>

<div class="page-wrapper" style="max-width:1200px;margin:0 auto">

  <!-- KPIs -->
  <div class="kpi-strip cols-4" style="margin-top:-3.5rem;position:relative;z-index:10;margin-bottom:1.5rem">
    <div class="kpi blue">  <label>Total</label>       <strong id="kpiTotal">—</strong></div>
    <div class="kpi amber"> <label>Em Análise</label>  <strong id="kpiAnalise">—</strong></div>
    <div class="kpi red">   <label>Suspensos</label>   <strong id="kpiSuspenso">—</strong></div>
    <div class="kpi green"> <label>Total Pago</label>  <strong id="kpiPago" style="font-size:1.25rem">—</strong></div>
  </div>

  <!-- Filtros + botão -->
  <div class="card" style="margin-bottom:1.25rem">
    <div class="filter-bar">
      <div class="form-group">
        <label class="form-label">Buscar</label>
        <input type="text" class="form-control" id="filtroBusca"
               placeholder="Nome, CPF ou nº apólice…"
               onkeydown="if(event.key==='Enter')App.carregar()" />
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select class="form-select" id="filtroStatus">
          <option value="">Todos</option>
          <option value="aberto">Aberto</option>
          <option value="em_analise">Em Análise</option>
          <option value="suspenso">Suspenso</option>
          <option value="aprovado">Aprovado</option>
          <option value="recusado">Recusado</option>
          <option value="pago">Pago</option>
        </select>
      </div>
      <div class="form-group" style="flex:0">
        <label class="form-label">&nbsp;</label>
        <div style="display:flex;gap:.5rem">
          <button class="btn btn-primary" onclick="App.carregar()">Buscar</button>
          <button class="btn btn-outline" onclick="App.limpar()">Limpar</button>
        </div>
      </div>
      <div class="form-group" style="flex:0;margin-left:auto">
        <label class="form-label">&nbsp;</label>
        <button class="btn btn-dark" onclick="App.abrirModalNovo()">
          + Novo Sinistro
        </button>
      </div>
    </div>
  </div>

  <!-- Tabela -->
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nº Sinistro</th><th>Cliente</th><th>Apólice</th>
            <th>Tipo</th><th>Data</th><th>Valor Est.</th>
            <th>Score Fraude</th><th>Status</th><th>Ações</th>
          </tr>
        </thead>
        <tbody id="corpoTabela">
          <tr><td colspan="9">
            <div style="display:flex;flex-direction:column;gap:.6rem;padding:1rem">
              <div class="skeleton" style="height:32px"></div>
              <div class="skeleton" style="height:32px"></div>
              <div class="skeleton" style="height:32px"></div>
            </div>
          </td></tr>
        </tbody>
      </table>
    </div>
    <div style="padding:.75rem 1.25rem;border-top:1px solid var(--gray-200);
                display:flex;align-items:center;justify-content:space-between;
                font-size:.82rem;color:var(--gray-500)">
      <span id="totalReg">—</span>
      <button class="btn btn-outline btn-sm" onclick="App.exportarCSV()">⬇ Exportar CSV</button>
    </div>
  </div>

</div>

<!-- ══════════════ MODAL: NOVO SINISTRO ══════════════ -->
<div class="modal-backdrop" id="modalNovo">
  <div class="modal-box">
    <div class="modal-head">
      <h3>📋 Registrar Novo Sinistro</h3>
      <button class="modal-close" onclick="App.fecharModal('modalNovo')">✕</button>
    </div>
    <div class="modal-body">
      <form id="formSinistro">

        <!-- Passo 1: Cliente / Apólice -->
        <fieldset style="border:none;padding:0;margin-bottom:1.25rem">
          <legend style="font-size:.75rem;font-weight:700;text-transform:uppercase;
                         letter-spacing:.6px;color:var(--gray-500);margin-bottom:.75rem">
            1. Identificação
          </legend>
          <div class="fgrid c2">
            <div class="form-group span2">
              <label class="form-label">CPF do Segurado *</label>
              <div style="display:flex;gap:.5rem">
                <input type="text" class="form-control" id="sinCPF"
                       maxlength="14" placeholder="000.000.000-00" />
                <button type="button" class="btn btn-outline" onclick="App.buscarApolices()"
                        style="white-space:nowrap">Buscar</button>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Nome do Segurado</label>
              <input type="text" class="form-control" id="sinNome" readonly placeholder="Preenchido automaticamente" />
            </div>
            <div class="form-group">
              <label class="form-label">Apólice *</label>
              <select class="form-select" id="sinApolice">
                <option value="">Selecione após buscar CPF…</option>
              </select>
            </div>
          </div>
        </fieldset>

        <!-- Passo 2: Ocorrência -->
        <fieldset style="border:none;padding:0;margin-bottom:1.25rem">
          <legend style="font-size:.75rem;font-weight:700;text-transform:uppercase;
                         letter-spacing:.6px;color:var(--gray-500);margin-bottom:.75rem">
            2. Ocorrência
          </legend>
          <div class="fgrid c3">
            <div class="form-group">
              <label class="form-label">Tipo de Sinistro *</label>
              <select class="form-select" id="sinTipo">
                <option value="">Selecione…</option>
                <optgroup label="Automóvel">
                  <option value="colisao">Colisão</option>
                  <option value="furto_simples">Furto Simples</option>
                  <option value="roubo">Roubo</option>
                  <option value="roubo_carga">Roubo de Carga</option>
                  <option value="incendio_auto">Incêndio (Auto)</option>
                  <option value="alagamento">Alagamento</option>
                </optgroup>
                <optgroup label="Residencial">
                  <option value="incendio">Incêndio</option>
                  <option value="furto_simples">Furto Residencial</option>
                  <option value="danos_eletricos">Danos Elétricos</option>
                  <option value="vazamento">Vazamento/Alagamento</option>
                  <option value="vendaval">Vendaval</option>
                </optgroup>
                <optgroup label="Vida">
                  <option value="morte_natural">Morte Natural</option>
                  <option value="morte_acidental">Morte Acidental</option>
                  <option value="invalidez">Invalidez Permanente</option>
                </optgroup>
                <optgroup label="Saúde">
                  <option value="internacao_eletiva">Internação Eletiva</option>
                  <option value="internacao_emergencia">Internação Emergência</option>
                  <option value="cirurgia">Cirurgia</option>
                  <option value="exames">Exames de Alta Complexidade</option>
                </optgroup>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Data da Ocorrência *</label>
              <input type="date" class="form-control" id="sinData" />
            </div>
            <div class="form-group">
              <label class="form-label">Hora da Ocorrência</label>
              <input type="time" class="form-control" id="sinHora" value="12:00" />
            </div>
            <div class="form-group">
              <label class="form-label">Valor Estimado do Dano (R$) *</label>
              <input type="number" step="0.01" min="0" class="form-control"
                     id="sinValor" placeholder="Ex: 15000.00" />
            </div>
            <div class="form-group">
              <label class="form-label">Franquia (R$)</label>
              <input type="number" step="0.01" min="0" class="form-control"
                     id="sinFranquia" placeholder="Ex: 2000.00" value="0" />
            </div>
            <div class="form-group">
              <label class="form-label">Depreciação (%)</label>
              <input type="number" step="1" min="0" max="100" class="form-control"
                     id="sinDepreciacao" placeholder="0" value="0" />
            </div>
            <div class="form-group span2">
              <label class="form-label">Descrição da Ocorrência *</label>
              <textarea class="form-control" id="sinDescricao" rows="3"
                        placeholder="Descreva como o sinistro ocorreu…"></textarea>
            </div>
          </div>
        </fieldset>

        <!-- Passo 3: Documentação -->
        <fieldset style="border:none;padding:0">
          <legend style="font-size:.75rem;font-weight:700;text-transform:uppercase;
                         letter-spacing:.6px;color:var(--gray-500);margin-bottom:.75rem">
            3. Documentação Recebida
          </legend>
          <div class="docs-list" style="display:grid;grid-template-columns:1fr 1fr;gap:.25rem">
            <?php
            $docs = [
              'doc_bo'        => ' Boletim de Ocorrência',
              'doc_fotos'     => ' Fotos do Dano',
              'doc_nf'        => ' Nota Fiscal / Orçamento',
              'doc_laudo'     => ' Laudo Técnico',
              'doc_identidade'=> ' Documento de Identidade',
              'doc_apolice'   => ' Cópia da Apólice',
              'doc_medico'    => ' Laudo Médico (Saúde/Vida)',
              'doc_morte'     => ' Certidão de Óbito (Vida)',
            ];
            foreach ($docs as $id => $label):
            ?>
            <label>
              <input type="checkbox" name="doc" value="<?= $id ?>" checked />
              <?= $label ?>
            </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

      </form>
    </div>
    <div class="modal-foot">
      <button class="btn btn-outline" onclick="App.fecharModal('modalNovo')">Cancelar</button>
      <button class="btn btn-dark" id="btnRegistrar" onclick="App.registrar()">
        🔎 Registrar e Analisar
      </button>
    </div>
  </div>
</div>

<!-- ══════════════ MODAL: RESULTADO DA ANÁLISE ══════════════ -->
<div class="modal-backdrop" id="modalResultado">
  <div class="modal-box" style="max-width:620px">
    <div class="modal-head">
      <h3 id="resultTitulo">Resultado da Análise</h3>
      <button class="modal-close" onclick="App.fecharModal('modalResultado')">✕</button>
    </div>
    <div class="modal-body" id="resultBody"></div>
    <div class="modal-foot">
      <button class="btn btn-outline" onclick="App.fecharModal('modalResultado');App.carregar()">Fechar</button>
    </div>
  </div>
</div>

<!-- ══════════════ MODAL: DETALHE DO SINISTRO ══════════════ -->
<div class="modal-backdrop" id="modalDetalhe">
  <div class="modal-box" style="max-width:600px">
    <div class="modal-head">
      <h3 id="detTitulo">Detalhe do Sinistro</h3>
      <button class="modal-close" onclick="App.fecharModal('modalDetalhe')">✕</button>
    </div>
    <div class="modal-body" id="detBody"></div>
    <div class="modal-foot" id="detFoot"></div>
  </div>
</div>

<div id="toastArea"></div>

<script>
/* ============================================================
   sinistros.php — lógica completa de gestão de sinistros
   ============================================================ */

const toast = (msg, tipo='info') => {
  const el = document.createElement('div');
  el.className = `toast ${tipo}`; el.textContent = msg;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => el.remove(), 4500);
};

const fmt = {
  moeda: v  => 'R$ ' + Number(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2}),
  data:  v  => v ? new Date(v+'T00:00').toLocaleDateString('pt-BR') : '—',
  cpf:   v  => v ? v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,'$1.$2.$3-$4') : '—',
};

// Máscara CPF
document.getElementById('sinCPF').addEventListener('input', function() {
  let v = this.value.replace(/\D/g,'').slice(0,11);
  if (v.length>9)      v=v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,'$1.$2.$3-$4');
  else if (v.length>6) v=v.replace(/(\d{3})(\d{3})(\d{0,3})/,'$1.$2.$3');
  else if (v.length>3) v=v.replace(/(\d{3})(\d{0,3})/,'$1.$2');
  this.value = v;
});

const STATUS_CFG = {
  aberto:     {bg:'#dbeafe',cor:'#1e5fa8',txt:'Aberto'},
  em_analise: {bg:'#fef3c7',cor:'#92400e',txt:'Em Análise'},
  suspenso:   {bg:'#fee2e2',cor:'#991b1b',txt:'Suspenso'},
  aprovado:   {bg:'#d1fae5',cor:'#065f46',txt:'Aprovado'},
  recusado:   {bg:'#f1f5f9',cor:'#64748b',txt:'Recusado'},
  pago:       {bg:'#d1fae5',cor:'#065f46',txt:'Pago'},
};

const FRAUDE_COR = {
  'BAIXO': {bg:'#d1fae5',cor:'#065f46'},
  'MÉDIO': {bg:'#fef3c7',cor:'#92400e'},
  'ALTO':  {bg:'#fee2e2',cor:'#991b1b'},
};

let _dados = [];
let _sinistroAtivoId = null;

const App = {

  async carregar() {
    const busca  = document.getElementById('filtroBusca').value.trim();
    const status = document.getElementById('filtroStatus').value;
    const p = new URLSearchParams({ listar_sinistros:1, t:Date.now() });
    if (busca)  p.set('busca', busca);
    if (status) p.set('status', status);

    document.getElementById('corpoTabela').innerHTML =
      '<tr><td colspan="9"><div style="padding:1rem;display:flex;flex-direction:column;gap:.5rem">' +
      '<div class="skeleton" style="height:30px"></div>'.repeat(3) + '</div></td></tr>';

    try {
      const res  = await fetch(`sinistro_engine.php?${p}`);
      const data = await res.json();
      if (data.erro) throw new Error(data.erro);
      _dados = data.sinistros || [];
      this.renderKPIs(data.kpi || {});
      this.renderTabela(_dados);
    } catch(e) {
      document.getElementById('corpoTabela').innerHTML =
        `<tr><td colspan="9"><div class="empty-state"><span class="icon">❌</span><p>${e.message}</p></div></td></tr>`;
    }
  },

  renderKPIs(k) {
    document.getElementById('kpiTotal').textContent   = k.total    || 0;
    document.getElementById('kpiAnalise').textContent = k.em_analise||0;
    document.getElementById('kpiSuspenso').textContent= k.suspensos ||0;
    document.getElementById('kpiPago').textContent    = fmt.moeda(k.total_pago||0);
  },

  renderTabela(rows) {
    document.getElementById('totalReg').textContent = `${rows.length} registro(s)`;
    if (!rows.length) {
      document.getElementById('corpoTabela').innerHTML =
        '<tr><td colspan="9"><div class="empty-state"><span class="icon">🔎</span><p>Nenhum sinistro encontrado.</p></div></td></tr>';
      return;
    }

    document.getElementById('corpoTabela').innerHTML = rows.map(s => {
      const st = STATUS_CFG[s.status] || {bg:'#f1f5f9',cor:'#64748b',txt:s.status};
      const fc = FRAUDE_COR[s.nivel_fraude] || {bg:'#f1f5f9',cor:'#64748b'};
      return `
      <tr class="sin-row" onclick="App.verDetalhe(${s.id})">
        <td><code>${s.numero_sinistro}</code></td>
        <td><div style="font-weight:600;font-size:.88rem">${s.cliente}</div>
            <div style="font-size:.75rem;color:var(--gray-500)">${fmt.cpf(s.cpf)}</div></td>
        <td><code style="font-size:.78rem">${s.numero_apolice}</code></td>
        <td style="font-size:.83rem">${s.tipo_sinistro}</td>
        <td style="font-size:.83rem;white-space:nowrap">${fmt.data(s.data_ocorrencia)}</td>
        <td style="font-weight:600">${fmt.moeda(s.valor_estimado)}</td>
        <td>
          <span class="score-badge" style="background:${fc.bg};color:${fc.cor}">
            ${s.score_fraude} — ${s.nivel_fraude||'—'}
          </span>
        </td>
        <td>
          <span class="score-badge" style="background:${st.bg};color:${st.cor}">${st.txt}</span>
        </td>
        <td onclick="event.stopPropagation()">
          <div style="display:flex;gap:.4rem">
            <button class="btn btn-outline btn-sm" onclick="App.verDetalhe(${s.id})">Ver</button>
          </div>
        </td>
      </tr>`;
    }).join('');
  },

  limpar() {
    document.getElementById('filtroBusca').value = '';
    document.getElementById('filtroStatus').value = '';
    this.carregar();
  },

  // ── Buscar apólices por CPF ──────────────────────────────
  async buscarApolices() {
    const cpf = document.getElementById('sinCPF').value.replace(/\D/g,'');
    if (cpf.length !== 11) { toast('CPF inválido.','error'); return; }

    try {
      const res  = await fetch(`sinistro_engine.php?apolices_cliente=1&cpf=${cpf}`);
      const data = await res.json();

      if (data.erro) { toast(data.erro,'error'); return; }

      document.getElementById('sinNome').value = data.cliente?.nome || '';
      const sel = document.getElementById('sinApolice');
      sel.innerHTML = '<option value="">Selecione a apólice…</option>' +
        (data.apolices||[]).map(a =>
          `<option value="${a.id}" data-cobertura="${a.valor_cobertura}">
            ${a.numero_apolice} — ${a.tipo_seguro} (${fmt.moeda(a.valor_cobertura)})
          </option>`
        ).join('');
      toast(`${data.apolices.length} apólice(s) encontrada(s) para ${data.cliente?.nome}.`,'success');
    } catch(e) { toast('Erro ao buscar apólices.','error'); }
  },

  // ── Registrar sinistro ───────────────────────────────────
  async registrar() {
    const apoliceId = document.getElementById('sinApolice').value;
    const tipo      = document.getElementById('sinTipo').value;
    const data_oc   = document.getElementById('sinData').value;
    const hora      = document.getElementById('sinHora').value;
    const valor     = document.getElementById('sinValor').value;
    const desc      = document.getElementById('sinDescricao').value.trim();

    if (!apoliceId)      { toast('Selecione uma apólice.','error'); return; }
    if (!tipo)           { toast('Selecione o tipo de sinistro.','error'); return; }
    if (!data_oc)        { toast('Informe a data da ocorrência.','error'); return; }
    if (!valor || valor<=0) { toast('Informe o valor estimado do dano.','error'); return; }
    if (!desc)           { toast('Descreva a ocorrência.','error'); return; }

    // Docs não marcados = pendentes
    const totalDocs    = document.querySelectorAll('input[name=doc]').length;
    const docsEntregues= document.querySelectorAll('input[name=doc]:checked').length;
    const docsPendentes= totalDocs - docsEntregues;

    const btn = document.getElementById('btnRegistrar');
    btn.disabled = true; btn.innerHTML = '<span class="sp"></span> Analisando…';

    try {
      const res  = await fetch('sinistro_engine.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          apolice_id:      Number(apoliceId),
          tipo_sinistro:   tipo,
          data_ocorrencia: data_oc,
          hora_ocorrencia: hora,
          valor_estimado:  parseFloat(valor),
          franquia:        parseFloat(document.getElementById('sinFranquia').value)||0,
          depreciacao_pct: parseFloat(document.getElementById('sinDepreciacao').value)||0,
          descricao:       desc,
          docs_pendentes:  docsPendentes,
        }),
      });
      const data = await res.json();

      if (data.status === 'sucesso') {
        this.fecharModal('modalNovo');
        document.getElementById('formSinistro').reset();
        document.getElementById('sinNome').value = '';
        document.getElementById('sinApolice').innerHTML = '<option value="">Selecione após buscar CPF…</option>';
        this.mostrarResultado(data);
        this.carregar();
        // Notifica sobre atualização da apólice
        if (data.apolice_atualizada) {
          const st = data.apolice_atualizada.status;
          const msg = st === 'suspensa'
            ? `⚠️ Apólice suspensa automaticamente — score de fraude alto.`
            : `✅ Apólice mantida ativa — sinistro em análise.`;
          toast(msg, st === 'suspensa' ? 'error' : 'info');
        }
      } else {
        toast('Erro: ' + (data.erro||'Tente novamente.'),'error');
      }
    } catch(e) { toast('Falha de conexão.','error'); }
    finally { btn.disabled=false; btn.innerHTML='🔎 Registrar e Analisar'; }
  },

  mostrarResultado(data) {
    const af   = data.analise_fraude   || {};
    const ind  = data.indenizacao      || {};
    const fc   = FRAUDE_COR[af.nivel]  || {bg:'#f1f5f9',cor:'#64748b'};
    const barW = af.score || 0;
    const barC = barW>=60 ? '#ef4444' : barW>=30 ? '#d97706' : '#10b981';

    const fatoresHTML = Object.entries(af.fatores||{}).map(([k,f]) => {
      const nc = f.nivel==='alto' ? '#ef4444' : f.nivel==='medio' ? '#d97706' : '#10b981';
      return `<div class="fat-row">
        <div class="fat-dot" style="background:${nc}"></div>
        <div class="fat-label">${f.label}</div>
        <div class="fat-pts">${f.pts > 0 ? '+'+f.pts+' pts' : 'ok'}</div>
      </div>`;
    }).join('');

    const alertasHTML = (af.alertas||[]).map(a =>
      `<div class="alert-item warn">⚠️ ${a}</div>`
    ).join('') || '<div class="alert-item ok">✅ Nenhum alerta identificado.</div>';

    document.getElementById('resultTitulo').textContent =
      `Sinistro ${data.numero_sinistro} — Resultado da Análise`;

    document.getElementById('resultBody').innerHTML = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">

        <!-- Score fraude -->
        <div style="text-align:center;padding:1rem;background:var(--gray-50);border-radius:10px">
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.6px;
                      color:var(--gray-500);margin-bottom:.5rem">Score Antifraude</div>
          <div class="gauge-num" style="color:${barC}">${af.score}</div>
          <div class="gauge-bar">
            <div class="gauge-fill" style="width:${barW}%;background:${barC}"></div>
          </div>
          <span class="score-badge" style="background:${fc.bg};color:${fc.cor}">
            Risco ${af.nivel}
          </span>
        </div>

        <!-- Indenização -->
        <div class="inden-box">
          <div class="sub">Valor aprovado para indenização</div>
          <div class="val">${fmt.moeda(ind.valor_aprovado)}</div>
          <div style="margin-top:1rem">
            <div class="inden-row"><span>Valor solicitado</span><span>${fmt.moeda(ind.valor_solicitado)}</span></div>
            <div class="inden-row"><span>Franquia</span><span>− ${fmt.moeda(ind.franquia)}</span></div>
            <div class="inden-row"><span>Depreciação</span><span>− ${ind.depreciacao_pct}%</span></div>
            <div class="inden-row"><span>Teto de cobertura</span><span>${fmt.moeda(ind.cobertura_maxima)}</span></div>
          </div>
          ${ind.suspenso ? '<div style="margin-top:.75rem;background:rgba(255,255,255,.15);border-radius:6px;padding:.5rem .75rem;font-size:.8rem">⚠️ Pagamento suspenso — fraude sob investigação</div>' : ''}
        </div>
      </div>

      <!-- Fatores -->
      <div style="margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
                    color:var(--gray-500);margin-bottom:.6rem">Fatores Analisados</div>
        ${fatoresHTML}
      </div>

      <!-- Alertas -->
      <div style="margin-bottom:1rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
                    color:var(--gray-500);margin-bottom:.6rem">Alertas</div>
        ${alertasHTML}
      </div>

      <!-- Recomendação -->
      <div style="background:var(--blue-light);border-radius:8px;padding:.9rem 1rem;border-left:4px solid var(--blue-mid)">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                    color:var(--blue-mid);margin-bottom:.3rem">Recomendação do Motor</div>
        <p style="margin:0;font-size:.88rem;color:var(--blue-deep);font-weight:500">${af.recomendacao}</p>
      </div>

      <!-- Status da apólice atualizado -->
      ${data.apolice_atualizada ? `
      <div style="margin-top:1rem;border-radius:8px;padding:.85rem 1rem;
                  background:${data.apolice_atualizada.status==='suspensa'?'#fee2e2':'#d1fae5'};
                  border-left:4px solid ${data.apolice_atualizada.status==='suspensa'?'#ef4444':'#10b981'}">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                    color:${data.apolice_atualizada.status==='suspensa'?'#991b1b':'#065f46'};margin-bottom:.3rem">
          Status da Apólice Atualizado
        </div>
        <p style="margin:0;font-size:.85rem;font-weight:500;
                  color:${data.apolice_atualizada.status==='suspensa'?'#991b1b':'#065f46'}">
          ${data.apolice_atualizada.status==='suspensa'
            ? '🔒 Apólice suspensa automaticamente devido ao alto score de fraude. Requer revisão antes de reativação.'
            : '✅ Apólice mantida ativa. Sinistro registrado e em processo de análise.'}
        </p>
      </div>` : ''}`;
    this.abrirModal('modalResultado');
  },

  // ── Ver detalhe de um sinistro ───────────────────────────
  async verDetalhe(id) {
    _sinistroAtivoId = id;
    document.getElementById('detBody').innerHTML =
      '<div style="padding:1.5rem;display:flex;flex-direction:column;gap:.75rem">' +
      '<div class="skeleton" style="height:30px"></div>'.repeat(5) + '</div>';
    document.getElementById('detFoot').innerHTML = '';
    this.abrirModal('modalDetalhe');

    try {
      const res  = await fetch(`sinistro_engine.php?sinistro_id=${id}`);
      const data = await res.json();
      if (data.erro) throw new Error(data.erro);
      const s  = data.sinistro;
      const st = STATUS_CFG[s.status] || {bg:'#f1f5f9',cor:'#64748b',txt:s.status};
      const fc = FRAUDE_COR[s.nivel_fraude] || {bg:'#f1f5f9',cor:'#64748b'};
      const alertas = JSON.parse(s.alertas_fraude||'[]');
      const barW = s.score_fraude||0;
      const barC = barW>=60?'#ef4444':barW>=30?'#d97706':'#10b981';

      document.getElementById('detTitulo').textContent = s.numero_sinistro;

      document.getElementById('detBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
          <div class="info-item"><span style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500)">Cliente</span>
            <strong>${s.cliente_nome}</strong><div style="font-size:.78rem;color:var(--gray-500)">${fmt.cpf(s.cpf)}</div></div>
          <div class="info-item"><span style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500)">Apólice</span>
            <code style="font-weight:700">${s.numero_apolice}</code><div style="font-size:.78rem;color:var(--gray-500)">${s.tipo_seguro}</div></div>
          <div class="info-item"><span style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500)">Tipo de Sinistro</span>
            <strong>${s.tipo_sinistro}</strong></div>
          <div class="info-item"><span style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500)">Data / Hora</span>
            <strong>${fmt.data(s.data_ocorrencia)} às ${s.hora_ocorrencia||'—'}</strong></div>
          <div class="info-item"><span style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500)">Valor Estimado</span>
            <strong>${fmt.moeda(s.valor_estimado)}</strong></div>
          <div class="info-item"><span style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500)">Valor Aprovado</span>
            <strong style="color:var(--blue-deep)">${fmt.moeda(s.valor_aprovado)}</strong></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
          <div style="text-align:center;padding:.9rem;background:var(--gray-50);border-radius:10px">
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500)">Score Fraude</div>
            <div style="font-size:2rem;font-weight:800;color:${barC}">${barW}</div>
            <div class="gauge-bar"><div class="gauge-fill" style="width:${barW}%;background:${barC}"></div></div>
            <span class="score-badge" style="background:${fc.bg};color:${fc.cor}">${s.nivel_fraude}</span>
          </div>
          <div style="display:flex;flex-direction:column;justify-content:center;gap:.5rem">
            <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500)">Status</div>
            <span class="score-badge" style="background:${st.bg};color:${st.cor};font-size:.85rem;padding:.35em 1em">${st.txt}</span>
            <div style="font-size:.78rem;color:var(--gray-500)">Analista: ${s.analista_nome||'—'}</div>
          </div>
        </div>

        ${alertas.length ? `
        <div style="margin-bottom:1rem">
          <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500);margin-bottom:.5rem">Alertas Antifraude</div>
          ${alertas.map(a=>`<div class="alert-item warn">⚠️ ${a}</div>`).join('')}
        </div>` : ''}

        <div style="background:var(--blue-light);border-radius:8px;padding:.85rem 1rem;border-left:4px solid var(--blue-mid)">
          <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--blue-mid);margin-bottom:.3rem">Recomendação</div>
          <p style="margin:0;font-size:.85rem;color:var(--blue-deep)">${s.recomendacao||'—'}</p>
        </div>

        ${s.descricao ? `<div style="margin-top:1rem;background:var(--gray-50);border-radius:8px;padding:.85rem 1rem">
          <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500);margin-bottom:.3rem">Descrição</div>
          <p style="margin:0;font-size:.85rem;color:var(--gray-700)">${s.descricao}</p>
        </div>` : ''}

        <!-- Alterar status -->
        <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--gray-200)">
          <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-500);margin-bottom:.6rem">Alterar Status</div>
          <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.75rem;align-items:end">
            <div class="form-group" style="margin:0">
              <label class="form-label">Novo Status</label>
              <select class="form-select" id="detNovoStatus">
                ${Object.entries(STATUS_CFG).map(([v,c])=>`<option value="${v}" ${v===s.status?'selected':''}>${c.txt}</option>`).join('')}
              </select>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">Observação</label>
              <input type="text" class="form-control" id="detObs" placeholder="Motivo…" />
            </div>
            <button class="btn btn-primary" onclick="App.atualizarStatus(${s.id})">Salvar</button>
          </div>
        </div>`;

    } catch(e) {
      document.getElementById('detBody').innerHTML = `<p class="text-danger">${e.message}</p>`;
    }
  },

  async atualizarStatus(id) {
    const novoSt = document.getElementById('detNovoStatus').value;
    const obs    = document.getElementById('detObs').value.trim();
    try {
      const res  = await fetch('sinistro_engine.php', {
        method: 'PATCH',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id, status: novoSt, obs }),
      });
      const data = await res.json();
      if (data.status==='sucesso') {
        toast('Status atualizado com sucesso.','success');
        this.fecharModal('modalDetalhe');
        this.carregar();
      } else { toast('Erro: '+(data.erro||''),'error'); }
    } catch(e) { toast('Falha de conexão.','error'); }
  },

  exportarCSV() {
    if (!_dados.length) { toast('Nenhum dado para exportar.','error'); return; }
    const cab = ['Nº Sinistro','Cliente','CPF','Apólice','Tipo','Data','Valor Est.','Score','Nível','Status'];
    const lin = _dados.map(s => [
      s.numero_sinistro, s.cliente, s.cpf, s.numero_apolice,
      s.tipo_sinistro, s.data_ocorrencia, s.valor_estimado,
      s.score_fraude, s.nivel_fraude, s.status
    ].map(v=>`"${v||''}"`).join(','));
    const csv  = [cab.join(','),...lin].join('\n');
    const blob = new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href=url; a.download=`sinistros_${Date.now()}.csv`; a.click();
    URL.revokeObjectURL(url);
    toast('CSV exportado.','success');
  },

  abrirModal(id)  { document.getElementById(id).classList.add('open'); },
  fecharModal(id) { document.getElementById(id).classList.remove('open'); },
  abrirModalNovo(){ this.abrirModal('modalNovo'); },
};

// Fecha ao clicar no backdrop
document.querySelectorAll('.modal-backdrop').forEach(el =>
  el.addEventListener('click', e => { if(e.target===e.currentTarget) App.fecharModal(el.id); })
);

// CSS do spinner inline
document.head.insertAdjacentHTML('beforeend',`<style>
.sp{display:inline-block;width:13px;height:13px;border:2px solid rgba(255,255,255,.3);
border-top-color:#fff;border-radius:50%;animation:sp .5s linear infinite;vertical-align:middle}
@keyframes sp{to{transform:rotate(360deg)}}
</style>`);

document.addEventListener('DOMContentLoaded', () => App.carregar());
</script>
</body>
</html>
