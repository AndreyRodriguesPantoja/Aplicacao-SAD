let graficoClassificacao = null;
let graficoFaixaEtaria = null;

function classificarRiscoTexto(score) {
  const valor = Number(score);

  if (valor <= 2) return "Baixo";
  if (valor <= 5) return "Moderado";
  return "Alto";
}

function classificarRiscoBadge(score) {
  const valor = Number(score);

  if (valor <= 2) return '<span class="badge badge-baixo">Baixo</span>';
  if (valor <= 5) return '<span class="badge badge-moderado">Moderado</span>';
  return '<span class="badge badge-alto">Alto</span>';
}

function obterFiltros() {
  return {
    idade_min: document.getElementById("filtroIdadeMin").value.trim(),
    idade_max: document.getElementById("filtroIdadeMax").value.trim(),
    classificacao: document.getElementById("filtroClassificacao").value.trim()
  };
}

function limparFiltros() {
  document.getElementById("filtroIdadeMin").value = "";
  document.getElementById("filtroIdadeMax").value = "";
  document.getElementById("filtroClassificacao").value = "";
  carregarDashboard();
}

async function carregarDashboard() {
  const container = document.getElementById("resultado-bi");
  const filtros = obterFiltros();

  const params = new URLSearchParams();
  params.append("view", "dashboard");

  if (filtros.idade_min !== "") params.append("idade_min", filtros.idade_min);
  if (filtros.idade_max !== "") params.append("idade_max", filtros.idade_max);
  if (filtros.classificacao !== "") params.append("classificacao", filtros.classificacao);

  try {
    const res = await fetch(`get_dados.php?${params.toString()}&t=${new Date().getTime()}`);
    const dados = await res.json();

    if (dados.erro) {
      container.innerHTML = `<p class="text-danger">${dados.erro}</p>`;
      return;
    }

    atualizarKPIs(dados.summary);
    renderizarGraficoClassificacao(dados.classification);
    renderizarGraficoFaixaEtaria(dados.age_groups);
    renderizarTabela(dados.recent);
  } catch (error) {
    container.innerHTML = '<p class="text-danger">Erro ao carregar o dashboard.</p>';
    console.error("Erro dashboard:", error);
  }
}

function atualizarKPIs(summary) {
  document.getElementById("kpiTotal").textContent = summary.total || 0;
  document.getElementById("kpiBaixo").textContent = summary.baixo || 0;
  document.getElementById("kpiModerado").textContent = summary.moderado || 0;
  document.getElementById("kpiAlto").textContent = summary.alto || 0;
  document.getElementById("kpiMedia").textContent = Number(summary.media_risco || 0).toFixed(2);
}

function renderizarGraficoClassificacao(classification) {
  const ctx = document.getElementById("graficoClassificacao").getContext("2d");

  const labels = classification.map(item => item.label);
  const valores = classification.map(item => Number(item.total));

  if (graficoClassificacao) {
    graficoClassificacao.destroy();
  }

  graficoClassificacao = new Chart(ctx, {
    type: "pie",
    data: {
      labels: labels,
      datasets: [{
        data: valores,
        backgroundColor: ["#28a745", "#ffc107", "#dc3545"]
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: "bottom"
        }
      }
    }
  });
}

function renderizarGraficoFaixaEtaria(ageGroups) {
  const ctx = document.getElementById("graficoFaixaEtaria").getContext("2d");

  const labels = ageGroups.map(item => item.faixa);
  const valores = ageGroups.map(item => Number(item.total));

  if (graficoFaixaEtaria) {
    graficoFaixaEtaria.destroy();
  }

  graficoFaixaEtaria = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [{
        label: "Quantidade de Clientes",
        data: valores,
        backgroundColor: "#007bff"
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero:true,
          ticks: {
            precision: 0
          }
        }
      },
      plugins: {
        legend:{
          display: false
        }
      }
    }
  });
}

function renderizarTabela(registros) {
  const container = document.getElementById("resultado-bi");

  if (!registros || registros.length === 0) {
    container.innerHTML = '<p class="text-muted mt-3">Nenhum registro encontrado para os filtros aplicados.</p>';
    return;
  }

  let html = `
    <table class="table table-bordered table-sm mt-3">
      <thead class="thead-dark">
        <tr>
          <th>ID</th>
          <th>Idade</th>
          <th>IMC</th>
          <th>Score</th>
          <th>Classificação</th>
        </tr>
      </thead>
      <tbody>
  `;

  registros.forEach(item => {
    html += `
      <tr>
        <td>${item.id}</td>
        <td>${item.idade ? Number(item.idade).toFixed(0) : "-"}</td>
        <td>${Number(item.imc).toFixed(2)}</td>
        <td>${Number(item.risco).toFixed(2)}</td>
        <td>${classificarRiscoBadge(item.risco)}</td>
      </tr>
    `;
  });

  html += `</tbody></table>`;
  container.innerHTML = html;
}

function analisarRisco() {
  const idadeInput = document.getElementById("idade").value;
  const imcInput = document.getElementById("imc").value;

  if (!idadeInput || !imcInput) {
    alert("Por favor, preencha Idade e IMC para análise.");
    return;
  }

  const idade = parseFloat(idadeInput);
  const imc = parseFloat(imcInput);

  if (idade < 0 || imc <= 0) {
    alert("Informe valores válidos para idade e IMC.");
    return;
  }

  const divResultado = document.getElementById("resultado");

  let risco;
  let mensagem;
  let alertClass;

  if (idade > 60) {
    risco = 8;
    mensagem = "Risco Alto - Negar (Fator Idade)";
    alertClass = "alert-danger";
  } else {
    if (imc >= 30) {
      risco = 8;
      mensagem = "Risco Alto - Negar (Obesidade)";
      alertClass = "alert-danger";
    } else if (imc >= 25 && idade > 45) {
      risco = 5;
      mensagem = "Risco Moderado - Avaliação Manual";
      alertClass = "alert-warning";
    } else {
      risco = 2;
      mensagem = "Risco Baixo - Aprovar";
      alertClass = "alert-success";
    }
  }

  divResultado.innerHTML = `<div class="alert ${alertClass} font-weight-bold mb-0">${mensagem}</div>`;

  fetch("salvar.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      idade: idade,
      imc: imc,
      risco: risco
    })
  })
    .then(res => {
      if (!res.ok) throw new Error("Erro na resposta do servidor");
      return res.json();
    })
    .then(data => {
      if (data.status === "sucesso") {
        carregarDashboard();
        document.getElementById("formSAD").reset();
      } else {
        divResultado.innerHTML += `<div class="alert alert-danger mt-2">Erro ao salvar: ${data.mensagem}</div>`;
      }
    })
    .catch(error => {
      console.error("Erro ao salvar:", error);
      divResultado.innerHTML += `<div class="alert alert-danger mt-2">Erro inesperado ao salvar os dados.</div>`;
    });
}

async function limparHistorico() {
  if (confirm("Deseja realmente apagar todo o histórico do Data Warehouse?")) {
    try {
      const res = await fetch("limpar.php");
      const resultado = await res.json();

      if (resultado.status === "sucesso") {
        document.getElementById("resultado").innerHTML = "";
        carregarDashboard();
      } else {
        alert("Erro ao limpar histórico: " + resultado.mensagem);
      }
    } catch (e) {
      console.error("Erro ao limpar:", e);
      alert("Erro ao limpar histórico.");
    }
  }
}
document.addEventListener("DOMContentLoaded", carregarDashboard);
//window.onload = carregarDashboard;







