<?php
header('Content-Type: application/json');
require_once 'conexion.php'; // Incluye la conexión PDO

$productos = [];

try {
    // La consulta ahora usa el esquema 'tienda_virtual' para la tabla 'productos'
    // Como no hay parámetros, se puede usar query() directamente.
    $stmt = $conn->query("SELECT id, nombre, precio, imagen_url FROM tienda_virtual.productos");

    // fetchAll(PDO::FETCH_ASSOC) obtendrá todas las filas como un array asociativo.
    // Alternativamente, puedes iterar con fetch() en un bucle while si el conjunto de resultados es muy grande.
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Asegurarse de que el precio sea un float si no lo es ya
    foreach ($productos as &$producto) {
        $producto['precio'] = floatval($producto['precio']);
        // Asegurarse de que la URL de la imagen sea accesible o un valor por defecto si es nula
        if (!isset($producto['imagen_url']) || is_null($producto['imagen_url']) || empty($producto['imagen_url'])) {
            $producto['imagen_url'] = 'placeholder.jpg'; // O una URL de imagen por defecto
        }
    }
    unset($producto); // Romper la referencia del último elemento

    echo json_encode($productos);

} catch (PDOException $e) {
    // Capturar errores de la base de datos
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener productos: " . $e->getMessage()]);
}

// En PDO, la conexión se cierra automáticamente cuando el script termina,
// o puedes establecer $conn = null; si quieres cerrarla antes.
?>