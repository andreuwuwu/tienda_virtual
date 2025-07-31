<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tienda_virtual");
if ($conexion->connect_error) {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => "Error de conexión: " . $conexion->connect_error]);
  exit;
}

// Obtener los datos del cuerpo de la solicitud
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

// Sanitizar y validar datos
$nombre = $conexion->real_escape_string(trim($datos["nombre"]));
$telefono = $conexion->real_escape_string(trim($datos["telefono"]));
$correo = $conexion->real_escape_string(trim($datos["correo"]));
$direccion = $conexion->real_escape_string(trim($datos["direccion"]));

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(["success" => false, "error" => "Formato de correo electrónico inválido"]);
  exit;
}

// Verificar si el correo ya existe
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
  http_response_code(409);
  echo json_encode(["success" => false, "error" => "El correo ya está registrado"]);
  $stmt->close();
  exit;
}
$stmt->close();

// Hash de la contraseña
$contrasena = $conexion->real_escape_string(trim($datos["contrasena"]));

// Insertar el nuevo usuario (usando consultas preparadas)
$stmt = $conexion->prepare("INSERT INTO usuarios (nombre, telefono, correo, direccion, contrasena) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $nombre, $telefono, $correo, $direccion, $contrasena);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => "Error al registrar usuario: " . $stmt->error]);
}

$stmt->close();
$conexion->close();
?>