<?php
include('ValidationData/conexion.php');

// Proceso para actualizar el libro
if (isset($_POST['actualizar'])) {
    $id = $_POST['id_libro'];
    $nombre_libro = $_POST['nombre_libro'];
    $autor = $_POST['autor'];
    $descripcion = $_POST['descripcion'];
    $ruta_antigua = $_POST['ruta_antigua'] ?? '';

    $nueva_ruta = $ruta_antigua;
    $subio_nueva = false;

    // Manejo de archivo si se subió uno nuevo
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
        $tipo = $_FILES['imagen']['type'];
        $tamano = $_FILES['imagen']['size'];
        $tamano_maximo = 5 * 1024 * 1024; // 5MB

        if (!in_array($tipo, $tipos_permitidos)) {
            echo '<p style="color: red;">Tipo de archivo no permitido. Solo JPEG, PNG o GIF.</p>';
        } elseif ($tamano > $tamano_maximo) {
            echo '<p style="color: red;">El archivo es demasiado grande. Máximo 5MB.</p>';
        } else {
            $nombre_archivo = $_FILES['imagen']['name'];
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            $nombre_unico = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $nueva_ruta = "img_upload/" . $nombre_unico;

            if (!file_exists('img_upload/')) {
                mkdir('img_upload/', 0777, true);
            }

            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $nueva_ruta)) {
                $subio_nueva = true;
                if (!empty($ruta_antigua) && file_exists($ruta_antigua)) {
                    unlink($ruta_antigua);
                }
            } else {
                echo '<p style="color: red;">Error al mover la imagen. Verifica permisos en img_upload/.</p>';
                $nueva_ruta = $ruta_antigua;
            }
        }
    }

    // Actualiza los campos de texto y la ruta de la imagen
    $sql = "UPDATE libro SET nombre_libro = ?, autor = ?, descripcion = ?, imagen_ruta = ? WHERE id_libro = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssi", $nombre_libro, $autor, $descripcion, $nueva_ruta, $id);
        if ($stmt->execute()) {
            echo "<p style='color:green;'>Libro actualizado correctamente. Serás redirigido en 3 segundos.</p>";
            // Redirige de vuelta a la página de búsqueda/edición principal
            header("refresh:3;url=EditarEliminar.php");
            exit;
        } else {
            echo "<p style='color: red;'>Error al actualizar: " . $stmt->error . "</p>";
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
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <title>Editar Libro</title>
</head>

<body>

    <h2>Editar Libro</h2>

    <?php
    // Cargar datos del libro para editar
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        $sql = "SELECT * FROM vista_libros_generos WHERE id_libro = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if (mysqli_num_rows($resultado) > 0) {
            $fila = mysqli_fetch_assoc($resultado);
            $stmt->close();

            // Usamos htmlspecialchars para prevenir XSS
            echo "
            <form method='POST' action='FormularioEditar.php' enctype='multipart/form-data'>
                <input type='hidden' name='id_libro' value='" . htmlspecialchars($fila['id_libro']) . "'>
                <input type='hidden' name='ruta_antigua' value='" . htmlspecialchars($fila['imagen_ruta']) . "'>
                
                <table border='1' cellpadding='8'>
                    <tr>
                        <th>Campo</th>
                        <th>Valor</th>
                    </tr>
                    <tr>
                        <td>ID</td>
                        <td>" . htmlspecialchars($fila['id_libro']) . "</td>
                    </tr>
                    <tr>
                        <td>Nombre del libro</td>
                        <td><input type='text' name='nombre_libro' value='" . htmlspecialchars($fila['nombre_libro']) . "' required></td> 
                    </tr>
                    <tr>
                        <td>Autor</td>
                        <td><input type='text' name='autor' value='" . htmlspecialchars($fila['autor']) . "' required pattern='[A-Za-záéíóúÁÉÍÓÚñÑ ]+'></td>
                    </tr>
                    <tr>
                        <td>Descripción</td>
                        <td><textarea name='descripcion' required>" . htmlspecialchars($fila['descripcion']) . "</textarea></td>
                    </tr>
                    <tr>
                        <td>Género(s)</td>
                        <td>" . htmlspecialchars($fila['generos']) . " <i>(No editable aquí)</i></td>
                    </tr>
                    <tr>
                        <td>Portada Actual</td>
                        <td><img src='" . htmlspecialchars($fila['imagen_ruta']) . "' alt='Portada' width='170' height='240'></td>
                    </tr>
                    <tr>
                        <td>Cambiar imagen (opcional)</td>
                        <td><input type='file' name='imagen' accept='image/*'></td>
                    </tr>
                </table>
                <br>
                <button type='submit' name='actualizar'>Guardar Cambios</button>
            </form>
            ";
        } else {
            echo "<p>No se encontró un libro con ese ID.</p>";
        }
    } else {
        echo "<p>No se ha especificado un ID de libro para editar.</p>";
    }
    ?>

    <br><br>
    <button onclick="window.location.href='EditarEliminar.php'">Volver a la Búsqueda</button>

</body>
</html>
<?php
mysqli_close($conexion);
?>