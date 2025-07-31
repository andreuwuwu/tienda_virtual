<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['es_admin'] != 1) {
    header("Location: index.html"); // Redirigir si no es admin
    exit;
}

require_once 'conexion.php';

// Obtener el estado de filtro de la URL, por defecto 'pendiente'
$estado_filtro = isset($_GET['estado']) ? $conn->real_escape_string($_GET['estado']) : 'pendiente';

// Lista de estados válidos para evitar inyección SQL directa
$estados_validos = ['pendiente', 'confirmado', 'entregado', 'negado'];

if (!in_array($estado_filtro, $estados_validos)) {
    $estado_filtro = 'pendiente'; // Si el estado no es válido, vuelve al predeterminado
}

// Obtener pedidos con información del usuario, filtrando por estado
$sql = "SELECT p.id, u.nombre AS cliente, p.estado, p.fecha, p.total
        FROM pedidos p
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.estado = ?
        ORDER BY p.fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $estado_filtro); // 's' para string
$stmt->execute();
$resultado = $stmt->get_result(); // Obtener el resultado de la consulta

// Verificar si la consulta fue exitosa
if (!$resultado) {
    die("Error al obtener pedidos: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - Tienda Orégano</title>
  <style>
    /* Definir variables de color para consistencia con index.html */
    :root {
        --primary-color: #4CAF50; /* Verde principal */
        --primary-dark: #388E3C;
        --secondary-color: #2196F3; /* Azul para botones secundarios/links */
        --danger-color: #f44336; /* Rojo para acciones peligrosas */
        --text-color: #333;
        --light-bg: #f5f5f5; /* Fondo claro general */
        --card-bg: #ffffff; /* Fondo para tarjetas/secciones */
        --border-color: #e0e0e0; /* Color de borde suave */
        --shadow-light: rgba(0, 0, 0, 0.08); /* Sombra ligera */
        --shadow-medium: rgba(0, 0, 0, 0.15); /* Sombra media */
    }

    body {
      font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background-color: var(--light-bg); /* Usar la variable de fondo claro */
      margin: 0;
      padding: 20px;
      color: var(--text-color);
    }
    h2 {
      text-align: center;
      color: var(--primary-dark); /* Color de título consistente */
      margin-bottom: 30px;
      font-size: 2em;
      position: relative;
    }
    h2::after { /* Línea decorativa debajo del título, como en index.html */
        content: '';
        display: block;
        width: 60px;
        height: 3px;
        background: var(--primary-color);
        margin: 10px auto 0;
        border-radius: 2px;
    }
    .container { /* Contenedor principal para el panel */
      max-width: 1000px; /* Aumentar el ancho para la tabla */
      margin: 20px auto;
      background: var(--card-bg);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 20px var(--shadow-medium); /* Sombra más pronunciada */
    }
    /* Estilos para los botones de filtro */
    .filter-buttons {
        text-align: center;
        margin-bottom: 25px;
        display: flex; /* Para que estén en fila */
        justify-content: center; /* Centrar los botones */
        flex-wrap: wrap; /* Envolver en pantallas pequeñas */
        gap: 10px; /* Espacio entre botones */
    }
    .filter-btn {
        display: inline-block;
        padding: 10px 20px;
        border: 1px solid var(--primary-color);
        border-radius: 25px; /* Más redondeados como pestañas */
        background-color: transparent;
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .filter-btn:hover {
        background-color: rgba(76, 175, 80, 0.1); /* Ligero fondo al pasar el ratón */
        color: var(--primary-dark);
    }
    .filter-btn.active {
        background-color: var(--primary-color); /* Color de fondo para el activo */
        color: white;
        border-color: var(--primary-color);
        box-shadow: 0 2px 8px var(--shadow-light);
    }
    .filter-btn.active:hover {
        background-color: var(--primary-dark); /* Un poco más oscuro al pasar el ratón en el activo */
        color: white;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      font-size: 0.95em;
    }
    th, td {
      padding: 15px;
      border-bottom: 1px solid var(--border-color); /* Borde más suave */
      text-align: left;
    }
    th {
      background-color: var(--primary-color); /* Encabezados de tabla con color primario */
      color: white;
      font-weight: 600;
      text-transform: uppercase;
    }
    tr:last-child td {
      border-bottom: none; /* Eliminar borde inferior de la última fila */
    }
    tr:hover {
      background-color: rgba(76, 175, 80, 0.05); /* Ligero hover en las filas */
    }
    select {
      padding: 8px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      background-color: white;
      font-size: 0.9em;
      min-width: 120px; /* Asegurar un ancho mínimo */
    }
    button.btn-confirmar { /* Botón de actualizar */
      padding: 10px 18px;
      border: none;
      border-radius: 8px;
      background-color: var(--secondary-color); /* Usar color secundario */
      color: white;
      cursor: pointer;
      font-size: 0.9em;
      font-weight: 600;
      transition: background-color 0.3s ease, transform 0.1s ease;
    }
    button.btn-confirmar:hover {
      background-color: #1976D2; /* Azul más oscuro al pasar el ratón */
    }
    button.btn-confirmar:active {
        transform: scale(0.98);
    }

    .btn-back-home { /* Estilo para el botón de volver al inicio */
      display: inline-block;
      margin-top: 25px;
      padding: 12px 25px;
      background-color: var(--primary-color);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      transition: background-color 0.3s ease, transform 0.1s ease;
    }
    .btn-back-home:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
    }

    /* Responsividad básica para el panel de administración */
    @media (max-width: 768px) {
      body {
        padding: 10px;
      }
      .container {
        margin: 10px auto;
        padding: 15px;
        border-radius: 8px;
      }
      table, thead, tbody, th, td, tr {
        display: block; /* Apilar elementos de tabla */
      }
      thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }
      tr {
        margin-bottom: 15px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background-color: var(--card-bg);
      }
      td {
        border: none;
        position: relative;
        padding-left: 50%; /* Espacio para la "etiqueta" del encabezado */
        text-align: right;
      }
      td:before {
        content: attr(data-label); /* Usar el atributo data-label para mostrar el encabezado */
        position: absolute;
        left: 10px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        text-align: left;
        font-weight: bold;
      }
      td:last-child {
        border-bottom: none;
      }
      select, button.btn-confirmar {
        width: 100%;
        box-sizing: border-box; /* Incluir padding y border en el ancho */
        margin-top: 5px;
      }
      .btn-back-home {
        width: calc(100% - 20px); /* Ajustar al ancho del contenedor */
        box-sizing: border-box;
        text-align: center;
      }
      .filter-buttons {
          flex-direction: column; /* Apilar botones de filtro en móviles */
          align-items: center;
      }
      .filter-btn {
          width: 80%; /* Ancho más grande para botones de filtro en móviles */
          box-sizing: border-box;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Panel de Administración de Pedidos</h2>

    <div class="filter-buttons">
        <a href="panel_admin.php?estado=pendiente" class="filter-btn <?= $estado_filtro == 'pendiente' ? 'active' : '' ?>">Pendientes</a>
        <a href="panel_admin.php?estado=confirmado" class="filter-btn <?= $estado_filtro == 'confirmado' ? 'active' : '' ?>">Confirmados</a>
        <a href="panel_admin.php?estado=entregado" class="filter-btn <?= $estado_filtro == 'entregado' ? 'active' : '' ?>">Entregados</a>
        <a href="panel_admin.php?estado=negado" class="filter-btn <?= $estado_filtro == 'negado' ? 'active' : '' ?>">Negados/Cancelados</a>
    </div>

    <?php if ($resultado->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>ID Pedido</th>
          <th>Cliente</th>
          <th>Estado</th>
          <th>Fecha</th>
          <th>Total (Bs)</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $resultado->fetch_assoc()): ?>
          <tr>
            <td data-label="ID Pedido"><?= htmlspecialchars($row['id']) ?></td>
            <td data-label="Cliente"><?= htmlspecialchars($row['cliente']) ?></td>
            <td data-label="Estado">
              <select id="estado_<?= htmlspecialchars($row['id']) ?>">
                <option value="pendiente" <?= $row['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="confirmado" <?= $row['estado'] === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                <option value="entregado" <?= $row['estado'] === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                <option value="negado" <?= $row['estado'] === 'negado' ? 'selected' : '' ?>>Negado</option> </select>
            </td>
            <td data-label="Fecha"><?= htmlspecialchars($row['fecha']) ?></td>
            <td data-label="Total (Bs)"><?= number_format($row['total'], 2) ?></td>
            <td data-label="Acción">
              <button class="btn-confirmar" onclick="actualizarEstado(<?= htmlspecialchars($row['id']) ?>)">Actualizar</button>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
        <p style="text-align: center; color: var(--text-color);">No hay pedidos en estado "<?= htmlspecialchars($estado_filtro) ?>".</p>
    <?php endif; ?>

    <a href="index.html" class="btn-back-home">Volver a la Tienda</a>
  </div>

  <script>
    function actualizarEstado(id) {
      const estado = document.getElementById("estado_" + id).value;

      fetch("actualizar_estado.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ pedido_id: id, nuevo_estado: estado })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert("Estado actualizado con éxito!");
          // Recargar la página para que la tabla se actualice con el filtro actual
          window.location.reload();
        } else {
          alert("Error al actualizar el estado: " + data.error);
        }
      })
      .catch(error => {
        console.error("Error en la solicitud:", error);
        alert("Ocurrió un error al intentar actualizar el estado.");
      });
    }
  </script>
</body>
</html>

<?php
$conn->close();
?>