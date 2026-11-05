<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): void {
    http_response_code($codigo);
    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, [
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
}

$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$modalidad = trim($_POST['modalidad'] ?? '');
$cantidad = trim($_POST['cantidad'] ?? '');
$presupuesto = $_POST['presupuesto'] ?? '';
$flexibilidad = trim($_POST['flexibilidad'] ?? '');
$pago = trim($_POST['pago'] ?? '');
$uso = trim($_POST['uso'] ?? '');
$exigencia = trim($_POST['exigencia'] ?? '');
$programas = trim($_POST['programas'] ?? '');
$grafica = trim($_POST['grafica'] ?? '');
$almacenamiento = trim($_POST['almacenamiento'] ?? '');
$componentes = trim($_POST['componentes_actuales'] ?? '');
$plazo = trim($_POST['plazo'] ?? '');
$detalle = trim($_POST['detalle'] ?? '');

$elementosPermitidos = [
    'Monitor',
    'Teclado y mouse',
    'Auriculares',
    'Wi-Fi y Bluetooth',
    'Windows instalado',
    'Armado y pruebas'
];

$elementosRecibidos = $_POST['elementos'] ?? [];

if (!is_array($elementosRecibidos)) {
    responder(400, [
        'ok' => false,
        'mensaje' => 'La selección de elementos no es válida.'
    ]);
}

$elementos = array_values(array_unique($elementosRecibidos));

if (array_diff($elementos, $elementosPermitidos)) {
    responder(400, [
        'ok' => false,
        'mensaje' => 'Se recibió un elemento no válido.'
    ]);
}

if (
    $nombre === '' ||
    mb_strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 254 ||
    strlen($telefono) > 30 ||
    $modalidad === '' ||
    $uso === '' ||
    !in_array($cantidad, ['1', '2', '3', '4', '5 o más'], true) ||
    !is_scalar($presupuesto) ||
    !preg_match('/^[0-9]{1,12}$/', (string)$presupuesto) ||
    (float)$presupuesto < 1
) {
    responder(422, [
        'ok' => false,
        'mensaje' => 'Revisá los campos obligatorios y el presupuesto.'
    ]);
}

$modalidadesPermitidas = [
    'Armado desde cero',
    'PC prearmada',
    'Actualizar equipo',
    'Comprar notebook',
    'Asesoramiento'
];

$usosPermitidos = [
    'Oficina y estudio',
    'Programación',
    'Gaming',
    'Diseño y edición',
    'Modelado 3D e IA',
    'Uso profesional',
    'Uso variado'
];

if (
    !in_array($modalidad, $modalidadesPermitidas, true) ||
    !in_array($uso, $usosPermitidos, true)
) {
    responder(422, [
        'ok' => false,
        'mensaje' => 'La modalidad o el uso seleccionado no es válido.'
    ]);
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=soporte_informatico;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $sql = "INSERT INTO solicitudes_armado (
        nombre, correo, telefono, modalidad, cantidad,
        presupuesto, flexibilidad, pago, uso, exigencia,
        programas, grafica, almacenamiento, elementos,
        componentes_actuales, plazo, detalle
    ) VALUES (
        :nombre, :correo, :telefono, :modalidad, :cantidad,
        :presupuesto, :flexibilidad, :pago, :uso, :exigencia,
        :programas, :grafica, :almacenamiento, :elementos,
        :componentes, :plazo, :detalle
    )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':telefono' => $telefono ?: null,
        ':modalidad' => $modalidad,
        ':cantidad' => $cantidad,
        ':presupuesto' => $presupuesto,
        ':flexibilidad' => $flexibilidad ?: null,
        ':pago' => $pago ?: null,
        ':uso' => $uso,
        ':exigencia' => $exigencia ?: null,
        ':programas' => $programas ?: null,
        ':grafica' => $grafica ?: null,
        ':almacenamiento' => $almacenamiento ?: null,
        ':elementos' => json_encode(
            $elementos,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ),
        ':componentes' => $componentes ?: null,
        ':plazo' => $plazo ?: null,
        ':detalle' => $detalle ?: null
    ]);

    responder(201, [
        'ok' => true,
        'mensaje' => '¡Tu solicitud se guardó correctamente!',
        'id' => $pdo->lastInsertId()
    ]);

} catch (Throwable $e) {
    error_log('Error al guardar solicitud de armado: ' . $e->getMessage());

    responder(500, [
        'ok' => false,
        'mensaje' => 'No pudimos guardar la solicitud. Revisá la conexión con la base de datos.'
    ]);
}
