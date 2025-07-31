<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$resultado = $conn->query("SELECT id, nombre, precio FROM productos");

$productos = [];

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = [
            "id" => $fila["id"],
            "nombre" => $fila["nombre"],
            "precio" => floatval($fila["precio"])
        ];
    }
    echo json_encode($productos);
} else {
    echo json_encode(["error" => "Error al obtener productos: " . $conn->error]);
}

$conn->close();
?>
