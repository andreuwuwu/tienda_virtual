<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['es_admin'] != 1) {
    header("Location: index.html"); // Redirigir si no es admin
    exit;
}

require_once 'conexion.php'; // Incluye la conexión PDO

// Obtener el estado de filtro de la URL, por defecto 'pendiente'
// No necesitamos real_escape_string aquí, PDO lo maneja con prepared statements
$estado_filtro = isset($_GET['estado']) ? trim($_GET['estado']) : 'pendiente';

// Lista de estados válidos para evitar inyección SQL directa
$estados_validos = ['pendiente', 'confirmado', 'entregado', 'negado'];

if (!in_array($estado_filtro, $estados_validos)) {
    $estado_filtro = 'pendiente'; // Si el estado no es válido, vuelve al predeterminado
}

$pedidos = []; // Array para almacenar los pedidos

try {
    // Obtener pedidos con información del usuario, filtrando por estado
    // Usamos el esquema 'tienda_virtual' para las tablas 'pedidos' y 'usuarios'
    $sql = "SELECT p.id, u.nombre AS cliente, p.estado, p.fecha, p.total
            FROM tienda_virtual.pedidos p
            JOIN tienda_virtual.usuarios u ON p.usuario_id = u.id
            WHERE p.estado = ?
            ORDER BY p.fecha DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$estado_filtro]); // Ejecutar la consulta con el estado_filtro

    // Obtener todos los resultados
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Capturar errores de la base de datos y mostrar un mensaje
    // En un entorno de producción, solo registrarías el error y mostrarías un mensaje genérico.
    echo "<p style='color: red;'>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</p>";
    $pedidos = []; // Asegurarse de que $pedidos es un array vacío en caso de error
}

// No necesitas $conn->close() con PDO, la conexión se cierra automáticamente al final del script
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel de Administración</title>
  <style>
    /* Estilos CSS (puedes mantener los que ya tenías o mejorarlos) */
    :root {
      --primary-color: #4CAF50; /* Un verde agradable */
      --primary-dark: #388E3C;
      --secondary-color: #2196F3; /* Azul para acciones secundarias */
      --danger-color: #f44336;
      --text-color: #333;
      --light-bg: #f5f5f5;
      --card-bg: #ffffff;
      --border-color: #e0e0e0;
      --shadow-light: rgba(0, 0, 0, 0.08);
      --shadow-medium: rgba(0, 0, 0, 0.15);
    }

    body {
      font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background-color: var(--light-bg);
      color: var(--text-color);
      line-height: 1.6;
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 1200px;
      margin: 20px auto;
      background: var(--card-bg);
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 15px var(--shadow-medium);
    }

    h1 {
      text-align: center;
      color: var(--primary-dark);
      margin-bottom: 30px;
    }

    .filter-section {
      text-align: center;
      margin-bottom: 20px;
    }

    .filter-section label {
      font-weight: bold;
      margin-right: 10px;
    }

    .filter-section select {
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 5px;
      font-size: 1rem;
      background-color: white;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      box-shadow: 0 2px 10px var(--shadow-light);
      border-radius: 8px;
      overflow: hidden; /* Asegura que el border-radius afecte a los bordes de la tabla */
    }

    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
    }

    th {
      background-color: var(--primary-color);
      color: white;
      font-weight: bold;
      text-transform: uppercase;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tr:hover {
      background-color: #f1f1f1;
    }

    .btn-confirmar, .btn-back-home {
      background-color: var(--secondary-color);
      color: white;
      padding: 8px 15px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background-color 0.3s ease;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .btn-confirmar:hover {
      background-color: #1976D2; /* Un azul más oscuro */
    }

    .btn-back-home {
      margin-top: 20px;
      background-color: var(--primary-dark);
    }

    .btn-back-home:hover {
      background-color: #2e7d32; /* Un verde más oscuro */
    }

    select.estado-select {
        width: 100%;
        padding: 5px;
        border-radius: 3px;
        border: 1px solid #ccc;
    }

    /* Estilos para hacer la tabla responsive */
    @media screen and (max-width: 768px) {
      table, thead, tbody, th, td, tr {
        display: block;
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
        overflow: hidden;
      }

      td {
        border: none;
        border-bottom: 1px solid var(--border-color);
        position: relative;
        padding-left: 50%;
        text-align: right;
      }

      td:before {
        position: absolute;
        top: 0;
        left: 6px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        text-align: left;
        font-weight: bold;
        color: var(--primary-dark);
      }

      td:nth-of-type(1):before { content: "ID:"; }
      td:nth-of-type(2):before { content: "Cliente:"; }
      td:nth-of-type(3):before { content: "Estado:"; }
      td:nth-of-type(4):before { content: "Fecha:"; }
      td:nth-of-type(5):before { content: "Total:"; }
      td:nth-of-type(6):before { content: "Acción:"; }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Panel de Administración de Pedidos</h1>

    <div class="filter-section">
      <label for="filtroEstado">Filtrar por Estado:</label>
      <select id="filtroEstado" onchange="window.location.href='panel_admin.php?estado=' + this.value;">
        <option value="pendiente" <?= $estado_filtro == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
        <option value="confirmado" <?= $estado_filtro == 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
        <option value="entregado" <?= $estado_filtro == 'entregado' ? 'selected' : '' ?>>Entregado</option>
        <option value="negado" <?= $estado_filtro == 'negado' ? 'selected' : '' ?>>Negado</option>
      </select>
    </div>

    <?php if (!empty($pedidos)): ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Cliente</th>
          <th>Estado</th>
          <th>Fecha</th>
          <th>Total (Bs)</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pedidos as $row): ?>
          <tr>
            <td data-label="ID"><?= htmlspecialchars($row['id']) ?></td>
            <td data-label="Cliente"><?= htmlspecialchars($row['cliente']) ?></td>
            <td data-label="Estado">
              <select id="estado_<?= htmlspecialchars($row['id']) ?>" class="estado-select">
                <option value="pendiente" <?= $row['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="confirmado" <?= $row['estado'] == 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                <option value="entregado" <?= $row['estado'] == 'entregado' ? 'selected' : '' ?>>Entregado</option>
                <option value="negado" <?= $row['estado'] == 'negado' ? 'selected' : '' ?>>Negado</option>
              </select>
            </td>
            <td data-label="Fecha"><?= htmlspecialchars($row['fecha']) ?></td>
            <td data-label="Total (Bs)"><?= number_format(floatval($row['total']), 2) ?></td>
            <td data-label="Acción">
              <button class="btn-confirmar" onclick="actualizarEstado(<?= htmlspecialchars($row['id']) ?>)">Actualizar</button>
            </td>
          </tr>
        <?php endforeach; ?>
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
        console.error('Error al enviar la solicitud:', error);
        alert("Hubo un problema al comunicarse con el servidor.");
      });
    }
  </script>
</body>
</html>