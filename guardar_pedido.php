<?php
header('Content-Type: application/json');
require_once 'conexion.php'; // Incluye la conexión PDO
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["success" => false, "error" => "No autenticado"]);
    exit;
}

// Decodificar los datos JSON enviados en el cuerpo de la solicitud
$datos = json_decode(file_get_contents("php://input"), true);

// Validar que la estructura de datos sea correcta
if (!isset($datos['productos']) || !is_array($datos['productos']) || empty($datos['productos'])) {
    echo json_encode(["success" => false, "error" => "No hay productos en el pedido"]);
    exit;
}

// Validar que se hayan recibido el total y el nombre del cliente
if (!isset($datos['total']) || !isset($datos['nombreCliente'])) {
    echo json_encode(["success" => false, "error" => "Datos de pedido incompletos (total o nombreCliente)."]);
    exit;
}

// Obtener y sanitizar el total y el nombre del cliente
$total = floatval($datos['total']);
$nombreCliente = trim($datos['nombreCliente']); // trim para quitar espacios, PDO maneja la sanitización en el execute.

// Iniciar transacción de base de datos
// Esto asegura que todas las operaciones (insertar pedido y detalles) se realicen
// como una sola unidad atómica. Si algo falla, todo se deshace (rollback).
$conn->beginTransaction();

try {
    // 1. Insertar en la tabla 'pedidos'
    // Se insertan: usuario_id, cliente_nombre, total, estado y la fecha actual.
    // 'confirmado' es el estado inicial y CURRENT_TIMESTAMP es una función de PostgreSQL para la fecha/hora.
    $stmt = $conn->prepare("INSERT INTO tienda_virtual.pedidos (usuario_id, cliente_nombre, total, estado, fecha) VALUES (?, ?, ?, 'confirmado', CURRENT_TIMESTAMP)");
    
    // Ejecutar la inserción con los parámetros. PDO se encarga de la sanitización.
    $stmt->execute([
        $_SESSION['usuario']['id'], // ID del usuario de la sesión
        $nombreCliente,             // Nombre del cliente proporcionado en el formulario
        $total                      // Total del pedido
    ]);
    
    // Obtener el ID del pedido recién insertado.
    // Es crucial para PostgreSQL especificar el nombre de la secuencia asociada a la columna serial/bigserial.
    // El formato suele ser 'nombre_esquema.nombre_tabla_nombre_columna_seq'.
    $pedidoId = $conn->lastInsertId('tienda_virtual.pedidos_id_seq');
    $stmt = null; // Cierra el statement para liberar recursos (buena práctica)

    // 2. Insertar en la tabla 'pedido_detalles' para cada producto en el pedido
    $stmtDetalle = $conn->prepare("INSERT INTO tienda_virtual.pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    
    foreach ($datos['productos'] as $producto) {
        $productoId = intval($producto['id']);
        $cantidad = intval($producto['cantidad']);
        $precio = floatval($producto['precio']);
        
        // Ejecutar la inserción de cada detalle de producto
        $stmtDetalle->execute([
            $pedidoId,     // El ID del pedido al que pertenece este detalle
            $productoId,   // ID del producto
            $cantidad,     // Cantidad de este producto
            $precio        // Precio unitario del producto en el momento de la compra
        ]);
    }

    $stmtDetalle = null; // Cierra el statement

    // Si todas las inserciones fueron exitosas, confirmar la transacción
    $conn->commit();

    // 3. Generar enlace de WhatsApp para el administrador
    $numeroAdmin = "59178352333"; // Reemplaza con tu número de WhatsApp con código de país (sin +)
    $mensaje = "Hola, quiero confirmar mi pedido N°$pedidoId por Bs $total - $nombreCliente";
    $whatsappLink = "https://wa.me/$numeroAdmin?text=" . urlencode($mensaje);

    // Enviar respuesta JSON de éxito al cliente
    echo json_encode([
        "success" => true,
        "message" => "Pedido realizado con éxito. Serás redirigido a WhatsApp para confirmar.",
        "whatsappLink" => $whatsappLink
    ]);

} catch (PDOException $e) {
    // Si ocurre cualquier error de base de datos (PDOException), se revierte la transacción.
    $conn->rollBack();
    http_response_code(500); // Enviar un código de estado HTTP 500 (Error Interno del Servidor)
    echo json_encode([
        "success" => false, 
        "error" => "Error al procesar el pedido: " . $e->getMessage(),
        "sql_error_info" => $e->errorInfo // Incluye información detallada del error de la base de datos
    ]);
} catch (Exception $e) {
    // Capturar cualquier otra excepción no relacionada con PDO (ej. errores lógicos, validaciones)
    $conn->rollBack(); // También revertir la transacción por si acaso
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error inesperado: " . $e->getMessage()]);
}
?>
