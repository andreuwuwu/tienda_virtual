<?php
header('Content-Type: application/json');
require_once 'conexion.php'; // Incluye la conexión PDO que conecta a Neon
session_start();

$datos = json_decode(file_get_contents("php://input"), true);

// Verificar que se recibieron datos
if (!$datos) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No se recibieron datos"]);
    exit;
}

// Validar campos requeridos
$camposRequeridos = ["nombre", "telefono", "correo", "direccion", "contrasena"];
foreach ($camposRequeridos as $campo) {
    if (!isset($datos[$campo]) || empty(trim($datos[$campo]))) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "El campo $campo es requerido"]);
        exit;
    }
}

// Sanitizar y validar datos (trim para quitar espacios)
// Con PDO y prepared statements, no necesitas real_escape_string.
$nombre = trim($datos["nombre"]);
$telefono = trim($datos["telefono"]);
$correo = trim($datos["correo"]);
$direccion = trim($datos["direccion"]);
$contrasena = trim($datos["contrasena"]); // Contraseña sin hashing, como en tu implementación actual

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Formato de correo electrónico inválido"]);
    exit;
}

try {
    // Verificar si el correo ya existe
    // Aseguramos el esquema 'tienda_virtual'
    $stmt = $conn->prepare("SELECT id FROM tienda_virtual.usuarios WHERE correo = ?");
    $stmt->execute([$correo]); // Pasa el parámetro como un array a execute()

    // Para PDO, fetch() devolverá un array si encuentra un resultado, o false si no.
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["success" => false, "error" => "El correo ya está registrado"]);
        exit;
    }
    $stmt = null; // Cierra el statement

    // Insertar el nuevo usuario
    // Aseguramos el esquema 'tienda_virtual'
    $sqlInsert = "INSERT INTO tienda_virtual.usuarios (nombre, telefono, correo, direccion, contrasena, es_admin) VALUES (?, ?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    
    // Asignar 0 (false) a 'es_admin' por defecto para nuevos usuarios
    $es_admin = 0; 

    $stmtInsert->execute([$nombre, $telefono, $correo, $direccion, $contrasena, $es_admin]);

    // Si la inserción fue exitosa
    echo json_encode(["success" => true, "message" => "Usuario registrado con éxito."]);

} catch (PDOException $e) {
    // Capturar errores de la base de datos
    http_response_code(500);
    // Mostrar el mensaje de error real de la base de datos
    echo json_encode(["success" => false, "error" => "Error al registrar usuario: " . $e->getMessage()]);
} catch (Exception $e) {
    // Capturar cualquier otra excepción inesperada
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error inesperado al registrar usuario: " . $e->getMessage()]);
}

// Con PDO, no necesitas cerrar la conexión explícitamente ($conn = null;),
// PHP la cierra automáticamente al final del script.
?>
