<?php

$localidadesValidas = [
    'Usaquén',
    'Chapinero',
    'Santa Fe',
    'San Cristóbal',
    'Usme',
    'Tunjuelito',
    'Bosa',
    'Kennedy',
    'Fontibón',
    'Engativá',
    'Suba',
    'Barrios Unidos',
    'Teusaquillo',
    'Los Mártires',
    'Antonio Nariño',
    'Puente Aranda',
    'La Candelaria',
    'Rafael Uribe Uribe',
    'Ciudad Bolívar',
    'Sumapaz',
];

$localidad = '';
$paraderos = [];
$mensajeError = '';
$consultaRealizada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['localidad'])) {

    $consultaRealizada = true;
    $localidad = trim($_POST['localidad']);

    if (!in_array($localidad, $localidadesValidas, true)) {

        $mensajeError = 'La localidad ingresada no es válida. Por favor selecciona una de la lista.';
    } else {

        $python = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
        $script = __DIR__ . DIRECTORY_SEPARATOR . 'sitp.py';

        $comando = $python . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($localidad);

        $respuesta = shell_exec($comando);

        if ($respuesta === null || trim($respuesta) === '') {

            $mensajeError = 'No fue posible obtener información del servicio SITP.';
        } else {

            $datos = json_decode($respuesta, true);

            if ($datos === null) {

                $mensajeError = 'La respuesta de Python no tiene un formato válido.';
            } elseif (isset($datos['error'])) {

                $mensajeError = $datos['error'];
            } elseif (!isset($datos['paraderos'])) {

                $mensajeError = 'La respuesta no contiene los datos esperados.';
            } elseif (count($datos['paraderos']) === 0) {

                $mensajeError = 'No se encontraron paraderos para la localidad "' . $localidad . '".';
            } else {

                $paraderos = $datos['paraderos'];
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Paraderos SITP por Localidad</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f2f4f7;
            color: #1f2937;
            margin: 0;
            padding: 30px 15px;
        }

        .contenedor {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }

        h1 {
            font-size: 1.5rem;
            color: #0f4c81;
            margin-top: 0;
        }

        p.subtitulo {
            color: #6b7280;
            margin-top: -8px;
        }

        form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin: 20px 0 25px;
            padding: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        select,
        button {
            padding: 10px 14px;
            font-size: 1rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
        }

        button {
            background: #0f4c81;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        button:hover {
            background: #0b3a63;
        }

        .alerta {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.92rem;
        }

        th {
            background: #0f4c81;
            color: #fff;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        .contador {
            color: #374151;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
    </style>

</head>

<body>

    <div class="contenedor">

        <h1>🚌 Consulta de Paraderos del SITP</h1>

        <p class="subtitulo">
            Fuente: Datos Abiertos Bogotá / IDECA — dataset "Paraderos SITP Bogotá D.C"
        </p>

        <form method="POST" action="paraderos_sitp.php">

            <label for="localidad">Localidad:</label>

            <select name="localidad" id="localidad" required>

                <option value="">-- Selecciona una localidad --</option>

                <?php foreach ($localidadesValidas as $opcion): ?>

                    <option
                        value="<?php echo htmlspecialchars($opcion); ?>"
                        <?php echo ($opcion === $localidad) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($opcion); ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">Buscar paraderos</button>

        </form>

        <?php if ($mensajeError !== ''): ?>

            <div class="alerta">
                ⚠️ <?php echo htmlspecialchars($mensajeError); ?>
            </div>

        <?php endif; ?>

        <?php if ($consultaRealizada && count($paraderos) > 0): ?>

            <p class="contador">
                Se encontraron
                <strong><?php echo count($paraderos); ?></strong>
                paradero(s) en
                <strong><?php echo htmlspecialchars($localidad); ?></strong>.
            </p>

            <table>

                <thead>

                    <tr>
                        <th>Código</th>
                        <th>Nombre del paradero</th>
                        <th>Dirección aproximada</th>
                        <th>Latitud</th>
                        <th>Longitud</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($paraderos as $p): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($p['codigo']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($p['nombre']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($p['direccion']); ?>
                            </td>

                            <td>
                                <?php
                                echo $p['latitud'] !== null
                                    ? htmlspecialchars($p['latitud'])
                                    : '-';
                                ?>
                            </td>

                            <td>
                                <?php
                                echo $p['longitud'] !== null
                                    ? htmlspecialchars($p['longitud'])
                                    : '-';
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</body>

</html>