<!DOCTYPE html>
<html>

<head>
    <title>Insertar Libro</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>

<body class="container mt-5">
    <h1 class="mb-4">Insertar Libro</h1>
    <!--Se agrego el enctype para permitir la subida de archivos -->
    <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
        <?php
        include("../ValidationData/conexion.php");

        echo "<div class='mb-3'>";
        echo "<label for='nombre' class='form-label'>Nombre:</label>";
        echo "<input type='text' class='form-control' id='nombre' name='nombre' required style='text-transform: uppercase;'>";
        echo "</div>";
        
        echo "<div class='mb-3'>";
        echo "<label for='autor' class='form-label'>Autor:</label>";
        echo "<input type='text' class='form-control' id='autor' name='autor' required pattern='[A-Za-záéíóúÁÉÍÓÚñÑ ]+'>";
        echo "</div>";
        
        echo "<div class='mb-3'>";
        echo "<label for='descripcion' class='form-label'>Descripción:</label>";
        echo "<textarea class='form-control' id='descripcion' name='descripcion' required></textarea>";
        echo "</div>";
        
        echo "<div class='mb-3'>";
        echo "<label for='id_genero' class='form-label'>Géneros:</label>";
        echo "<select class='form-select' name='id_genero[]' id='id_genero' multiple required>"; 
        $sql = "SELECT id_genero, nombre_genero FROM genero"; 
        $result = mysqli_query($conexion, $sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            echo "<option value='" . $row['id_genero'] . "'>" . $row['nombre_genero'] . "</option>";
        }
        echo "</select>";
        echo "<div class='form-text'>Usa Ctrl para seleccionar múltiples.</div>";
        echo "</div>";
        
        echo "<div class='mb-3'>";
        echo "<label class='form-label'>Imagen:</label>";
        echo "<input type='file' class='form-control' name='imagen' accept='image/*' required>";
        echo "</div>";
        
        echo "<div class='d-grid gap-2 d-md-block'>";
        echo "<input type='submit' name='insertar' value='Insertar' class='btn btn-primary me-2'>";
        echo "<button type='button' class='btn btn-secondary me-2' onclick=\"window.location.href='Libros.php'\">Volver al inicio</button>";
        echo "<button type='button' class='btn btn-info' onclick=\"window.location.href='insertarGenero.php'\">Ingresar un nuevo genero</button>";
        echo "</div>";
        ?>
    </form>


    <?php
    // Se lleva acabo el proceso de 'insertar' atraves del metodo POST
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['insertar'])) {
        include("../ValidationData/conexion.php");

        // Habilitar el reporte de errores de MySQLi para que lance excepciones
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        // Recibe los datos del formulario para insertarlos en la BD
        $Nombre = $_POST['nombre'];
        $Autor = $_POST['autor'];
        $Descripcion = $_POST['descripcion'];
        $ids_genero = $_POST['id_genero'];  // Recordemos que esto es un array

        // Manejo de archivo si se subió uno nuevo
        // En este caso, es opcional cambiar la imagen. Si se sube, procesa; si no, mantiene la antigua.
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nombre_archivo = $_FILES['imagen']['name']; // Nombre original
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION); // Obtiene la extension
            // Nombre único para evitar sobrescribir (timestamp + rand)
            $nombre_unico = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension; // Nombre único para evitar colisiones formado por timestamp + random
            $ruta = "../img_upload/" . $nombre_unico; // Ruta del destino de la imagen

            // Esto nos permite evitar errores si el directorio no existe
            // Si no existe, lo crea con permisos 0777 (lectura, escritura, ejecución para todos)
            if (!file_exists('../img_upload/')) {
                mkdir('../img_upload/', 0777, true);
            }

            // Se usa in if para mover el archivo subido a la nueva ruta
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
                try {
                    // Inserta en tabla 'libro'
                    $sql = "INSERT INTO libro (nombre_libro, autor, descripcion, imagen_ruta) VALUES (?, ?, ?, ?)";
                    $stmt = $conexion->prepare($sql);
                    $stmt->bind_param("ssss", $Nombre, $Autor, $Descripcion, $ruta);
                    $stmt->execute();

                    $id_libro = $conexion->insert_id;
                    $stmt->close();

                    // Ahora se insertan las relaciones en 'librogenero'
                    $sql2 = "INSERT INTO librogenero (fk_id_genero, fk_id_libro) VALUES (?, ?)";
                    $stmt2 = $conexion->prepare($sql2);
                    foreach ($ids_genero as $id_genero) {
                        $stmt2->bind_param("ii", $id_genero, $id_libro);
                        $stmt2->execute();
                    }
                    echo '<p style="color: green;">Registro creado correctamente. ID del libro: ' . $id_libro . '</p>';
                    $stmt2->close();
                } catch (mysqli_sql_exception $e) {
                    // El unlink borra el archivo si falla la insercion y evitamos imagenes que no tienen libro asociado
                    unlink($ruta);
                    // Se comprueba si el error es por una entrada duplicada (código 1062)
                    if ($e->getCode() == 1062) {
                        echo '<script>alert("Error, este libro ya existe.");</script>';
                    } else {
                        // Para cualquier otro error de base de datos, muestra el mensaje de la excepción
                        echo '<p style="color: red;">Error en la base de datos: ' . $e->getMessage() . '</p>';
                    }
                }
            } else {
                echo '<p style="color: red;">Error al mover la imagen. Verifica permisos en img_upload/.</p>';
            }
        }
        mysqli_close($conexion);  // Cierra solo después del processing
    }
    ?>


</body>

</html>