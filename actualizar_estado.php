<?php
header("Content-Type: application/json");
require_once 'conexion.php'; // Incluye la conexión PDO
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
$nuevo_estado = trim($datos['nuevo_estado']); // trim para quitar espacios, PDO maneja la sanitización.

// Lista de estados válidos para evitar cualquier inyección no deseada
$estados_validos = ['pendiente', 'confirmado', 'entregado', 'negado'];
if (!in_array($nuevo_estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Estado inválido proporcionado."]);
    exit;
}

try {
    // Actualizar el estado del pedido en la base de datos
    // Usamos el esquema 'tienda_virtual' para la tabla 'pedidos'
    $sql = "UPDATE tienda_virtual.pedidos SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    // Ejecutar la consulta con los parámetros
    $exec = $stmt->execute([$nuevo_estado, $pedido_id]);

    if ($exec) {
        // Verificar si alguna fila fue afectada (es decir, si el pedido_id existía y se actualizó)
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Estado actualizado exitosamente."]);
        } else {
            // No se encontró el pedido con ese ID o el estado ya era el mismo
            echo json_encode(["success" => false, "error" => "No se encontró el pedido o el estado ya era el mismo."]);
        }
    } else {
        // Si execute devuelve false (aunque con PDO::ERRMODE_EXCEPTION esto no debería pasar)
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Error al ejecutar la actualización."]);
    }

} catch (PDOException $e) {
    // Capturar errores de la base de datos
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error en la base de datos: " . $e->getMessage()]);
}

// En PDO, la conexión se cierra automáticamente cuando el script termina.
?>