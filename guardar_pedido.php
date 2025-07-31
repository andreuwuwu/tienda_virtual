<?php
header('Content-Type: application/json');
require_once 'conexion.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["success" => false, "error" => "No autenticado"]);
    exit;
}

$datos = json_decode(file_get_contents("php://input"), true);

// Validar estructura de datos
if (!isset($datos['productos']) || !is_array($datos['productos']) || empty($datos['productos'])) {
    echo json_encode(["success" => false, "error" => "No hay productos en el pedido"]);
    exit;
}

$total = floatval($datos['total']);
$nombreCliente = $conn->real_escape_string($datos['nombreCliente']);

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Insertar en pedidos
    $stmt = $conn->prepare("INSERT INTO pedidos (usuario_id, cliente_nombre, total, estado, fecha) VALUES (?, ?, ?, 'confirmado', NOW())");
    $stmt->bind_param("isd", $_SESSION['usuario']['id'], $nombreCliente, $total);
    
    if (!$stmt->execute()) throw new Exception("Error al guardar pedido: " . $stmt->error);
    
    $pedidoId = $conn->insert_id;
    $stmt->close();

    // 2. Insertar en pedido_detalles
    $stmtDetalle = $conn->prepare("INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    
    foreach ($datos['productos'] as $producto) {
        $productoId = intval($producto['id']);
        $cantidad = intval($producto['cantidad']);
        $precio = floatval($producto['precio']);
        $stmtDetalle->bind_param("iiid", $pedidoId, $productoId, $cantidad, $precio);
        if (!$stmtDetalle->execute()) throw new Exception("Error al guardar detalle: " . $stmtDetalle->error);
    }

    $stmtDetalle->close();
    $conn->commit();

    // 3. Enlace WhatsApp
    $numeroAdmin = "59178352333"; // Tu número con código de país (sin +)
    $mensaje = "Hola, quiero confirmar mi pedido N°$pedidoId por Bs $total - $nombreCliente";
    $whatsappLink = "https://wa.me/$numeroAdmin?text=" . urlencode($mensaje);

    // 4. Enviar respuesta exitosa
    echo json_encode([
        "success" => true,
        "pedidoId" => $pedidoId,
        "whatsappLink" => $whatsappLink
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

$conn->close();
?>
