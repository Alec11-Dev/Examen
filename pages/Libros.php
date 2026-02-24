<?php
session_start();
include('../ValidationData/conexion.php');
if (isset($_SESSION['email']) != "") {
    $nameUser = $_SESSION['usuario'];
    $email = $_SESSION['email'];
    $idUser = $_SESSION['id_usuario'];

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

    <body class="bodyHome d-flex flex-column" style="min-height: calc(100vh - 120px);">
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
                            <a class="nav-link" href="../ValidationData/eliminar_cuenta.php" onclick="return confirm('¿Estás seguro de que deseas eliminar tu cuenta permanentemente? Esta acción no se puede deshacer.');" style="color: #dc3545;">Eliminar cuenta</a>
                        </li>
                    </ul>
                    <form class="d-flex justify-content-end align-items-center" role="search" onsubmit="return false;">
                        <input class="form-control me-2" id="searchInput" type="search" placeholder="Search" aria-label="Search" />
                        <button class="btn btn-outline-success" type="button">Buscar</button>
                    </form>
                </div>
            </div>
        </nav>

        <div class="content flex-grow-1">
            <h2>Bienvenido de nuevo, <?php echo $nameUser ?>!</h2>
            <p>Que vamos a hacer hoy...</p>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
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
                                    <td><img src="<?= ($libro['imagen_ruta']) ?>" alt="Portada" class="img-fluid rounded" style="max-width: 100px; height: auto;"></td>
                                    <td>
                                        <form action="" method="POST"><input type="hidden" name="id" value="<?= ($libro['id_libro']) ?>"><button class="btn btn-outline-success" type="submit" name="eliminar">Eliminar</button></form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No hay registros</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer con enlaces a Términos y Políticas -->
        <footer class="text-center mt-auto mb-4">
            <hr>
            <p class="text-muted">
                &copy; <?php echo date('Y'); ?> Biblioteca. Todos los derechos reservados.<br>
                <a href="terminos.html" class="text-decoration-none" target="_blank">Términos y Condiciones</a> |
                <a href="politicas.html" class="text-decoration-none" target="_blank">Políticas de Privacidad</a>
            </p>
        </footer>
        <script src="../js/bootstrap.bundle.min.js"></script>
        <script>
            // Lógica para el buscador de la tabla
            document.getElementById('searchInput').addEventListener('input', function() {
                let filter = this.value.toLowerCase();
                let rows = document.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    let text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
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
<?php
}
