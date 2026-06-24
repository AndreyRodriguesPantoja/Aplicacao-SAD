<?php
// ============================================================
// login.php — Autenticação unificada para funcionários
// Perfis: analista (perfil_id=2) e gerente (perfil_id=3)
// ============================================================
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login-screen.html');
    exit;
}

$email = trim($_POST['usuario'] ?? '');
$senha = trim($_POST['senha']   ?? '');

// Captura qual aba foi clicada no HTML ('funcionario' para Analista ou 'gerente')
$tipo_perfil_form = $_POST['tipo_perfil'] ?? 'funcionario'; 

if ($email === '' || $senha === '') {
    // Retorna para a mesma aba mantendo o erro
    header('Location: login-screen.html?erro=campos_vazios&perfil=' . urlencode($tipo_perfil_form));
    exit;
}

try {
    // Busca o funcionário pelo e-mail
    $stmt = $pdo->prepare("
        SELECT f.id, f.nome, f.email, f.senha, f.ativo,
               p.id   AS perfil_id,
               p.nome AS perfil_nome
        FROM funcionarios f
        JOIN perfis p ON p.id = f.perfil_id
        WHERE f.email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $funcionario = $stmt->fetch();

    // 1. Verifica se o perfil clicado na tela corresponde ao cargo real no banco
    $perfil_valido = false;
    if ($funcionario) {
        // Se clicou na aba Analista, o perfil no banco TEM que ser 2
        if ($tipo_perfil_form === 'funcionario' && (int)$funcionario['perfil_id'] === 2) {
            $perfil_valido = true;
        }
        // Se clicou na aba Gerente, o perfil no banco TEM que ser 3
        elseif ($tipo_perfil_form === 'gerente' && (int)$funcionario['perfil_id'] === 3) {
            $perfil_valido = true;
        }
    }

    // 2. Valida a senha apenas se o perfil estiver correto e ativo
    $senhaOk = false;
    if ($perfil_valido && $funcionario['ativo']) {
        if (password_verify($senha, $funcionario['senha'])) {
            $senhaOk = true;                          // hash bcrypt
        } elseif ($funcionario['senha'] === $senha) {
            $senhaOk = true;                          // texto plano legado
        }
    }

    // Se a senha estiver errada OU se tentou logar na aba errada (ex: analista na aba gerente)
    if (!$senhaOk || !$perfil_valido) {
        header('Location: login-screen.html?erro=credenciais&perfil=' . urlencode($tipo_perfil_form));
        exit;
    }

    // Grava sessão
    session_regenerate_id(true);
    $_SESSION['usuario_id']    = $funcionario['id'];
    $_SESSION['usuario_nome']  = $funcionario['nome'];
    $_SESSION['usuario_email'] = $funcionario['email'];
    $_SESSION['perfil_id']     = $funcionario['perfil_id'];
    $_SESSION['perfil']        = $funcionario['perfil_nome'];   // 'analista' ou 'gerente'

    // Atualiza último acesso
    $pdo->prepare("UPDATE funcionarios SET ultimo_acesso = NOW() WHERE id = :id")
        ->execute([':id' => $funcionario['id']]);

    header('Location: painel_funcionario.php');
    exit;
} catch (PDOException $e) {
    error_log('Erro login: ' . $e->getMessage());
    header('Location: login-screen.html?erro=sistema&perfil=' . urlencode($tipo_perfil_form));
    exit;
}