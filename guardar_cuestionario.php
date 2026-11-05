<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): void {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responder(405, [
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
}

function campo(string $nombre, int $maximo): string {
    $valor = $_POST[$nombre] ?? '';

    if (!is_string($valor)) {
        responder(422, [
            'ok' => false,
            'mensaje' => 'Se recibió un campo inválido.'
        ]);
    }

    $valor = trim($valor);

    if (strlen($valor) > $maximo) {
        responder(422, [
            'ok' => false,
            'mensaje' => 'Un campo supera la longitud permitida.'
        ]);
    }

    return $valor;
}

$nombre = campo('nombre', 100);
$correo = campo('correo', 254);
$equipo = campo('equipo', 40);
$sistema = campo('sistema', 40);
$antiguedad = campo('antiguedad', 30);
$categoria = campo('categoria', 60);
$frecuencia = campo('frecuencia', 60);
$mensajeError = campo('mensaje_error', 20);
$detalle = campo('detalle', 5000);
$detalleError = campo('detalle_error', 1500);
$intentos = campo('intentos', 2000);
$respaldo = campo('respaldo', 20);

if (
    $nombre === '' || $correo === '' || $equipo === '' ||
    $sistema === '' || $categoria === '' || $frecuencia === '' ||
    $mensajeError === '' || $detalle === ''
) {
    responder(422, [
        'ok' => false,
        'mensaje' => 'Completá todos los campos obligatorios.'
    ]);
}

if (preg_match('/[\x00-\x1F\x7F]/', $nombre)) {
    responder(422, [
        'ok' => false,
        'mensaje' => 'El nombre contiene caracteres no permitidos.'
    ]);
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    responder(422, [
        'ok' => false,
        'mensaje' => 'Ingresá un correo electrónico válido.'
    ]);
}

if (strlen($detalle) < 10) {
    responder(422, [
        'ok' => false,
        'mensaje' => 'Describí el problema con al menos 10 caracteres.'
    ]);
}

$opciones = [
    'equipo' => [
        'PC de escritorio', 'Notebook', 'Otro dispositivo'
    ],
    'sistema' => [
        'Windows 11', 'Windows 10', 'Linux', 'macOS', 'Otro o no sé'
    ],
    'antiguedad' => [
        'No sé', 'Menos de 1 año', '1 a 3 años',
        '4 a 6 años', 'Más de 6 años'
    ],
    'categoria' => [
        'Rendimiento lento', 'No enciende', 'Internet o red',
        'Programas o errores', 'Virus o seguridad',
        'Hardware o periféricos', 'Instalación o configuración',
        'Pérdida de archivos', 'Otro'
    ],
    'frecuencia' => [
        'Siempre', 'A veces', 'Desde hoy', 'Después de un cambio'
    ],
    'mensaje_error' => ['Sí', 'No', 'No sé'],
    'respaldo' => ['Sí', 'No', 'No sé']
];

$valores = [
    'equipo' => $equipo,
    'sistema' => $sistema,
    'antiguedad' => $antiguedad,
    'categoria' => $categoria,
    'frecuencia' => $frecuencia,
    'mensaje_error' => $mensajeError,
    'respaldo' => $respaldo
];

foreach ($valores as $campoNombre => $valor) {
    if (!in_array($valor, $opciones[$campoNombre], true)) {
        responder(422, [
            'ok' => false,
            'mensaje' => 'Una de las opciones seleccionadas no es válida.'
        ]);
    }
}

try {
    require __DIR__ . '/conexion.php';

    $sql = 'INSERT INTO cuestionario
        (nombre, correo, equipo, sistema_operativo, antiguedad,
         categoria, frecuencia, mensaje_error, detalle,
         detalle_error, intentos, respaldo)
        VALUES
        (:nombre, :correo, :equipo, :sistema, :antiguedad,
         :categoria, :frecuencia, :mensaje_error, :detalle,
         :detalle_error, :intentos, :respaldo)';

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':equipo' => $equipo,
        ':sistema' => $sistema,
        ':antiguedad' => $antiguedad,
        ':categoria' => $categoria,
        ':frecuencia' => $frecuencia,
        ':mensaje_error' => $mensajeError,
        ':detalle' => $detalle,
        ':detalle_error' => $detalleError,
        ':intentos' => $intentos,
        ':respaldo' => $respaldo
    ]);

    responder(201, [
        'ok' => true,
        'mensaje' => 'Tu consulta fue recibida correctamente.',
        'id' => (int) $pdo->lastInsertId()
    ]);

} catch (Throwable $e) {
    error_log('Error guardando solicitud: ' . $e->getMessage());

    responder(500, [
        'ok' => false,
        'mensaje' => 'No se pudo guardar la consulta. Intentá más tarde.'
    ]);
}
