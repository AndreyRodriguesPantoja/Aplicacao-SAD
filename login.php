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

$usuario = trim($_POST['usuario'] ?? '');
$senha   = trim($_POST['senha']   ?? '');

if ($usuario === '' || $senha === '') {
    header('Location: login-screen.html?erro=campos_vazios');
    exit;
}

try {
    // Busca o funcionário pelo campo 'usuario' e traz o nome do perfil junto
    $stmt = $pdo->prepare("
        SELECT f.id, f.nome, f.usuario, f.senha, f.ativo,
               p.id   AS perfil_id,
               p.nome AS perfil_nome
        FROM funcionarios f
        JOIN perfis p ON p.id = f.perfil_id
        WHERE f.usuario = :usuario
        LIMIT 1
    ");
    $stmt->execute([':usuario' => $usuario]);
    $funcionario = $stmt->fetch();

    // Valida existência, status ativo e senha (bcrypt ou legado texto plano)
    $senhaOk = false;
    if ($funcionario && $funcionario['ativo']) {
        if (password_verify($senha, $funcionario['senha'])) {
            $senhaOk = true;                          // hash bcrypt (correto)
        } elseif ($funcionario['senha'] === $senha) {
            $senhaOk = true;                          // texto plano legado
        }
    }

    if (!$senhaOk) {
        header('Location: login-screen.html?erro=credenciais');
        exit;
    }

    // Grava sessão
    session_regenerate_id(true);
    $_SESSION['usuario_id']    = $funcionario['id'];
    $_SESSION['usuario_nome']  = $funcionario['nome'];
    $_SESSION['usuario_login'] = $funcionario['usuario'];
    $_SESSION['perfil_id']     = $funcionario['perfil_id'];
    $_SESSION['perfil']        = $funcionario['perfil_nome'];   // 'analista' ou 'gerente'

    // Atualiza último acesso
    $pdo->prepare("UPDATE funcionarios SET ultimo_acesso = NOW() WHERE id = :id")
        ->execute([':id' => $funcionario['id']]);

    header('Location: painel_funcionario.php');
    exit;
} catch (PDOException $e) {
    error_log('Erro login: ' . $e->getMessage());
    header('Location: login-screen.html?erro=sistema');
    exit;
}
