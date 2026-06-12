<?php

session_start();

if(!isset($_SESSION['id'])){
    header("Location: login-screen.html");
    exit;
}

?>

<h1>Bem-Vindo <?= $_SESSION['usuario']; ?></h1>
<a href="logout.php">Sair</a>