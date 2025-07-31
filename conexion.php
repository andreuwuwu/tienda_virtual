<?php
// conexion.php - Conexión a la base de datos Neon (PostgreSQL)

// ************************************************************************************
// *** IMPORTANTE: Reemplaza estos valores con los datos EXACTOS de tu conexión a Neon ***
// ************************************************************************************

// Puedes obtener estos datos de tu panel de control de Neon, en la sección "Connect"
// La URL completa de conexión 'psql' que viste es:
// 'postgresql://neondb_owner:npg_cgvUQXe3CHJ1@ep-dry-shadow-ac9moc1g-pooler.sa-east-1.aws.neon.tech:5432/neondb?sslmode=require&channel_binding=require'
// Y de ahí extraemos los componentes:

$host = "ep-dry-shadow-ac9moc1g-pooler.sa-east-1.aws.neon.tech"; // Tu host de Neon
$port = "5432";
$dbname = "neondb"; // El nombre de tu base de datos en Neon
$user = "neondb_owner"; // Tu usuario de Neon
$password = "npg_cgvUQXe3CHJ1"; // Tu contraseña de Neon (¡esta es la que viste oculta con asteriscos!)
$sslmode = "require"; // Neon exige SSL para conexiones seguras

try {
    // Cadena de conexión DSN (Data Source Name) para PDO PostgreSQL
    // PDO es la extensión de PHP recomendada para bases de datos, incluyendo PostgreSQL.
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";

    // Crear una nueva instancia de PDO
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Lanza excepciones en caso de errores SQL, útil para depuración.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC    // Obtiene resultados como arrays asociativos por defecto (más fácil de manejar).
    ]);

    // Opcional: Puedes quitar o comentar esta línea en producción para evitar mensajes innecesarios.
    // echo "Conexión a PostgreSQL en Neon exitosa!";

} catch (PDOException $e) {
    // En caso de error de conexión, detiene la ejecución y muestra el mensaje de error.
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>