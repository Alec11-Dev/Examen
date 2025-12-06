<!DOCTYPE html>
<html>

<head>
    <title>Insertar</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/estilos.css">
</head>

<body>
    <h1>Insertar</h1>
    <!--Se agrego el enctype para permitir la subida de archivos -->
    <form method="post" enctype="multipart/form-data">
        <?php
        include("conexion.php");

        echo "<label for='nombre'>Nombre:</label>";
        echo "<input type='text' id='nombre' name='nombre' required style='text-transform: uppercase;'>";
        echo "<br>";
        echo "<label for='autor'>Autor:</label>";
        echo "<input type='text' id='autor' name='autor' required pattern='[A-Za-záéíóúÁÉÍÓÚñÑ ]+'>";
        echo "<br>";
        echo "<label for='descripcion'>Descripción:</label>";
        echo "<textarea id='descripcion' name='descripcion' required></textarea>";
        echo "<br>";
        echo "<select name='id_genero[]' id='id_genero' multiple required>"; // En este caso, como se permitiran seleccionar multiples generos, se agrega un array [] para guardar los ids 
        $sql = "SELECT id_genero, nombre_genero FROM genero"; 
        $result = mysqli_query($conexion, $sql);
        // Con el while se generan las opciones del select, una por cada genero en la tabla
        while ($row = mysqli_fetch_assoc($result)) { 
            // En el value se guarda el id del genero para posteriormente usarlo a la hora insertar en la tabla librogenero
            echo "<option value='" . $row['id_genero'] . "'>" . $row['nombre_genero'] . "</option>";
        }
        echo "</select>";
        echo "<br>";
        echo "<input type='file' name='imagen' accept='image/*' required>";
        echo "<br>";
        echo "<input type='submit' name='insertar' value='Insertar'>";
        echo "<button type='button' onclick=\"window.location.href='Libros.php'\">Volver al inicio</button>";
        ?>
    </form>


    <?php
    // Se lleva acabo el proceso de 'insertar' atraves del metodo POST
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['insertar'])) {
        include("conexion.php");

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
            $ruta = "img_upload/" . $nombre_unico; // Ruta del destino de la imagen

            // Esto nos permite evitar errores si el directorio no existe
            // Si no existe, lo crea con permisos 0777 (lectura, escritura, ejecución para todos)
            if (!file_exists('img_upload/')) {
                mkdir('img_upload/', 0777, true);
            }

            // Se usa in if para mover el archivo subido a la nueva ruta
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
                // Inserta en tabla 'libro'
                $sql = "INSERT INTO libro (nombre_libro, autor, descripcion, imagen_ruta) VALUES (?, ?, ?, ?)";
                if ($stmt = $conexion->prepare($sql)) {
                    $stmt->bind_param("ssss", $Nombre, $Autor, $Descripcion, $ruta); // Vincula los parametros recibidos del formulario con el bind_param
                    // Ejecuta la insercion y verifica si fue exitoso
                    if ($stmt->execute()) {
                        $id_libro = $conexion->insert_id;
                        $stmt->close();

                        // Ahora se insertan las relaciones en 'librogenero'
                        // Aqui se usara principalmente el array $ids_genero para insertar multiples filas y para lograrlo usamos un foreach
                        $sql2 = "INSERT INTO librogenero (fk_id_genero, fk_id_libro) VALUES (?, ?)"; // Consulta para insertar en librogenero
                        if ($stmt2 = $conexion->prepare($sql2)) { // Prepara la consulta
                            foreach ($ids_genero as $id_genero) { // Recorre cada id del genero seleccionado y almacenado en el array 
                                $stmt2->bind_param("ii", $id_genero, $id_libro); // Vincula los parametros: id del genero y id del libro recien insertado
                                $stmt2->execute();
                            }
                            echo '<p style="color: green;">Registro creado correctamente. ID del libro: ' . $id_libro . '</p>';
                            $stmt2->close();
                        } else {
                            echo '<p style="color: red;">Error al preparar inserción de géneros: ' . $conexion->error . '</p>';
                        }
                    } else {
                        echo '<p style="color: red;">Error al insertar libro: ' . $stmt->error . '</p>';
                        // El unlink borra el archivo si falla la insercion y evitamos imagenes que no tienen libro asociado
                        unlink($ruta);
                    }
                } else {
                    echo '<p style="color: red;">Error al preparar inserción: ' . $conexion->error . '</p>';
                    // Se vuelve a usar el unlink para borrar la imagen en caso de error por parte del prepare
                    unlink($ruta);
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