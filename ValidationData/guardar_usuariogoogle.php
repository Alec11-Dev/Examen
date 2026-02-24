<?php
require_once('conexion.php');
session_start();

// Configurar reporte de errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if ($datos && isset($datos['token'])) {
    $token = $datos['token'];
    
    // Validar el token directamente con Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
    $response = file_get_contents($url);
    
    if ($response) {
        $google_data = json_decode($response, true);
        
        // Verificar si Google devolvió un error o los datos
        if (isset($google_data['email'])) {
            $email = $google_data['email'];
            $nombre = $google_data['name'];
            $google_id = $google_data['sub']; // 'sub' es el ID único de Google
            $foto = $google_data['picture'];

            try {
                // Insertar o actualizar usuario (Asegúrate de haber creado la columna google_id en tu BD)
                $stmt = $conexion->prepare("INSERT INTO usuariosGoogle (usuario, email, google_id, foto_perfil) 
                                            VALUES (?, ?, ?, ?) 
                                            ON DUPLICATE KEY UPDATE usuario = VALUES(usuario), google_id = VALUES(google_id), foto_perfil = VALUES(foto_perfil)");
                
                $stmt->bind_param("ssss", $nombre, $email, $google_id, $foto);
                $stmt->execute();

                // Obtener ID interno para la sesión
                $stmt_select = $conexion->prepare("SELECT id_usuario, usuario, email FROM usuariosGoogle WHERE email = ?");
                $stmt_select->bind_param("s", $email);
                $stmt_select->execute();
                $resultado = $stmt_select->get_result();
                $usuario = $resultado->fetch_assoc();

                if ($usuario) {
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['usuario'] = $usuario['usuario'];
                    $_SESSION['email'] = $usuario['email'];
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado tras registro.']);
                }
                $stmt->close();
                $stmt_select->close();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Token de Google inválido.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al conectar con Google.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No se recibió el token.']);
}
?>