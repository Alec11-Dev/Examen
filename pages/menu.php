<?php
session_start();
include('../ValidationData/conexion.php');

if (isset($_SESSION['email']) != "") { //iset parametros para tu inicio de sesion, permite incializar y verificar si una función tiene datos
    $email = $_SESSION['email'];
    $nameUser = $_SESSION['usuario'];
    $idUser = $_SESSION['id_usuario'];
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/styles.css">

        <title>Incio de <?php echo $nameUser ?></title>
    </head>

    <body class="bodyHome">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="" aria-disabled="true">Menu</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/Libros.php">Libros</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../ValidationData/cerrar.php" onclick="return confirm('¿Estas seguro de cerrar sesión?');">Cerrar sesión</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../ValidationData/eliminar_cuenta.php" onclick="return confirm('¿Estás seguro de que deseas eliminar tu cuenta permanentemente? Esta acción no se puede deshacer.');" style="color: #dc3545;">Eliminar cuenta</a>
                        </li>
                    </ul>
                    <form class="d-flex justify-content-end align-items-center" role="search" onsubmit="return false;">
                        <input class="form-control me-2" id="searchInput" type="search" placeholder="Search" aria-label="Search" />
                        <button class="btn btn-outline-success" type="button">Search</button>
                    </form>
                </div>
            </div>
        </nav>
        <div class="content">
            <h2>Bienvenido de nuevo, <?php echo $nameUser ?>!</h2>
            <p>Que vamos a hacer hoy...</p>
            
            <!-- Boton para el modal -->
            <button type="button" class="btn btn-primary btn-agregar" data-bs-toggle="modal" data-bs-target="#exampleModalCenteredScrollable">
                Agregar nuevo libro
            </button>
            <!-- modal -->
            <div class="modal fade" id="exampleModalCenteredScrollable" tabindex="-1" aria-labelledby="exampleModalCenteredScrollableLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalCenteredScrollableLabel">Agrega un nuevo libro</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!--Se agrego el enctype para permitir la subida de archivos -->
                            <form class="registrar" method="post" enctype="multipart/form-data">
                                <?php
                                include("../ValidationData/conexion.php");

                                echo "<label for='nombre'>Nombre:</label>";
                                echo "<input class='nombre' type='text' id='nombre' name='nombre' required style='text-transform: uppercase;'>";
                                echo "<br>";
                                echo "<label for='autor'>Autor:</label>";
                                echo "<input class='autor' type='text' id='autor' name='autor' required pattern='[A-Za-záéíóúÁÉÍÓÚñÑ ]+'>";
                                echo "<br>";
                                echo "<label for='descripcion'>Descripción:</label>";
                                echo "<textarea class='descrip' id='descripcion' name='descripcion' required></textarea>";
                                echo "<br>";
                                echo "<label for='generos'>Generos:</label>";
                                echo "<select class='genero' name='id_genero[]' id='id_genero' multiple required>"; // En este caso, como se permitiran seleccionar multiples generos, se agrega un array [] para guardar los ids 
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
                                echo "<button type='submit' name='insertar' class='btn btn-primary'>Guardar registro</button>";
                                echo "<button type='button' class='btn btn-primary' onclick=\"window.location.href='insertarGenero.php'\">Ingresar un nuevo genero</button>";
                                echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cerrar</button>";
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
                        </div>
                        <div class="modal-footer">
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Lógica para actualizar libro (Procesamiento del formulario de edición)
            if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['actualizar'])) {
                include("../ValidationData/conexion.php");
                
                $id_libro_act = $_POST['id_libro'];
                $nombre_act = $_POST['nombre'];
                $autor_act = $_POST['autor'];
                $descripcion_act = $_POST['descripcion'];
                $ruta_antigua = $_POST['ruta_antigua'];
                $ids_genero_act = isset($_POST['id_genero']) ? $_POST['id_genero'] : [];
                
                $nueva_ruta = $ruta_antigua;
                
                // Manejo de imagen si se sube una nueva
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $nombre_archivo = $_FILES['imagen']['name'];
                    $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
                    $nombre_unico = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                    $ruta_destino = "../img_upload/" . $nombre_unico;
                    
                    if (!file_exists('../img_upload/')) {
                        mkdir('../img_upload/', 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                        $nueva_ruta = $ruta_destino;
                        // Borrar imagen antigua si existe y es diferente
                        if (file_exists($ruta_antigua) && $ruta_antigua != $nueva_ruta) {
                            unlink($ruta_antigua);
                        }
                    }
                }
                
                // Actualizar datos básicos del libro
                $sql_update = "UPDATE libro SET nombre_libro = ?, autor = ?, descripcion = ?, imagen_ruta = ? WHERE id_libro = ?";
                $stmt_upd = $conexion->prepare($sql_update);
                $stmt_upd->bind_param("ssssi", $nombre_act, $autor_act, $descripcion_act, $nueva_ruta, $id_libro_act);
                
                if ($stmt_upd->execute()) {
                    // Actualizar géneros: Primero borramos los existentes para este libro
                    $sql_del_gen = "DELETE FROM librogenero WHERE fk_id_libro = ?";
                    $stmt_del = $conexion->prepare($sql_del_gen);
                    $stmt_del->bind_param("i", $id_libro_act);
                    $stmt_del->execute();
                    $stmt_del->close();
                    
                    // Insertamos los nuevos géneros seleccionados
                    if (!empty($ids_genero_act)) {
                        $sql_ins_gen = "INSERT INTO librogenero (fk_id_genero, fk_id_libro) VALUES (?, ?)";
                        $stmt_ins = $conexion->prepare($sql_ins_gen);
                        foreach ($ids_genero_act as $gid) {
                            $stmt_ins->bind_param("ii", $gid, $id_libro_act);
                            $stmt_ins->execute();
                        }
                        $stmt_ins->close();
                    }
                    // Recargar la página para ver cambios
                    echo "<script>window.location.href='menu.php';</script>";
                }
                $stmt_upd->close();
                mysqli_close($conexion);
            }
            ?>

            <div class="row row-cols-1 row-cols-md-3 g-4 mt-4" id="bookContainer">
                <?php
                include('../ValidationData/conexion.php');
                
                // Obtener todos los géneros una sola vez para usarlos en los selects de los modales
                $todos_los_generos = [];
                $res_g = $conexion->query("SELECT * FROM genero");
                if($res_g) {
                    while($row_g = $res_g->fetch_assoc()) {
                        $todos_los_generos[] = $row_g;
                    }
                }

                $query = "SELECT * FROM vista_libros_generos";
                $resultado = $conexion->query($query);
                if ($resultado) {
                    while ($libro = $resultado->fetch_assoc()) {
                        // Obtener los IDs de los géneros actuales de este libro específico
                        $generos_libro_actual = [];
                        $q_gen_lib = "SELECT fk_id_genero FROM librogenero WHERE fk_id_libro = " . $libro['id_libro'];
                        $r_gen_lib = $conexion->query($q_gen_lib);
                        if($r_gen_lib){
                            while($gl = $r_gen_lib->fetch_assoc()){
                                $generos_libro_actual[] = $gl['fk_id_genero'];
                            }
                        }
                ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo $libro['imagen_ruta']; ?>" class="card-img-top" alt="Portada" style="height: 300px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $libro['nombre_libro']; ?></h5>
                                <p class="card-text"><strong>Autor:</strong> <?php echo $libro['autor']; ?></p>
                                <p class="card-text"><small class="text-body-secondary"><?php echo $libro['generos']; ?></small></p>
                                <p class="card-text"><?php echo $libro['descripcion']; ?></p>
                                
                                <!-- Botón que abre el modal específico de este libro -->
                                <button type="button" class="btn btn-primary btn-agregar" data-bs-toggle="modal" data-bs-target="#modalEditar<?php echo $libro['id_libro']; ?>">
                                    Actualizar
                                </button>
                                
                                <!-- Modal de Edición Específico -->
                                <div class="modal fade" id="modalEditar<?php echo $libro['id_libro']; ?>" tabindex="-1" aria-labelledby="modalEditarLabel<?php echo $libro['id_libro']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="modalEditarLabel<?php echo $libro['id_libro']; ?>">Editar: <?php echo $libro['nombre_libro']; ?></h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post" enctype="multipart/form-data">
                                                    <input type="hidden" name="id_libro" value="<?php echo $libro['id_libro']; ?>">
                                                    <input type="hidden" name="ruta_antigua" value="<?php echo $libro['imagen_ruta']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Nombre:</label>
                                                        <input type="text" name="nombre" class="form-control" value="<?php echo $libro['nombre_libro']; ?>" required style="text-transform: uppercase;">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Autor:</label>
                                                        <input type="text" name="autor" class="form-control" value="<?php echo $libro['autor']; ?>" required pattern="[A-Za-záéíóúÁÉÍÓÚñÑ ]+">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Descripción:</label>
                                                        <textarea name="descripcion" class="form-control" required><?php echo $libro['descripcion']; ?></textarea>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Géneros:</label>
                                                        <select name="id_genero[]" class="form-select" multiple required>
                                                            <?php foreach($todos_los_generos as $gen): ?>
                                                                <option value="<?php echo $gen['id_genero']; ?>" 
                                                                    <?php echo in_array($gen['id_genero'], $generos_libro_actual) ? 'selected' : ''; ?>>
                                                                    <?php echo $gen['nombre_genero']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Mantén presionada la tecla Ctrl (o Cmd) para seleccionar múltiples.</div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Imagen (opcional):</label>
                                                        <input type="file" name="imagen" class="form-control" accept="image/*">
                                                        <div class="mt-2 text-center">
                                                            <small>Imagen actual:</small><br>
                                                            <img src="<?php echo $libro['imagen_ruta']; ?>" alt="Actual" style="height: 100px;">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" name="actualizar" class="btn btn-primary">Guardar Cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                }
                ?>
            </div>

        </div>

        <?php
        include('../ValidationData/funcion.php');
        myfuntion();
        ?>
        <script src="../js/bootstrap.bundle.min.js"></script>
        
        <script>
            document.getElementById('searchInput').addEventListener('input', function() {
                let filter = this.value.toLowerCase();
                let container = document.getElementById('bookContainer');
                let cards = container.getElementsByClassName('col');

                for (let i = 0; i < cards.length; i++) {
                    let text = cards[i].textContent || cards[i].innerText;
                    if (text.toLowerCase().indexOf(filter) > -1) {
                        cards[i].style.display = "";
                    } else {
                        cards[i].style.display = "none";
                    }
                }
            });
        </script>
    </body>

    </html>

<?php
} else {
?>
    <script type="text/javascript">
        location.href = "../ValidationData/cerrar.php";
    </script>
<?php }
?>