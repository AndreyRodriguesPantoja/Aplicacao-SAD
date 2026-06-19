<?php

session_start();

$conexao = mysqli_connect(
    "localhost",
    "root",
    "",
    "sad_superseguro"
);

if(!$conexao){
    die("Deu certo não, corrige aí!!");
}

$login = $_POST['login'];
$senha = $_POST['senha'];

$sql = "SELECT * FROM funcionarios
        WHERE login='$login'
        AND senha='$senha'";

$resultado = mysqli_query($conexao, $sql);

echo "login digitado: " . $login . "<br>";
echo "Senha digitado: " . $senha . "<br>";
echo "Registros encontrados: " . mysqli_num_rows($resultado);

if(mysqli_num_rows($resultado) == 1){
    
    $funcionarios = mysqli_fetch_assoc($resultado);

    $_SESSION['id_funcionarios'] = $funcionarios['id'];
    $_SESSION['nome_funcionarios'] = $funcionarios['nome'];
    $_SESSION['login_funcionarios'] = $funcionarios['login'];

    header("Location: index.php");
    exit;

}else{

    echo "
    <script>
        alert('Login ou senha incorretos!');
        history.back();
    </script>
    ";
}
?>