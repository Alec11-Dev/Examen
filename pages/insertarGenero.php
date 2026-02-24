<?php
session_start();
include('../ValidationData/conexion.php');
if(isset($_SESSION['email']) != ""){
    $nameUser = $_SESSION['usuario'];
    $email = $_SESSION['email'];
?>



<!DOCTYPE html>
<html>

<head>
    <title>Insertar Genero</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>

<body>
    <h1>Insertar Genero</h1>
    <form method="post">
        <?php
        include("../ValidationData/conexion.php");

        echo "<label for='genero'>Genero:</label>";
        echo "<input type='text' id='genero' name='genero' required>";
        echo "<br>";
        echo "<input type='submit' name='insertar' value='Insertar'>";
        echo "<button type='button' onclick=\"window.location.href='../pages/menu.php'\">Volver al registro</button>";
        ?>
    </form>


    <?php
    include("../ValidationData/conexion.php");

    // Se lleva acabo el proceso de 'insertar' atraves del metodo POST
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['insertar'])) {
    $Nombre = $_POST['genero'];
        try{
            $sql2 = "INSERT INTO genero (nombre_genero) VALUES (?)";
            $stmt2 = $conexion->prepare($sql2);
            $stmt2->bind_param("s", $Nombre);

            if(!$stmt2->execute()){
                throw new Exception("Error al ejecutar la consulta");
            }
            $stmt2->close();
        }
        catch(Exception $e){
            echo '<script>alert("Error: '.$e->getMessage().'");</script>';
        }
    }
    ?>
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