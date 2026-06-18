<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "sad_superseguro";

$conexao = mysqli_connect(
    $host,
    $usuario,
    $senha,
    $banco
);

if(!$conexao){
    die("Erro de conexão");
}

$nome = $_POST['nome'];
$cpf = $_POST['cpf'];
$rg = $_POST['rg'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];
$genero = isset($_POST['genero']) ? $_POST['genero'] : '';
$datanascimento = $_POST['datanascimento'];
$pais = $_POST['pais'];
$estado = $_POST['estado'];
$cidade = $_POST['cidade'];
$rua = $_POST['rua'];
$numeroresi = $_POST['numeroresi'];
$salario = $_POST['salario'];
$valorapli = $_POST['valorapli'];

$sql = "INSERT INTO usuarios(
nome,
cpf,
rg,
email,
telefone,
genero,
datanascimento,
pais,
estado,
cidade,
rua,
numeroresi,
salario,
valorapli
)

VALUES(
'$nome',
'$cpf',
'$rg',
'$email',
'$telefone',
'$genero',
'$datanascimento',
'$pais',
'$estado',
'$cidade',
'$rua',
'$numeroresi',
'$salario',
'$valorapli'
)";

if(mysqli_query($conexao, $sql)){
    echo "
    <script>
        alert('Cadastro realizado com sucesso!');
        window.location.href='cadastro.html';
    </script>
    ";
}else{
    echo "
    <script>
        alert('Erro ao cadastrar!');
        history.back();
    </script>
    ";
}

?>