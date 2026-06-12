<?php
include 'conexao.php';

header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$json = file_get_contents('php://input');
$dados = json_decode($json, true);

if (!$dados || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "JSON inválido"
    ]);
    exit;
}

if (
    !isset($dados['idade'], $dados['imc'], $dados['risco']) ||
    !is_numeric($dados['idade']) ||
    !is_numeric($dados['imc']) ||
    !is_numeric($dados['risco'])
) {
    http_response_code(400);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Dados inválidos"
    ]);
    exit;
}

$idade = (int)$dados['idade'];
$imc = (float)$dados['imc'];
$risco = (float)$dados['risco'];

if ($idade < 0 || $imc <= 0) {
    http_response_code(400);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Valores fora do padrão"
    ]);
    exit;
}

try {
    $sql = "INSERT INTO clientes (Ins_Age, BMI, Response)
            VALUES (:idade, :imc, :risco)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':idade' => $idade,
        ':imc'   => $imc,
        ':risco' => $risco
    ]);

    echo json_encode(["status" => "sucesso"]);
} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao salvar os dados."
    ]);
}
?>

