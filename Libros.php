<?php
include('Conexion.php');

// 1. Ejecutar la consulta y obtener los resultados
$libros = [];
$resultado = $conexion->query("SELECT id_libro, nombre_libro, autor, descripcion, generos, imagen_ruta FROM vista_libros_generos");
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
    <link rel="stylesheet" href="style/estilos.css">
    <title>Lista de Libros</title>
    <style>
        /* Es mejor práctica usar CSS en lugar de atributos HTML para el estilo */
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        img { display: block; } /* Para evitar espacios extra debajo de la imagen */
    </style>
</head>
<body>
    <h4>LIBROS</h4>
    <table>
        <thead>
            <tr>
                <th>Id</th>
                <th>Nombre</th>
                <th>Autor</th>
                <th>Descripción</th>
                <th>Género</th>
                <th>Portada</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($libros)): ?>
                <?php foreach ($libros as $libro): ?>
                    <tr>
                        <td><?= ($libro['id_libro']) ?></td>
                        <td><?= ($libro['nombre_libro']) ?></td>
                        <td><?= ($libro['autor']) ?></td>
                        <td><?= ($libro['descripcion']) ?></td>
                        <td><?= ($libro['generos']) ?></td>
                        <td><img src="<?= ($libro['imagen_ruta']) ?>" alt="Portada" width="170" height="240"></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No hay registros</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <br><br>
    <button onclick="window.location.href='EditarEliminar.php'">Editar o Eliminar</button>
    <button onclick="window.location.href='insertar.php'">Regresar</button>
</body>
</html>
