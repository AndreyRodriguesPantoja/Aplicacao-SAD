<?php

session_start();

if(!isset($_SESSION['id_funcionarios'])){
    header("Location: login-screen.html");
    exit;
}

?>
<h2>
Bem-vindo,
<?php echo $_SESSION['nome_funcionarios']; ?>
</h2>

<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SAD - Super Seguro</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
        integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="css/estilo.css" />

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

</head>

<!--script para calular o IMC-->
<script>
    function calcular() {
        let peso = document.getElementById("peso").value;
        let altura = document.getElementById("altura").value;
        if (peso && altura) {
            let imc = peso / (altura * altura);
            document.getElementById("imc").value = imc.toFixed(2);
        } else {
            alert("Preencha peso e altura!");
        }
    }
</script>

<body>
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                SAD | <span class="text-warning">Super Seguro - Seguradora</span>
            </a>
        </div>
    </nav>
    <div class="container">
        <div class="row">
            <!-- Coluna esquerda -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        Simulador de Risco
                    </div>

                    <div class="card-body">
                        <form id="formSAD">
                            <div class="form-group">
                                <label for="idade">Idade (anos)</label>
                                <input type="number" step="1" min="0" class="form-control" id="idade"
                                    placeholder="Ex: 88" />
                            </div>

                            <div class="form-group">
                                <label for="imc">Qual seu peso (kg)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="peso"
                                    placeholder="Ex: 75.2" />
                            </div>

                            <div class="form-group">
                                <label for="imc">Qual sua altura em metros e centímetro</label>
                                <input type="number" step="0.01" class="form-control" id="altura"
                                    placeholder="Ex: 1.75" />
                            </div>
                            <div class="mb-3">
                                <label>IMC (kg/m²)</label>
                                <input type="text" class="form-control" id="imc" readonly />
                            </div>

                            <button type="button" onclick="calcular()" class="btn btn-primary w-100" id="butao1">
                                Calcular IMC
                            </button>
                            <button type="button" onclick="analisarRisco()" class="btn btn-primary w-100" id="butao2">
                                Verificar Emprestimo
                            </button>
                        </form>

                        <div id="resultado" class="mt-3"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-dark text-white">Filtros Analíticos</div>

                    <div class="card-body">

                        <div class="form-group">
                            <label for="filtroIdadeMin">Idade mínima</label>
                            <input type="number" class="form-control" id="filtroIdadeMin" placeholder="Ex: 18" />
                        </div>

                        <div class="form-group">
                            <label for="filtroIdadeMax">Idade máxima</label>
                            <input type="number" class="form-control" id="filtroIdadeMax" placeholder="Ex: 65" />
                        </div>

                        <div class="form-group">
                            <label for="filtroClassificacao">Classificação</label>
                            <select id="filtroClassificacao" class="form-control">
                                <option value="">Todas</option>
                                <option value="baixo">Baixo</option>
                                <option value="moderado">Moderado</option>
                                <option value="alto">Alto</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-success btn-block" onclick="carregarDashboard()">
                            Aplicar Filtros
                        </button>

                        <button type="button" class="btn btn-secondary btn-block mt-2" onclick="limparFiltros()">
                            Limpar Filtros
                        </button>

                    </div>
                </div>

            </div>
            <!-- Coluna direita -->
            <div class="col-lg-8">

                <div class="card">
                    <div class="card-header bg-dark text-white">
                        Painel de Apoio à Decisão
                    </div>

                    <div class="card-body">

                        <!-- KPIs -->
                        <div class="row text-center mb-4">

                            <div class="col-md-4 mb-3">
                                <div class="kpi-card">
                                    <h6>Total de Análises</h6>
                                    <h3 id="kpiTotal">0</h3>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="kpi-card kpi-success">
                                    <h6>Risco Baixo</h6>
                                    <h3 id="kpiBaixo">0</h3>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="kpi-card kpi-warning">
                                    <h6>Risco Moderado</h6>
                                    <h3 id="kpiModerado">0</h3>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="kpi-card kpi-danger">
                                    <h6>Risco Alto</h6>
                                    <h3 id="kpiAlto">0</h3>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="kpi-card">
                                    <h6>Média Geral de Risco</h6>
                                    <h3 id="kpiMedia">0.00</h3>
                                </div>
                            </div>
                        </div>

                        <!-- Gráficos -->
                        <div class="row">

                            <div class="col-md-6 mb-4">
                                <h6 class="text-center">Distribuição por Classificação</h6>
                                <canvas id="graficoClassificacao"></canvas>
                            </div>

                            <div class="col-md-6 mb-4">
                                <h6 class="text-center">Clientes por Faixa Etária</h6>
                                <canvas id="graficoFaixaEtaria"></canvas>
                            </div>

                        </div>

                        <!-- Tabela -->
                        <h6 class="mt-3">Últimos Registros</h6>

                        <div id="resultado-bi" class="table-responsive">
                            <div class="text-center p-4">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2">Carregando dashboard...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <h1 class="text-center mb-4">Visualizações para Apresentação - SAD Super Seguro</h1>
        <p class="text-center text-muted">Utilize prints destas visualizações nos seus slides</p>

        <!-- IMAGEM 1: Árvore de Decisão Ilustrada -->
        <div class="card" id="arvore-decisao">
            <div class="card-header">
                Figura 1: Representação da Árvore de Decisão - Classificação de Risco
            </div>
            <div class="card-body text-center">
                <div class="row justify-content-center">
                    <div class="col-12 mb-3">
                        <div class="tree-node d-inline-block px-4 py-2">
                            <strong>Idade > 60?</strong>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="tree-connector">⬇️ Sim</div>
                        <div class="tree-node mt-2 mb-3">
                            <strong>IMC > 30?</strong>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="tree-connector">⬇️ Sim</div>
                                <div class="tree-node risk-rejected p-2">
                                    <strong>ALTO RISCO</strong><br>
                                    <small>Recusar</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="tree-connector">⬇️ Não</div>
                                <div class="tree-node risk-high p-2">
                                    <strong>MÉDIO RISCO</strong><br>
                                    <small>Análise Manual</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="tree-connector">⬇️ Não</div>
                        <div class="tree-node mt-2 mb-3">
                            <strong>IMC > 25?</strong>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="tree-connector">⬇️ Sim</div>
                                <div class="tree-node risk-medium p-2">
                                    <strong>MÉDIO RISCO</strong><br>
                                    <small>Agravamento</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="tree-connector">⬇️ Não</div>
                                <div class="tree-node risk-low p-2">
                                    <strong>BAIXO RISCO</strong><br>
                                    <small>Aprovação</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--<p class="text-muted mt-3"><small>Fonte: Elaborado pelo autor com base nos critérios do SAD Super Seguro</small></p>
            </div>-->
            </div>

            <!-- IMAGEM 2: Matriz de Decisão (Pesos e Critérios) -->
            <div class="card" id="matriz-decisao">
                <div class="card-header">
                    Figura 2: Matriz de Decisão com Pesos - Fórmula da Média Ponderada
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Critério</th>
                                        <th>Peso</th>
                                        <th>Descrição (1 a 10)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Histórico de Saúde</td>
                                        <td><span class="badge bg-primary">4</span></td>
                                        <td>1 (Saudável) a 10 (Crítico)</td>
                                    </tr>
                                    <tr>
                                        <td>Hábitos de Vida</td>
                                        <td><span class="badge bg-primary">3</span></td>
                                        <td>1 (Ativo) a 10 (Sedentário)</td>
                                    </tr>
                                    <tr>
                                        <td>Risco Ocupacional</td>
                                        <td><span class="badge bg-primary">2</span></td>
                                        <td>1 (Baixo) a 10 (Alto risco)</td>
                                    </tr>
                                    <tr>
                                        <td>Saúde Psicossocial</td>
                                        <td><span class="badge bg-primary">1</span></td>
                                        <td>1 (Estável) a 10 (Instável)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="p-3 bg-light rounded">
                                <h5>Fórmula Aplicada:</h5>
                                <p class="h4 text-primary">Média = (S×4 + H×3 + O×2 + P×1) / 10</p>
                                <hr>
                                <h6>Classificação Final:</h6>
                                <ul class="list-unstyled">
                                    <li><span class="badge bg-success">1.0 - 3.0</span> Baixo Risco - Aprovação</li>
                                    <li><span class="badge bg-warning text-dark">3.1 - 6.0</span> Médio Risco -
                                        Agravamento</li>
                                    <li><span class="badge bg-danger">6.1 - 8.5</span> Alto Risco - Análise Manual</li>
                                    <li><span class="badge bg-dark">8.6 - 10.0</span> Recusado</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IMAGEM 3: Dashboard com Gráficos de Resultados -->
            <div class="card" id="dashboard-resultados">
                <div class="card-header">
                    Figura 3: Dashboard Analítico - Distribuição de Riscos
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <canvas id="chartPizza" width="400" height="300"></canvas>
                        </div>
                        <div class="col-md-8">
                            <canvas id="chartBarras" width="600" height="300"></canvas>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Baixo Risco</h5>
                                    <h2>45</h2>
                                    <p>propostas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning mb-3">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Médio Risco</h5>
                                    <h2>28</h2>
                                    <p>propostas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-danger mb-3">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Alto Risco</h5>
                                    <h2>15</h2>
                                    <p>propostas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-secondary mb-3">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Recusado</h5>
                                    <h2>12</h2>
                                    <p>propostas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IMAGEM 4: Tabela de Decisão e Exemplos -->
            <div class="card" id="tabela-decisao">
                <div class="card-header">
                    Figura 4: Exemplos de Classificação - Lógica da Árvore de Decisão
                </div>
                <div class="card-body">
                    <table class="table table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Cliente</th>
                                <th>Idade</th>
                                <th>IMC</th>
                                <th>Tabagismo</th>
                                <th>Profissão</th>
                                <th>Classificação</th>
                                <th>Decisão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>João Silva</td>
                                <td>35</td>
                                <td>22.5</td>
                                <td>Não</td>
                                <td>Admin</td>
                                <td><span class="badge bg-success">Baixo</span></td>
                                <td>Aprovar</td>
                            </tr>
                            <tr>
                                <td>Maria Santos</td>
                                <td>52</td>
                                <td>28.5</td>
                                <td>Não</td>
                                <td>Professor</td>
                                <td><span class="badge bg-warning text-dark">Médio</span></td>
                                <td>Agravamento</td>
                            </tr>
                            <tr>
                                <td>Carlos Lima</td>
                                <td>63</td>
                                <td>32.0</td>
                                <td>Sim</td>
                                <td>Construção</td>
                                <td><span class="badge bg-danger">Alto</span></td>
                                <td>Análise Manual</td>
                            </tr>
                            <tr>
                                <td>Ana Oliveira</td>
                                <td>58</td>
                                <td>35.0</td>
                                <td>Sim</td>
                                <td>Mineiro</td>
                                <td><span class="badge bg-dark">Recusado</span></td>
                                <td>Negar</td>
                            </tr>
                        </tbody>
                    </table>
                    <!--<p class="text-muted text-center mt-2">Baseado em 100 propostas simuladas com precisão de 89%</p>-->
                </div>
            </div>
        </div>

        <script>
            // Gráfico de Pizza
            const ctxPizza = document.getElementById('chartPizza').getContext('2d');
            new Chart(ctxPizza, {
                type: 'pie',
                data: {
                    labels: ['Baixo Risco', 'Médio Risco', 'Alto Risco', 'Recusado'],
                    datasets: [{
                        data: [45, 28, 15, 12],
                        backgroundColor: ['#198754', '#ffc107', '#dc3545', '#6c757d'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribuição das Classificações'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Gráfico de Barras por Faixa Etária
            const ctxBarras = document.getElementById('chartBarras').getContext('2d');
            new Chart(ctxBarras, {
                type: 'bar',
                data: {
                    labels: ['18-30', '31-40', '41-50', '51-60', '60+'],
                    datasets: [
                        {
                            label: 'Baixo Risco',
                            data: [18, 12, 8, 5, 2],
                            backgroundColor: '#198754'
                        },
                        {
                            label: 'Médio Risco',
                            data: [5, 8, 7, 5, 3],
                            backgroundColor: '#ffc107'
                        },
                        {
                            label: 'Alto Risco',
                            data: [1, 2, 4, 4, 4],
                            backgroundColor: '#dc3545'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribuição de Risco por Faixa Etária'
                        }
                    },
                    scales: {
                        x: { stacked: false },
                        y: { beginAtZero: true }
                    }
                }
            });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

        <script src="js/scripts.js"></script>
</body>

</html>