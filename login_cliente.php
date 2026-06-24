<?php
// ============================================================
// login_cliente.php — Autenticação de clientes (usuarios)
// ============================================================
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login-screen.html');
    exit;
}

// CORREÇÃO: O HTML envia o valor do CPF dentro do campo 'usuario'
$cpf   = preg_replace('/\D/', '', trim($_POST['usuario'] ?? ''));
$senha = trim($_POST['senha'] ?? '');

if (strlen($cpf) !== 11 || $senha === '') {
    header('Location: login-screen.html?perfil=cliente&erro=campos_vazios');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.cpf, u.email, u.senha, u.ativo,
               p.nome AS perfil
        FROM   usuarios u
        JOIN   perfis   p ON p.id = u.perfil_id
        WHERE  u.cpf = :cpf
        LIMIT  1
    ");
    $stmt->execute([':cpf' => $cpf]);
    $usuario = $stmt->fetch();

    $senhaOk = false;
    if ($usuario && $usuario['ativo']) {
        if (password_verify($senha, $usuario['senha']))      $senhaOk = true; // bcrypt
        elseif ($usuario['senha'] === $senha)                $senhaOk = true; // legado
    }

    if (!$senhaOk) {
        header('Location: login-screen.html?perfil=cliente&erro=credenciais');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['cliente_id']    = $usuario['id'];
    $_SESSION['cliente_nome']  = $usuario['nome'];
    $_SESSION['cliente_cpf']   = $usuario['cpf'];
    $_SESSION['perfil']        = 'cliente';

    // Atualiza último acesso
    $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id")
        ->execute([':id' => $usuario['id']]);

    header('Location: painel_cliente.php');
    exit;

} catch (PDOException $e) {
    error_log('Erro login cliente: ' . $e->getMessage());
    header('Location: login-screen.html?perfil=cliente&erro=sistema');
    exit;
}