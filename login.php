<?php
header('Content-Type: application/json');
require_once 'conexion.php';
session_start();

$input = json_decode(file_get_contents("php://input"), true);
$correo = $conn->real_escape_string($input['correo']);
$contrasena = $conn->real_escape_string($input['contrasena']);

$sql = "SELECT * FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $usuario = $result->fetch_assoc();

    // Comparar contraseña directamente (sin hash, según tu implementación actual)
    if ($usuario['contrasena'] === $contrasena) {
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
    }
}

echo json_encode([
    "success" => false,
    "error" => "Credenciales incorrectas"
]);
?>
