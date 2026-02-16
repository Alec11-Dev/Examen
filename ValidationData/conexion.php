<?php
$servidor = "localhost";
$usuario = "root";
$contrasenya = "";
$basededatos = "biblioteca";

$conexion = mysqli_connect($servidor, $usuario, $contrasenya) or die("No se puede conectar con el servidor");
$bd = mysqli_select_db($conexion, $basededatos) or die("Oops, no se pudo conectar a la base de datos");
?>