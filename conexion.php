
<?php
declare(strict_types=1);

$host = 'localhost';
$baseDatos = 'soporte_informatico';
$usuario = 'root';
$contrasena = ''; // Ajustar si tu MySQL tiene contraseña.

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$baseDatos;charset=utf8mb4",
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Error de conexión: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo conectar con la base de datos.'
    ]);
    exit;
}
