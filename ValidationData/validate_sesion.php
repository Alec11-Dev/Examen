<?php 
error_reporting();
session_start();

include('conexion.php');
$email = filter_var($_REQUEST['email'], FILTER_SANITIZE_EMAIL);
if(filter_var($email, FILTER_SANITIZE_EMAIL)){
    $correo = ($_REQUEST['email']);
}

$clave = trim($_REQUEST['password']);

$sqlverifica = ("SELECT * FROM usuarios WHERE email='".$correo."' AND password = '".$clave."' ");
$QueryResult = mysqli_query($conexion, $sqlverifica);

if($row=mysqli_fetch_assoc($QueryResult)){

    $_SESSION['email'] = $row['email'];
    $_SESSION['id_usuario'] = $row['id_usuario'];
    $_SESSION['usuario'] = $row['usuario'];
    $_SESSION['id'] = $row['id'];

    echo '<meta http-equiv="refresh" content = "0; url=../pages/Libros.php">';


}
else{
    echo '<meta http-equiv="refresh" content = "0; url=../pages/index.html">';
}
?>