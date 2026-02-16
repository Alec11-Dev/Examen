<?php
include('../ValidationData/conexion.php');

// Proceso para eliminar el libro (se activa desde la tabla de resultados)
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    
    // Prepared para seguridad: Borra géneros primero
    $sql2 = "DELETE FROM librogenero WHERE fk_id_libro = ?";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("i", $id);
    $resultado2 = $stmt2->execute();
    $stmt2->close();

    // Luego borra el libro
    $sql = "DELETE FROM libro WHERE id_libro = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $resultado = $stmt->execute();
    $stmt->close();
    
    if ($resultado && $resultado2) {
        // Opcional: Borra imagen del disco (consulta ruta antes de delete, pero por simplicidad, asume se maneja manual)
        echo "<p style='color:green;'>Libro eliminado correctamente.</p>";
    } else {
        echo "<p style='color:red;'>Error al eliminar: " . $conexion->error . "</p>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <title>Formulario</title>
</head>

<body>

    <!-- Formulario para buscar -->
    <form action="" method="POST">
        <label>Buscar por nombre del libro:</label>
        <input type="text" name="nombreBuscar" required>
        <button type="submit">Buscar</button>
    </form>

    <?php
    // Proceso para buscar libros por nombre y mostrar una tabla de resultados
    if (isset($_POST['nombreBuscar'])) {
        $nombre = "%" . $_POST['nombreBuscar'] . "%";

        $sql = "SELECT l.id_libro, l.nombre_libro, l.autor, l.descripcion, l.imagen_ruta, GROUP_CONCAT(g.nombre_genero SEPARATOR ', ') AS generos FROM libro l LEFT JOIN librogenero lg ON l.id_libro = lg.fk_id_libro LEFT JOIN genero g ON lg.fk_id_genero = g.id_genero WHERE l.nombre_libro LIKE ? GROUP BY l.id_libro, l.nombre_libro, l.autor, l.descripcion, l.imagen_ruta ORDER BY l.id_libro ASC";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if (mysqli_num_rows($resultado) > 0) {
            echo "
            <h2>Resultados de la búsqueda</h2>
            <table border='1' cellpadding='8'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Autor</th>
                        <th>Género(s)</th>
                        <th>Portada</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>";
            
            while ($fila = mysqli_fetch_assoc($resultado)) {
                echo "
                <tr>
                    <td>" . htmlspecialchars($fila['id_libro']) . "</td>
                    <td>" . htmlspecialchars($fila['nombre_libro']) . "</td>
                    <td>" . htmlspecialchars($fila['autor']) . "</td>
                    <td>" . htmlspecialchars($fila['generos']) . "</td>
                    <td><img src='" . htmlspecialchars($fila['imagen_ruta']) . "' alt='Portada' width='85' height='120'></td>
                    <td>
                        <!-- Formulario para Editar -->
                        <form action='FormularioEditar.php' method='GET' style='display:inline;'>
                            <input type='hidden' name='id' value='" . $fila['id_libro'] . "'>
                            <button type='submit'>Actualizar</button>
                        </form>
                        <!-- Formulario para Eliminar -->
                        <form action='' method='POST' style='display:inline;'>
                            <input type='hidden' name='id' value='" . $fila['id_libro'] . "'>
                            <button type='submit' name='eliminar' onclick='return confirm(\"¿Seguro que quieres eliminar este libro?\")'>Eliminar</button>
                        </form>
                    </td>
                </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No se encontraron libros con ese nombre.</p>";
        }
        $stmt->close();
    }
    ?>

    <br><br>
    <button onclick="window.location.href='Libros.php'">Regresar</button>

</body>

</html>

<?php
mysqli_close($conexion);  // Cierra al final
?>