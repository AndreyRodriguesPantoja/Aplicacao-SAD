<?php

$host = "localhost";
$usuario = "root";
$senha = "Teste1234@";
$banco = "sad_superseguro";

// Habilitar报告 de erros do mysqli para ajudar no desenvolvimento
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexao = mysqli_connect($host, $usuario, $senha, $banco);
    mysqli_set_charset($conexao, "utf8mb4"); // Garante acentuação correta
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Coleta e sanitização básica dos dados
$nome           = $_POST['nome'] ?? '';
$cpf            = $_POST['cpf'] ?? '';
$rg             = $_POST['rg'] ?? '';
$email          = $_POST['email'] ?? '';
$telefone       = $_POST['telefone'] ?? '';
$genero         = $_POST['genero'] ?? ''; // Certifique-se que o HTML envia 'masculino', 'feminino' ou 'outro'
$datanascimento = $_POST['datanascimento'] ?? '';
$pais           = !empty($_POST['pais']) ? $_POST['pais'] : 'Brasil';
$estado         = $_POST['estado'] ?? '';
$cidade         = $_POST['cidade'] ?? '';
$rua            = $_POST['rua'] ?? '';
$numeroresi     = $_POST['numeroresi'] ?? '';

// Tratamento de valores decimais (substitui vírgula por ponto, se houver)
$salario   = str_replace(',', '.', $_POST['salario'] ?? '0.00');
$valorapli = str_replace(',', '.', $_POST['valorapli'] ?? '0.00');

// === CORREÇÃO DOS CAMPOS OBRIGATÓRIOS ===
// 1. Gera o login do usuário baseado no CPF (apenas números)
$usuario_login = preg_replace('/[^0-9]/', '', $cpf); 

// 2. Cria uma senha segura (Bcrypt). Aqui usei uma padrão, mas idealmente vem do formulário $_POST['senha']
$senha_pura = "cliente123"; 
$senha_hash = password_hash($senha_pura, PASSWORD_BCRYPT);

// === PREPARED STATEMENT (Proteção contra SQL Injection) ===
$sql = "INSERT INTO usuarios (
            usuario, senha, nome, cpf, rg, email, telefone, 
            genero, datanascimento, pais, estado, cidade, rua, 
            numeroresi, salario, valorapli
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

try {
    $stmt = mysqli_prepare($conexao, $sql);
    
    // "ssssssssssssssdd" indica os tipos: s = string, d = double/decimal
    mysqli_stmt_bind_param($stmt, "ssssssssssssssdd", 
        $usuario_login, $senha_hash, $nome, $cpf, $rg, $email, $telefone,
        $genero, $datanascimento, $pais, $estado, $cidade, $rua,
        $numeroresi, $salario, $valorapli
    );

    if (mysqli_stmt_execute($stmt)) {
        echo "Cadastro realizado com sucesso! Seu usuário é o seu CPF (apenas números).";
    } else {
        echo "Erro ao cadastrar!";
    }
    
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    // Se o CPF ou Email já existirem, o banco vai disparar erro devido ao 'UNIQUE'
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "Erro: Este CPF ou E-mail já está cadastrado no sistema.";
    } else {
        echo "Erro no banco de dados: " . $e->getMessage();
    }
}

mysqli_close($conexao);
?>