<?php
// ============================================================
// cadastro_cliente.php — Tela isolada de cadastro de clientes
// Acesso: analistas e gerentes
// ============================================================
session_start();

// Protege a rota: apenas funcionários autenticados
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['perfil'])) {
    header('Location: login-screen.html');
    exit;
}

require_once 'conexao.php';

$perfil = $_SESSION['perfil'] ?? 'analista';
$isGerente = ($perfil === 'gerente');

$status_cadastro = '';
$mensagem_cadastro = '';

// FUNÇÃO ADICIONADA: Converte "R$ 1.250,50" em "1250.50" antes de salvar no banco
function limparMoeda($valor) {
    $valor = str_replace(['R$', ' ', '.'], '', $valor); 
    $valor = str_replace(',', '.', $valor); 
    return floatval($valor);
}

// Processamento do Banco de Dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_cliente'])) {
    $nome_cli       = trim($_POST['nome'] ?? '');
    $cpf_cli        = trim($_POST['cpf'] ?? '');
    $rg_cli         = trim($_POST['rg'] ?? '');
    $email_cli      = trim($_POST['email'] ?? '');
    $telefone_cli   = trim($_POST['telefone'] ?? '');
    $genero_cli     = $_POST['genero'] ?? '';
    $nascimento_cli = $_POST['datanascimento'] ?? '';
    $pais_cli       = trim($_POST['pais'] ?? '');
    $estado_cli     = trim($_POST['estado'] ?? '');
    $cidade_cli     = trim($_POST['cidade'] ?? '');
    $rua_cli        = trim($_POST['rua'] ?? '');
    $numero_cli     = trim($_POST['numeroresi'] ?? '');
    
    // PARTE MODIFICADA: Limpa a máscara do dinheiro antes de passar para o floatval
    $salario_cli    = limparMoeda($_POST['salario'] ?? '0');
    $valorapli_cli  = limparMoeda($_POST['valorapli'] ?? '0');
    
    $senha_cli      = $_POST['senha_login'] ?? '';

    // O e-mail do cliente passa a ser o identificador de usuário (login) no banco de dados
    $usuario_cli    = $email_cli; 

    if (empty($nome_cli) || empty($cpf_cli) || empty($email_cli) || empty($senha_cli) || empty($rg_cli)) {
        $status_cadastro = 'error';
        $mensagem_cadastro = 'Por favor, preencha todos os campos obrigatórios (*).';
    } else {
        try {
            $senha_hash = password_hash($senha_cli, PASSWORD_BCRYPT);

            $sql = "INSERT INTO usuarios (perfil_id, usuario, senha, nome, cpf, rg, email, telefone, genero, datanascimento, pais, estado, cidade, rua, numeroresi, salario, valorapli) 
                    VALUES (1, :usuario, :senha, :nome, :cpf, :rg, :email, :telefone, :genero, :datanascimento, :pais, :estado, :cidade, :rua, :numeroresi, :salario, :valorapli)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':usuario'        => $usuario_cli,
                ':senha'          => $senha_hash,
                ':nome'           => $nome_cli,
                ':cpf'            => $cpf_cli,
                ':rg'             => $rg_cli,
                ':email'          => $email_cli,
                ':telefone'       => $telefone_cli,
                ':genero'         => $genero_cli,
                ':datanascimento' => !empty($nascimento_cli) ? $nascimento_cli : null,
                ':pais'           => $pais_cli,
                ':estado'         => $estado_cli,
                ':cidade'         => $cidade_cli,
                ':rua'            => $rua_cli,
                ':numeroresi'     => $numero_cli,
                ':salario'        => $salario_cli,
                ':valorapli'      => $valorapli_cli
            ]);

            $status_cadastro = 'success';
            $mensagem_cadastro = 'Cliente cadastrado com sucesso!';
        } catch (PDOException $e) {
            error_log('Erro ao cadastrar cliente: ' . $e->getMessage());
            $status_cadastro = 'error';
            $mensagem_cadastro = 'Erro ao salvar cliente. Verifique se o CPF ou E-mail já existem.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cadastrar Cliente — Super Seguro</title>
  <link rel="stylesheet" href="css/apolices.css" />
  <style>
    body { background: #f1f5f9; }

    .card-cadastro-container {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 4px 20px rgba(0,0,0,.08);
      padding: 2rem;
      margin: 2rem auto;
      border: 1px solid #e2e8f0;
    }
    .cadastro-subtitulo {
      font-size: 0.95rem;
      font-weight: 700;
      color: #1e3a5c;
      margin: 1.5rem 0 0.75rem 0;
      border-bottom: 2px solid #f1f5f9;
      padding-bottom: 0.4rem;
    }
    .cadastro-subtitulo:first-of-type { margin-top: 0; }
    
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem;
    }
    .form-group { display: flex; flex-direction: column; gap: 0.35rem; }
    .form-group label { font-size: 0.8rem; font-weight: 600; color: #475569; }
    
    .form-control {
      width: 100%;
      padding: 0.65rem 0.75rem;
      background-color: #f8fafc;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 0.85rem;
      color: #334155;
      outline: none;
      transition: all 0.2s ease;
    }
    .form-control:focus {
      border-color: #1e5fa8;
      background-color: #fff;
      box-shadow: 0 0 0 3px rgba(30, 95, 168, 0.15);
    }
    .radio-group { display: flex; align-items: center; gap: 1.5rem; padding: 0.5rem 0; }
    .radio-option { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; cursor: pointer; }
    
    .btn-submit-cadastro {
      background: #1e5fa8; color: #fff; font-weight: 600; font-size: 0.9rem;
      padding: 0.75rem 1.75rem; border: none; border-radius: 8px; cursor: pointer;
      transition: background 0.2s; margin-top: 1.5rem;
    }
    .btn-submit-cadastro:hover { background: #1a3a5c; }
    .full-width-field { grid-column: 1 / -1; }

    .btn-voltar {
      display: inline-flex; align-items: center; gap: 0.5rem;
      text-decoration: none; color: #475569; font-size: 0.85rem; font-weight: 600;
      background: #fff; border: 1px solid #cbd5e1; padding: 0.5rem 1rem; border-radius: 8px;
      transition: all 0.2s; margin-bottom: 1rem;
    }
    .btn-voltar:hover { background: #f8fafc; color: #1e3a5c; }

    @media (max-width: 600px) {
      .page-wrapper { padding: 1rem; }
      .form-grid { grid-template-columns: 1fr; }
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
    <a href="painel_funcionario.php">Painel</a>
    <a href="apolices_analista.html">Apólices</a>
    <?php if ($isGerente): ?>
    <a href="index.html">Dashboard</a>
    <?php endif; ?>
    <a href="logout.php">Sair</a>
  </nav>
</header>

<div class="page-wrapper" style="max-width:1000px; margin: 2.5rem auto; padding: 0 1rem;">
  
  <a href="painel_funcionario.php" class="btn-voltar">← Voltar para o Painel</a>
  
  <div class="card-cadastro-container">
    <form action="cadastro_cliente.php" method="POST">
      
      <div class="cadastro-subtitulo">Informações Pessoais</div>
      <div class="form-grid">
        <div class="form-group full-width-field">
          <label for="nome">Nome Completo *</label>
          <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome completo do cliente" required>
        </div>
        <div class="form-group">
          <label for="cpf">CPF *</label>
          <input type="text" name="cpf" maxlength="11" pattern="[0-9]{11}" id="cpf" class="form-control" placeholder="Apenas números (11 dígitos)" required>
        </div>
        <div class="form-group">
          <label for="rg">RG *</label>
          <input type="text" name="rg" maxlength="7" pattern="[0-9]{7}" id="rg" class="form-control" placeholder="Apenas 7 dígitos numéricos" required>
        </div>
        <div class="form-group">
          <label for="datanascimento">Data de Nascimento *</label>
          <input type="date" name="datanascimento" id="datanascimento" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Sexo *</label>
          <div class="radio-group">
            <label class="radio-option" for="feminino">
              <input type="radio" id="feminino" name="genero" value="feminino" required> Feminino
            </label>
            <label class="radio-option" for="masculino">
              <input type="radio" id="masculino" name="genero" value="masculino" required> Masculino
            </label>
          </div>
        </div>
      </div>

      <div class="cadastro-subtitulo">Contato & Autenticação do Cliente</div>
      <div class="form-grid">
        <div class="form-group">
          <label for="email">E-mail (Será o Usuário de Login) *</label>
          <input type="email" name="email" id="email" class="form-control" placeholder="exemplo@email.com" required>
        </div>
        <div class="form-group">
          <label for="telefone">Telefone *</label>
          <input type="tel" name="telefone" id="telefone" class="form-control" placeholder="(91) 99001-0001" maxlength="15" oninput="mascaraTelefone(this)" required>
        </div>
        <div class="form-group">
          <label for="senha_login">Senha de Acesso do Cliente *</label>
          <input type="password" name="senha_login" id="senha_login" class="form-control" placeholder="Defina uma senha provisória" required>
        </div>
      </div>

      <div class="cadastro-subtitulo">Endereço Residencial</div>
      <div class="form-grid">
        <div class="form-group">
          <label for="pais">País *</label>
          <input type="text" name="pais" id="pais" class="form-control" placeholder="País" required>
        </div>
        <div class="form-group">
          <label for="estado">Estado *</label>
          <input type="text" name="estado" id="estado" class="form-control" placeholder="Estado" required>
        </div>
        <div class="form-group">
          <label for="cidade">Cidade *</label>
          <input type="text" name="cidade" id="cidade" class="form-control" placeholder="Cidade" required>
        </div>
        <div class="form-group">
          <label for="rua">Rua/Logradouro *</label>
          <input type="text" name="rua" id="rua" class="form-control" placeholder="Rua, Avenida, etc." required>
        </div>
        <div class="form-group">
          <label for="numeroresi">Número *</label>
          <input type="text" name="numeroresi" id="numeroresi" class="form-control" placeholder="Número" required>
        </div>
      </div>

      <div class="cadastro-subtitulo">Situação Financeira (Análise de Risco)</div>
      <div class="form-grid">
        <div class="form-group">
          <label for="salario">Salário Mensal *</label>
          <input type="text" name="salario" id="salario" class="form-control" placeholder="R$ 0,00" oninput="mascaraMoeda(this)" required>
        </div>
        <div class="form-group">
          <label for="valorapli">Valor a ser Aplicado *</label>
          <input type="text" name="valorapli" id="valorapli" class="form-control" placeholder="R$ 0,00" oninput="mascaraMoeda(this)" required>
        </div>
      </div>

      <div style="text-align: right;">
        <button type="submit" name="cadastrar_cliente" class="btn-submit-cadastro">
          💾 Salvar e Registrar Cliente
        </button>
      </div>
    </form>
  </div>
</div>

<div id="toastArea"></div>

<script>
function mascaraTelefone(htmlInp) {
  let res = htmlInp.value.replace(/\D/g, ""); 
  if (res.length > 11) {
    res = res.slice(0, 11);
  }
  res = res.replace(/^(\d{2})(\d)/g, "($1) $2");
  res = res.replace(/(\d{5})(\d)/, "$1-$2");
  htmlInp.value = res;
}

// ADICIONADO: Função JavaScript que formata dinamicamente em formato R$ 0,00
function mascaraMoeda(htmlInp) {
  let valor = htmlInp.value.replace(/\D/g, ""); 
  valor = (valor / 100).toFixed(2) + '';
  valor = valor.replace(".", ",");
  valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
  htmlInp.value = valor ? "R$ " + valor : "";
}

const toast = (msg, tipo='info') => {
  const el = document.createElement('div');
  el.className = `toast ${tipo}`;
  el.textContent = msg;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => el.remove(), 3500);
};

<?php if (!empty($status_cadastro)): ?>
  toast(<?= json_encode($mensagem_cadastro) ?>, <?= json_encode($status_cadastro) ?>);
<?php endif; ?>
</script>

</body>
</html>