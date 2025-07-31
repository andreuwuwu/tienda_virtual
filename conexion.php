<?php
// conexion.php - Conexión a la base de datos Neon (PostgreSQL)

// Datos de conexión a Neon
$host = "ep-dry-shadow-ac9moc1g-pooler.sa-east-1.aws.neon.tech"; // Tu host de Neon
$port = "5432";
$dbname = "neondb"; // El nombre de tu base de datos en Neon
$user = "neondb_owner"; // Tu usuario de Neon
$password = "npg_cgvUQXe3CHJ1"; // Tu contraseña de Neon
$sslmode = "require"; // Neon exige SSL para conexiones seguras

try {
    // Cadena de conexión DSN (Data Source Name) para PDO PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";

    // Crear una nueva instancia de PDO
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Lanza excepciones en caso de errores SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC    // Obtiene resultados como arrays asociativos
    ]);

    // Opcional: Puedes quitar o comentar esta línea en producción
    // echo "Conexión a PostgreSQL en Neon exitosa!";

} catch (PDOException $e) {
    // En caso de error, detiene la ejecución y muestra el mensaje
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>