<?php
// 1. Incluir la conexión y empezar la sesión
require_once('conexion.php');
session_start();

// 2. Leer los datos JSON enviados desde el frontend
$json = file_get_contents('php://input');
$datos = json_decode($json, true);

// 3. Validar que los datos necesarios llegaron
if ($datos && isset($datos['email']) && isset($datos['nombre']) && isset($datos['facebook_id'])) {
    $nombre = $datos['nombre'];
    $email  = $datos['email'];
    $fb_id  = $datos['facebook_id'];
    $foto   = $datos['foto'] ?? null; // Usar foto si existe, si no, null

    // 4. Preparar la consulta para insertar o actualizar el usuario
    // Se asume que 'email' es una clave única (UNIQUE KEY) en tu tabla 'usuarios'
    // Si el email ya existe, actualiza el nombre, el ID de Facebook y la foto.
    $stmt = $conexion->prepare("INSERT INTO usuarios (usuario, email, facebook_id, foto_perfil) 
                                VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE usuario = VALUES(usuario), facebook_id = VALUES(facebook_id), foto_perfil = VALUES(foto_perfil)");
    
    $stmt->bind_param("ssss", $nombre, $email, $fb_id, $foto);
    
    if ($stmt->execute()) {
        // 5. Si la inserción/actualización fue exitosa, obtener los datos del usuario para la sesión
        $stmt_select = $conexion->prepare("SELECT id_usuario, usuario, email FROM usuarios WHERE email = ?");
        $stmt_select->bind_param("s", $email);
        $stmt_select->execute();
        $resultado = $stmt_select->get_result();
        $usuario = $resultado->fetch_assoc();

        if ($usuario) {
            // 6. Establecer las variables de sesión que tu aplicación espera
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['usuario'] = $usuario['usuario'];
            $_SESSION['email'] = $usuario['email'];
            
            // 7. Enviar una respuesta de éxito al frontend
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo encontrar al usuario después de guardarlo.']);
        }
        $stmt_select->close();

    } else {
        // Si hubo un error en la base de datos
        echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos.']);
    }
    $stmt->close();
    $conexion->close();

} else {
    // Si no llegaron los datos esperados
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
}
?>
