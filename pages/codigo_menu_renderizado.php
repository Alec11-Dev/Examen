<?php
session_start();
// Usar require_once para asegurar que los archivos se incluyan una sola vez.
require_once('../ValidationData/conexion.php');
require_once('../ValidationData/funcion.php');

// 1. VERIFICACIÓN DE SESIÓN
// Es mejor verificar si la sesión está vacía y redirigir al principio.
if (empty($_SESSION['email'])) {
    // Usar header location es más limpio que un script de JS.
    header("Location: ../ValidationData/cerrar.php");
    exit(); // Detener la ejecución del script después de redirigir.
}

// Asignar variables de sesión de forma segura.
$email = $_SESSION['email'];
$nameUser = htmlspecialchars($_SESSION['usuario']); // Prevenir XSS
$idUser = $_SESSION['id'];

// Habilitar el reporte de errores de MySQLi para que lance excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 2. MANEJO DE SOLICITUDES POST (INSERTAR Y ACTUALIZAR)
// Centralizar el manejo de formularios al inicio del script.
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    
    // Lógica para insertar un nuevo libro
    if (isset($_POST['insertar'])) {
        $Nombre = $_POST['nombre'];
        $Autor = $_POST['autor'];
        $Descripcion = $_POST['descripcion'];
        $ids_genero = $_POST['id_genero'];

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nombre_archivo = $_FILES['imagen']['name'];
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            $nombre_unico = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $ruta = "../img_upload/" . $nombre_unico;

            if (!file_exists('../img_upload/')) {
                mkdir('../img_upload/', 0755, true); // 0755 es más seguro que 0777
            }

            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
                try {
                    $conexion->begin_transaction();

                    $sql = "INSERT INTO libro (nombre_libro, autor, descripcion, imagen_ruta) VALUES (?, ?, ?, ?)";
                    $stmt = $conexion->prepare($sql);
                    $stmt->bind_param("ssss", $Nombre, $Autor, $Descripcion, $ruta);
                    $stmt->execute();
                    $id_libro = $conexion->insert_id;
                    $stmt->close();

                    $sql2 = "INSERT INTO librogenero (fk_id_genero, fk_id_libro) VALUES (?, ?)";
                    $stmt2 = $conexion->prepare($sql2);
                    foreach ($ids_genero as $id_genero) {
                        $stmt2->bind_param("ii", $id_genero, $id_libro);
                        $stmt2->execute();
                    }
                    $stmt2->close();
                    
                    $conexion->commit();
                } catch (mysqli_sql_exception $e) {
                    $conexion->rollback();
                    if (file_exists($ruta)) {
                        unlink($ruta);
                    }
                    // Podrías guardar el error en una variable de sesión para mostrarlo después de redirigir.
                    $_SESSION['error_message'] = "Error en la base de datos: " . $e->getMessage();
                    if ($e->getCode() == 1062) {
                        $_SESSION['error_message'] = "Error, este libro ya existe.";
                    }
                }
            } else {
                $_SESSION['error_message'] = "Error al mover la imagen. Verifica permisos en img_upload/.";
            }
        }
        header("Location: menu.php");
        exit();
    }

    // Lógica para actualizar un libro
    if (isset($_POST['actualizar'])) {
        $id_libro_act = $_POST['id_libro'];
        $nombre_act = $_POST['nombre'];
        $autor_act = $_POST['autor'];
        $descripcion_act = $_POST['descripcion'];
        $ruta_antigua = $_POST['ruta_antigua'];
        $ids_genero_act = isset($_POST['id_genero']) ? $_POST['id_genero'] : [];
        
        $nueva_ruta = $ruta_antigua;
        
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nombre_archivo = $_FILES['imagen']['name'];
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            $nombre_unico = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $ruta_destino = "../img_upload/" . $nombre_unico;
            
            if (!file_exists('../img_upload/')) {
                mkdir('../img_upload/', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                $nueva_ruta = $ruta_destino;
                if (!empty($ruta_antigua) && file_exists($ruta_antigua) && $ruta_antigua != $nueva_ruta) {
                    unlink($ruta_antigua);
                }
            }
        }
        
        try {
            $conexion->begin_transaction();

            $sql_update = "UPDATE libro SET nombre_libro = ?, autor = ?, descripcion = ?, imagen_ruta = ? WHERE id_libro = ?";
            $stmt_upd = $conexion->prepare($sql_update);
            $stmt_upd->bind_param("ssssi", $nombre_act, $autor_act, $descripcion_act, $nueva_ruta, $id_libro_act);
            $stmt_upd->execute();
            $stmt_upd->close();

            $sql_del_gen = "DELETE FROM librogenero WHERE fk_id_libro = ?";
            $stmt_del = $conexion->prepare($sql_del_gen);
            $stmt_del->bind_param("i", $id_libro_act);
            $stmt_del->execute();
            $stmt_del->close();
            
            if (!empty($ids_genero_act)) {
                $sql_ins_gen = "INSERT INTO librogenero (fk_id_genero, fk_id_libro) VALUES (?, ?)";
                $stmt_ins = $conexion->prepare($sql_ins_gen);
                foreach ($ids_genero_act as $gid) {
                    $stmt_ins->bind_param("ii", $gid, $id_libro_act);
                    $stmt_ins->execute();
                }
                $stmt_ins->close();
            }
            
            $conexion->commit();
        } catch (mysqli_sql_exception $e) {
            $conexion->rollback();
            // Si se subió una nueva imagen pero la BD falló, bórrala.
            if (isset($ruta_destino) && file_exists($ruta_destino) && $nueva_ruta === $ruta_destino) {
                unlink($ruta_destino);
            }
            $_SESSION['error_message'] = "Error al actualizar: " . $e->getMessage();
        }
        
        header("Location: menu.php");
        exit();
    }
}

