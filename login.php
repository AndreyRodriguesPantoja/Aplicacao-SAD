<?php

session_start();
require 'conexao.php';

$usuario = $_POST['usuario'];
$senha = $_POST['senha'];

$sql = $pdo->sad_superseguro("
    SELECT * FROM funcionario WHERE usuario = ? AND senha = ?
")

$sql->execute([$usuario, $senha]);

if($sql->rowCount() > 0){
    
    $dados = $sql->fetch();

    $_SESSION['id'] = $dados['id'];
    $_SESSION['usuario'] = $dados['usuario'];

    header("Location: painel.php");
    exit;

}else{
    echo "Usuário ou senha inválida";
}