<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "sad_superseguro";
/*$db   = "super_seguro";*/

/*try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode([
        "status" => "erro",
        "mensagem" => "Falha na conexão: " . $e->getMessage()
    ]));
}*/

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch(PDOException $e){
    die("Erro: " . $e->getMessage());
}