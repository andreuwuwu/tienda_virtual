<?php
header('Content-Type: application/json');
require_once 'conexion.php'; // Incluye la conexión PDO
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

if (!isset($datos['total']) || !isset($datos['nombreCliente'])) {
    echo json_encode(["success" => false, "error" => "Datos de pedido incompletos (total o nombreCliente)."]);
    exit;
}

$total = floatval($datos['total']);
$nombreCliente = trim($datos['nombreCliente']); // trim para quitar espacios, PDO maneja la sanitización.

// Iniciar transacción
// Esto asegura que si una parte de la operación falla, todas se deshacen.
$conn->beginTransaction();

try {
    // 1. Insertar en la tabla 'pedidos'
    // Usamos CURRENT_TIMESTAMP para la fecha en PostgreSQL
    // Aseguramos el esquema 'tienda_virtual'
    $stmt = $conn->prepare("INSERT INTO tienda_virtual.pedidos (usuario_id, cliente_nombre, total, estado, fecha) VALUES (?, ?, ?, 'confirmado', CURRENT_TIMESTAMP)");
    
    // Ejecutar la inserción con los parámetros
    $stmt->execute([
        $_SESSION['usuario']['id'],
        $nombreCliente,
        $total
    ]);
    
    // Obtener el ID del pedido recién insertado.
    // Para PostgreSQL, necesitas especificar el nombre de la secuencia.
    // El nombre de la secuencia suele ser: nombre_tabla_columna_id_seq
    // En tu caso, para la tabla 'pedidos' y columna 'id', con el esquema 'tienda_virtual',
    // lo más probable es que sea 'tienda_virtual.pedidos_id_seq'.
    $pedidoId = $conn->lastInsertId('tienda_virtual.pedidos_id_seq');
    $stmt = null; // Cierra el statement (opcional, pero buena práctica)

    // 2. Insertar en la tabla 'pedido_detalles'
    // Aseguramos el esquema 'tienda_virtual'
    $stmtDetalle = $conn->prepare("INSERT INTO tienda_virtual.pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    
    foreach ($datos['productos'] as $producto) {
        $productoId = intval($producto['id']);
        $cantidad = intval($producto['cantidad']);
        $precio = floatval($producto['precio']);
        
        // Ejecutar la inserción de cada detalle
        $stmtDetalle->execute([
            $pedidoId,
            $productoId,
            $cantidad,
            $precio
        ]);
    }

    $stmtDetalle = null; // Cierra el statement

    // Si todo fue exitoso, confirmar la transacción
    $conn->commit();

    // 3. Generar enlace de WhatsApp (si aplica)
    $numeroAdmin = "59178352333"; // Tu número con código de país (sin +)
    $mensaje = "Hola, quiero confirmar mi pedido N°$pedidoId por Bs $total - $nombreCliente";
    $whatsappLink = "https://wa.me/$numeroAdmin?text=" . urlencode($mensaje);

    echo json_encode([
        "success" => true,
        "message" => "Pedido realizado con éxito.",
        "whatsappLink" => $whatsappLink
    ]);

} catch (PDOException $e) {
    // Si algo falla, revertir la transacción para no dejar datos incompletos
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error al procesar el pedido: " . $e->getMessage()]);
} catch (Exception $e) {
    // Capturar otras excepciones si las hubiera (ej. validaciones, errores lógicos)
    $conn->rollBack(); // También revertir si hay un error no-PDO
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error inesperado: " . $e->getMessage()]);
}
?>