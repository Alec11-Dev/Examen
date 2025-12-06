<?php
include('Conexion.php');

// Proceso para eliminar el libro
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];

    // Prepared para seguridad: Borra géneros primero
    $sql2 = "DELETE FROM librogenero WHERE fk_id_libro = ?";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("i", $id);
    $resultado2 = $stmt2->execute();
    $stmt2->close();

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

// Proceso para actualizar el libro
if (isset($_POST['actualizar'])) {
    $id = $_POST['id_libro'];
    $nombre_libro = $_POST['nombre_libro'];
    $autor = $_POST['autor'];
    $descripcion = $_POST['descripcion'];
    $ruta_antigua = $_POST['ruta_antigua'] ?? '';  // Permite guardar la ruta antigua de la imagen

    $nueva_ruta = $ruta_antigua;  // Por defecto, mantiene la antigua
    $subio_nueva = false; // Indica si se subió una nueva imagen

    // Manejo de archivo si se subió uno nuevo
    // En este caso, es opcional cambiar la imagen. Si se sube, procesa; si no, mantiene la antigua.
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        
        // Validaciones básicas (agregué para robustez)
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif']; // Define los tipos permitidos de imagen
        $tipo = $_FILES['imagen']['type']; // Se obtiene el tipo MIME
        $tamano = $_FILES['imagen']['size']; // Tamaño en bytes
        $tamano_maximo = 5 * 1024 * 1024;  // El tamaño máximo permitido (5MB)

        // Si el tipo o tamaño no son válidos, muestra error
        if (!in_array($tipo, $tipos_permitidos)) { 
            echo '<p style="color: red;">Tipo de archivo no permitido. Solo JPEG o PNG.</p>';
        } elseif ($tamano > $tamano_maximo) {
            echo '<p style="color: red;">El archivo es demasiado grande. Máximo 5MB.</p>';
        } else {
            // Ahora se genera un nombre único para evitar colisione con otras imágenes
            $nombre_archivo = $_FILES['imagen']['name']; // Nombre original
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION); // Se agrega la extensión
            $nombre_unico = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension; // Nombre único para evitar colisiones formado por timestamp + random
            $nueva_ruta = "img_upload/" . $nombre_unico; // Se define la ruta para guardar la imagen

            // Esto nos permite evitar errores si el directorio no existe
            // Si no existe, lo crea con permisos 0777 (lectura, escritura, ejecución para todos)
            if (!file_exists('img_upload/')) {
                mkdir('img_upload/', 0777, true);
            }

            // Se usa in if para mover el archivo subido a la nueva ruta
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $nueva_ruta)) {
                $subio_nueva = true; // Indica que se subió una nueva imagen

                // En este caso si se subió una nueva imagen, borra la antigua
                if (!empty($ruta_antigua) && file_exists($ruta_antigua)) {
                    unlink($ruta_antigua);
                }
            } else {
                echo '<p style="color: red;">Error al mover la imagen. Verifica permisos en img_upload/.</p>';
                $nueva_ruta = $ruta_antigua;  // Fallback a la antigua, si falla el move se mantiene la ultima imagen que se tenia
            }
        }
    }

    // En esta parte ya se actualiza los campos de texto y la ruta de la imagen
    $sql = "UPDATE libro SET nombre_libro = ?, autor = ?, descripcion = ?, imagen_ruta = ? WHERE id_libro = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssi", $nombre_libro, $autor, $descripcion, $nueva_ruta, $id);  // Aqui el bind_param recibira el tipo de datos que se va a actualizar
        // Se ejecuta el update y se verifica si fue exitoso
        if ($stmt->execute()) {
            echo "<p style='color:green;'>Libro actualizado correctamente</p>";
        } else {
            echo "<p style='color: red;'>Error al actualizar: " . $stmt->error . "</p>";
            // Si se subió una nueva imagen pero falló el update, borra la nueva para evitar basura
            if ($subio_nueva) {
                unlink($nueva_ruta);
            }
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>Error al preparar UPDATE: " . $conexion->error . "</p>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/estilos.css">
    <title>Formulario</title>
</head>

<body>

    <!-- Formulario para buscar -->
    <form action="" method="POST">
        <label>Buscar por ID:</label>
        <input type="number" min="1" name="idBuscar" required>
        <button type="submit">Buscar</button>
    </form>

    <?php
    /* Busca por id */
    if (isset($_POST['idBuscar'])) {
        $id = $_POST['idBuscar'];

        $sql = "SELECT * FROM vista_libros_generos WHERE id_libro = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if (mysqli_num_rows($resultado) > 0) {
            $fila = mysqli_fetch_assoc($resultado);
            $stmt->close();

            // Se agrego htmlspecialchars para evitar inyecciones XSS en los campos editables
            echo "
            <!--Actualizar(formulario)-->
            <form method='POST' enctype='multipart/form-data'>
                <input type='hidden' name='id_libro' value='" . $fila['id_libro'] . "'>
                <input type='hidden' name='ruta_antigua' value='" . ($fila['imagen_ruta']) . "'>
                <table border='1' cellpadding='8'>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del libro</th>
                        <th>Autor</th>
                        <th>Descripción</th>
                        <th>Género</th>
                        <th>Portada</th>
                    </tr>
                    <tr>
                        <td>" . $fila['id_libro'] . "</td>
                        <td><input type='text' name='nombre_libro' value='" . ($fila['nombre_libro']) . "' required></td> 
                        <td><input type='text' name='autor' value='" . ($fila['autor']) . "' required pattern='[A-Za-záéíóúÁÉÍÓÚñÑ ]+'></td>
                        <td><textarea name='descripcion' required>" . ($fila['descripcion']) . "</textarea></td>
                        <td>" . ($fila['generos']) . "</td>  <!-- No editable por ahora -->
                        <td>
                            <img src='" . ($fila['imagen_ruta']) . "' alt='Portada' width='170' height='240'><br>
                            <label>Cambiar imagen (opcional):</label><br>
                            <input type='file' name='imagen' accept='image/*'>
                        </td>
                    </tr>
                </table>
                <br>
                <button type='submit' name='actualizar'>Actualizar</button>
            </form>

            <!--Eliminar(formulario)-->
            <form action='' method='POST'>
                <input type='hidden' name='id' value='" . $fila['id_libro'] . "'>
                <button type='submit' name='eliminar' onclick='return confirm(\"¿Seguro que quieres eliminar?\")'>Eliminar</button>
            </form>
            ";
        } else {
            echo "<p>No se encontró un registro con ese ID.</p>";
        }
    }
    ?>

    <br><br>
    <button onclick="window.location.href='Libros.php'">Regresar</button>

</body>

</html>

<?php
mysqli_close($conexion);  // Cierra al final
?>