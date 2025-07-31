<?php
header('Content-Type: application/json');
require_once 'conexion.php'; // Incluye la conexión PDO
session_start();

$input = json_decode(file_get_contents("php://input"), true);

// Validar que se recibieron correo y contraseña
if (!isset($input['correo']) || empty(trim($input['correo'])) || !isset($input['contrasena']) || empty(trim($input['contrasena']))) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Correo y contraseña son requeridos."]);
    exit;
}

$correo = trim($input['correo']);
$contrasena = trim($input['contrasena']); // Recuerda que la contraseña no está hasheada en tu DB actual.

try {
    // Preparar la consulta SQL para buscar el usuario por correo
    // Usamos el esquema 'tienda_virtual' para la tabla 'usuarios'
    $sql = "SELECT id, nombre, correo, contrasena, es_admin FROM tienda_virtual.usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$correo]); // Ejecutar la consulta con el correo como parámetro

    // Obtener el resultado
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si se encontró un usuario y si la contraseña coincide
    if ($usuario && $usuario['contrasena'] === $contrasena) {
        // Autenticación exitosa, guardar datos del usuario en la sesión
        $_SESSION['usuario'] = [
            "id" => $usuario['id'],
            "nombre" => $usuario['nombre'],
            "correo" => $usuario['correo'],
            "es_admin" => $usuario['es_admin']
        ];

        echo json_encode([
            "success" => true,
            "usuario" => $_SESSION['usuario']
        ]);
        exit;
    } else {
        // No se encontró el usuario o la contraseña no coincide
        http_response_code(401); // Unauthorized
        echo json_encode([
            "success" => false,
            "error" => "Credenciales incorrectas"
        ]);
        exit;
    }

} catch (PDOException $e) {
    // Capturar errores de la base de datos
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error en la base de datos: " . $e->getMessage()]);
}
?>