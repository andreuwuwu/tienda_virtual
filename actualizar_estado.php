<?php
header("Content-Type: application/json");
require_once 'conexion.php';
session_start();

// Validar que el usuario esté autenticado y sea administrador
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['es_admin'] != 1) {
    echo json_encode(["success" => false, "error" => "No autorizado"]);
    exit;
}

// Obtener y validar los datos enviados por POST
$datos = json_decode(file_get_contents("php://input"), true);

if (!isset($datos['pedido_id']) || !isset($datos['nuevo_estado'])) {
    echo json_encode(["success" => false, "error" => "Datos incompletos"]);
    exit;
}

$pedido_id = intval($datos['pedido_id']);
$nuevo_estado = $conn->real_escape_string($datos['nuevo_estado']);

// Actualizar el estado del pedido en la base de datos
$sql = "UPDATE pedidos SET estado = '$nuevo_estado' WHERE id = $pedido_id";

if ($conn->query($sql)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Error al actualizar: " . $conn->error]);
}

$conn->close();
?>
