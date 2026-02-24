<?php
session_start();
require_once('conexion.php');

if (isset($_SESSION['id_usuario'])) {
    $idUser = $_SESSION['id_usuario'];

    try {
        $conexion->begin_transaction();

        // Eliminamos el usuario. 
        // NOTA: Asumimos que la columna ID en tu tabla 'usuarios' se llama 'id' (basado en $_SESSION['id']).
        // Si tu columna se llama 'id_usuario', cambia 'id' por 'id_usuario' en la siguiente línea.
        $sql = "DELETE FROM usuarios WHERE id_usuario = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $idUser);
        $stmt->execute();
        $stmt->close();

        $conexion->commit();
    } catch (Exception $e) {
        $conexion->rollback();
        echo "<script>alert('Error al eliminar la cuenta: " . $e->getMessage() . "'); window.location.href='../pages/menu.php';</script>";
        exit();
    }
}

// Destruir la sesión y redirigir al login
session_unset();
session_destroy();

echo "<script>alert('Tu cuenta ha sido eliminada permanentemente.'); window.location.href='../index.html';</script>";
?>