// 3. OBTENCIÓN DE DATOS PARA LA VISTA
// Obtener todos los géneros una sola vez para usarlos en los formularios.
$todos_los_generos = [];
$res_g = $conexion->query("SELECT id_genero, nombre_genero FROM genero ORDER BY nombre_genero");
if($res_g) {
    $todos_los_generos = $res_g->fetch_all(MYSQLI_ASSOC);
}

// Obtener todos los libros para mostrarlos.
$libros = [];
$query = "SELECT id_libro, nombre_libro, autor, descripcion, generos, imagen_ruta FROM vista_libros_generos";
$resultado_libros = $conexion->query($query);
if ($resultado_libros) {
    $libros = $resultado_libros->fetch_all(MYSQLI_ASSOC);
}

// Si hay un mensaje de error en la sesión, muéstralo y luego límpialo.
$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

?>

    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- 
            NOTA SOBRE RUTAS EN EL HOSTING:
            Los problemas con CSS y JS en un hosting (como InfinityFree) suelen ser por la sensibilidad a mayúsculas/minúsculas
            o por una estructura de directorios diferente.
            Verifica que las rutas y nombres de archivo coincidan EXACTAMENTE con los de tu servidor.
            Una ruta como '../css/bootstrap.min.css' asume que la carpeta 'css' está al mismo nivel que 'pages'.
            Si la estructura es /public_html/Examen/pages/ y /public_html/Examen/css/, la ruta es correcta.
            Si no, ajústala. Considera usar rutas absolutas desde la raíz del sitio, ej: /Examen/css/bootstrap.min.css
        -->
        <link rel="stylesheet" href="../css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/styles.css">
        

        <title>Inicio de <?php echo $nameUser; ?></title>
    </head>

    <body class="bodyHome">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="#" aria-disabled="true">Menú</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/Libros.php">Libros</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../ValidationData/cerrar.php" onclick="return confirm('¿Estás seguro de cerrar sesión?');">Cerrar sesión</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" aria-disabled="true">Rol asignado: <strong></strong></a>
                        </li>
                    </ul>
                    <form class="d-flex justify-content-end align-items-center" role="search" onsubmit="return false;">
                        <input class="form-control me-2" id="searchInput" type="search" placeholder="Search" aria-label="Search" />
                        <button class="btn btn-outline-success" type="button">Buscar</button>
                    </form>
                </div>
            </div>
        </nav>
        <div class="content">
            <h2>¡Bienvenido de nuevo, <?php echo $nameUser; ?>!</h2>
            <p>¿Qué vamos a hacer hoy?</p>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Botón para el modal de agregar libro -->
            <button type="button" class="btn btn-primary btn-agregar" data-bs-toggle="modal" data-bs-target="#modalAgregarLibro">
                Agregar nuevo libro
            </button>

            <!-- Modal para AGREGAR libro -->
            <div class="modal fade" id="modalAgregarLibro" tabindex="-1" aria-labelledby="modalAgregarLibroLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="modalAgregarLibroLabel">Agrega un nuevo libro</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- El action vacío envía el formulario a la misma página (menu.php) -->
                            <form class="registrar" method="post" action="" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre:</label>
                                    <input class="form-control nombre" type="text" id="nombre" name="nombre" required style="text-transform: uppercase;">
                                </div>
                                <div class="mb-3">
                                    <label for="autor" class="form-label">Autor:</label>
                                    <input class="form-control autor" type="text" id="autor" name="autor" required pattern="[A-Za-záéíóúÁÉÍÓÚñÑ ]+">
                                </div>
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción:</label>
                                    <textarea class="form-control descrip" id="descripcion" name="descripcion" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="id_genero" class="form-label">Géneros:</label>
                                    <select class="form-select genero" name="id_genero[]" id="id_genero" multiple required>
                                        <?php foreach ($todos_los_generos as $genero): ?>
                                            <option value="<?php echo $genero['id_genero']; ?>"><?php echo htmlspecialchars($genero['nombre_genero']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Mantén presionada la tecla Ctrl (o Cmd) para seleccionar múltiples.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="imagen" class="form-label">Portada:</label>
                                    <input type="file" name="imagen" class="form-control" accept="image/*" required>
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <button type="button" class="btn btn-info" onclick="window.location.href='insertarGenero.php'">Ingresar nuevo género</button>
                                    <button type="submit" name="insertar" class="btn btn-primary">Guardar registro</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenedor de libros -->
            <div class="row row-cols-1 row-cols-md-3 g-4 mt-4" id="bookContainer">
                <?php foreach ($libros as $libro): ?>
                    <?php
                        // Obtener los IDs de los géneros para este libro específico
                        $generos_libro_actual_ids = [];
                        $q_gen_lib = "SELECT fk_id_genero FROM librogenero WHERE fk_id_libro = " . $libro['id_libro'];
                        $r_gen_lib = $conexion->query($q_gen_lib);
                        if($r_gen_lib){
                            while($gl = $r_gen_lib->fetch_assoc()){
                                $generos_libro_actual_ids[] = $gl['fk_id_genero'];
                            }
                            $r_gen_lib->free();
                        }
                    ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($libro['imagen_ruta']); ?>" class="card-img-top" alt="Portada" style="height: 300px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($libro['nombre_libro']); ?></h5>
                                <p class="card-text"><strong>Autor:</strong> <?php echo htmlspecialchars($libro['autor']); ?></p>
                                <p class="card-text"><small class="text-body-secondary"><?php echo htmlspecialchars($libro['generos']); ?></small></p>
                                <p class="card-text"><?php echo htmlspecialchars($libro['descripcion']); ?></p>
                                
                                <button type="button" class="btn btn-primary btn-agregar" data-bs-toggle="modal" data-bs-target="#modalEditar<?php echo $libro['id_libro']; ?>">
                                    Actualizar
                                </button>
                                
                                <!-- Modal de Edición Específico -->
                                <div class="modal fade" id="modalEditar<?php echo $libro['id_libro']; ?>" tabindex="-1" aria-labelledby="modalEditarLabel<?php echo $libro['id_libro']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="modalEditarLabel<?php echo $libro['id_libro']; ?>">Editar: <?php echo htmlspecialchars($libro['nombre_libro']); ?></h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post" action="" enctype="multipart/form-data">
                                                    <input type="hidden" name="id_libro" value="<?php echo $libro['id_libro']; ?>">
                                                    <input type="hidden" name="ruta_antigua" value="<?php echo htmlspecialchars($libro['imagen_ruta']); ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Nombre:</label>
                                                        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($libro['nombre_libro']); ?>" required style="text-transform: uppercase;">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Autor:</label>
                                                        <input type="text" name="autor" class="form-control" value="<?php echo htmlspecialchars($libro['autor']); ?>" required pattern="[A-Za-záéíóúÁÉÍÓÚñÑ ]+">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Descripción:</label>
                                                        <textarea name="descripcion" class="form-control" required><?php echo htmlspecialchars($libro['descripcion']); ?></textarea>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Géneros:</label>
                                                        <select name="id_genero[]" class="form-select" multiple required>
                                                            <?php foreach($todos_los_generos as $gen): ?>
                                                                <option value="<?php echo $gen['id_genero']; ?>" <?php echo in_array($gen['id_genero'], $generos_libro_actual_ids) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($gen['nombre_genero']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <div class="form-text">Mantén presionada la tecla Ctrl (o Cmd) para seleccionar múltiples.</div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Cambiar imagen (opcional):</label>
                                                        <input type="file" name="imagen" class="form-control" accept="image/*">
                                                        <div class="mt-2 text-center">
                                                            <small>Imagen actual:</small><br>
                                                            <img src="<?php echo htmlspecialchars($libro['imagen_ruta']); ?>" alt="Actual" style="height: 100px;">
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
                <?php endforeach; ?>
            </div>

        </div>

        <?php
        // La función se incluye al principio, solo se llama aquí.
        myfuntion();
        ?>

        <!-- 
            NOTA SOBRE RUTAS EN EL HOSTING:
            Si este script no funciona en tu hosting, es muy probable que la ruta sea incorrecta o haya un problema
            de mayúsculas/minúsculas. Verifica que el archivo 'bootstrap.bundle.min.js' exista en la carpeta 'js'
            y que la carpeta 'js' esté al mismo nivel que 'pages'.
        -->
        <script src="../js/bootstrap.bundle.min.js"></script>
        <script>
            // Este console.log es útil para depurar. Si en la consola del navegador ves "Bootstrap: undefined",
            // significa que el archivo JS de Bootstrap no se cargó correctamente.
            console.log("Bootstrap:", typeof bootstrap);
        </script>
        
        <script>
            // Este script de búsqueda debería funcionar independientemente de Bootstrap.
            document.getElementById('searchInput').addEventListener('input', function() {
                let filter = this.value.toLowerCase();
                let container = document.getElementById('bookContainer');
                let cards = container.getElementsByClassName('col');

                for (let i = 0; i < cards.length; i++) {
                    let title = cards[i].querySelector('.card-title');
                    let author = cards[i].querySelector('.card-text strong');
                    let text = (title ? title.innerText : '') + ' ' + (author ? author.nextSibling.textContent : '');
                    
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
// Cerrar la conexión a la base de datos al final del script.
if (isset($conexion)) {
    mysqli_close($conexion);
}
?>