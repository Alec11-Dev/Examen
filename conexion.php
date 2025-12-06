<?php
$servidor = "localhost";
$usuario = "root";
$contrasenya = "";
$basededatos = "biblioteca";

$conexion = mysqli_connect($servidor, $usuario, $contrasenya, $basededatos);

if (!$conexion) {
    die("Error al conectar a la base de datos: " . mysqli_connect_error());
} else {
    //echo "Conectado a la base de datos";
}
?>