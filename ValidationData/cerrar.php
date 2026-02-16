<?php 
session_start(); //Traer todas las variables de inicio de sesion
unset($_SESSION['id']); //Eliminar las varibles
session_destroy(); // destruir las variables de inicio de sesión con destroy

// Borra cookies del inicio de sesion desde el path
$cookies = session_get_cookie_params();
setcookie(session_name(), 0, 1, $cookies["path"]);

header("Location: ../pages/index.html"); //Cuidado aqui
?>