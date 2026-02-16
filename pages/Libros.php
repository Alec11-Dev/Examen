<?php
session_start();
include('../ValidationData/conexion.php');
if(isset($_SESSION['email']) != ""){
    $nameUser = $_SESSION['usuario'];
    $email = $_SESSION['email'];

    // 1. Ejecutar la consulta y obtener los resultados
    $libros = [];
    $resultado = $conexion->query("SELECT id_libro ,nombre_libro, autor, descripcion, generos, imagen_ruta FROM vista_libros_generos");
    if ($resultado) {
        // 2. Guardar todos los resultados en un array
        $libros = $resultado->fetch_all(MYSQLI_ASSOC);
        $resultado->free();
    }
    $conexion->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">

    <title>Lista de Libros</title>
</head>

<body class="bodyHome">
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/Libros.php">Libros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../ValidationData/cerrar.php" onclick="return confirm('¿Estas seguro de cerrar sesión?');">Cerrar sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-disabled="true">Rol asignado: <strong></strong></a>
                    </li>
                </ul>
                <form class="d-flex justify-content-end align-items-center" role="search">
                    <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" />
                    <button class="btn btn-outline-success" type="submit">Search</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="content">
        <h2>Bienvenido de nuevo, <?php echo $nameUser ?>!</h2>
        <p>Que vamos a hacer hoy...</p>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Autor</th>
                    <th>Descripción</th>
                    <th>Género</th>
                    <th>Portada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($libros)): ?>
                    <?php foreach ($libros as $libro): ?>
                        <tr>
                            <td><?= ($libro['nombre_libro']) ?></td>
                            <td><?= ($libro['autor']) ?></td>
                            <td><?= ($libro['descripcion']) ?></td>
                            <td><?= ($libro['generos']) ?></td>
                            <td><img src="<?= ($libro['imagen_ruta']) ?>" alt="Portada" width="180px" height="240px"></td>
                            <td><form action="" method="POST"><input type="hidden" name="id" value="<?= ($libro['id_libro']) ?>"><button class="btn btn-outline-success" type="submit" name="eliminar">Eliminar</button></form></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No hay registros</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <br>
    </div>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
} else{
?>
    <script type="text/javascript">
        location.href = "../ValidationData/cerrar.php";
    </script>
<?php 
}
?>

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