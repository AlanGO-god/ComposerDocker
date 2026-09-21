<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Composer\InstalledVersions;

/** Escapa texto para imprimirlo de forma segura en HTML. */
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

// Pruebas disponibles en la aplicación
$demos = [
    [
        'titulo'        => 'Generar PDF',
        'paquete'       => 'dompdf/dompdf',
        'descripcion'   => 'Convierte HTML y CSS en un documento PDF con una tabla de ventas.',
        'url'           => 'pdf.php',
        'boton'         => 'Ver PDF',
        'nueva_pestana' => true,
    ],
    [
        'titulo'        => 'Generar código QR',
        'paquete'       => 'chillerlan/php-qrcode',
        'descripcion'   => 'Crea un código QR a partir de cualquier texto o enlace.',
        'url'           => 'qr.php',
        'boton'         => 'Abrir generador',
        'nueva_pestana' => false,
    ],
    [
        'titulo'        => 'Descargar Excel',
        'paquete'       => 'phpoffice/phpspreadsheet',
        'descripcion'   => 'Genera una hoja de cálculo .xlsx con fórmulas y formato de moneda.',
        'url'           => 'excel.php',
        'boton'         => 'Descargar archivo',
        'nueva_pestana' => false,
    ],
];

// Versión instalada de cada librería, consultada a Composer en tiempo de ejecución
$instaladas = [];
foreach (['dompdf/dompdf', 'chillerlan/php-qrcode', 'phpoffice/phpspreadsheet', 'fakerphp/faker'] as $paquete) {
    $instaladas[$paquete] = InstalledVersions::isInstalled($paquete)
        ? (InstalledVersions::getPrettyVersion($paquete) ?? 'sin versión')
        : 'no instalada';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Composer + Docker</title>
    <style>
        :root {
            --fondo: #f4f6f9;
            --tarjeta: #ffffff;
            --texto: #1f2933;
            --suave: #52606d;
            --borde: #d9e0e7;
            --acento: #2563eb;
            --acento-hover: #1d4ed8;
            --boton-texto: #ffffff;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --fondo: #0f1720;
                --tarjeta: #18222e;
                --texto: #e6ebf0;
                --suave: #a3b1bf;
                --borde: #2a3746;
                --acento: #6ea0ff;
                --acento-hover: #93b8ff;
                --boton-texto: #0b1220;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--fondo);
            color: var(--texto);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
        }
        main { max-width: 900px; margin: 0 auto; padding: 3rem 1.25rem; }
        h1 { margin: 0 0 .5rem; font-size: 2rem; }
        h2 { margin: 2.5rem 0 1rem; font-size: 1.15rem; }
        .subtitulo { margin: 0; color: var(--suave); }
        code { font-family: ui-monospace, Consolas, monospace; font-size: .9em; }
        .etiquetas { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
        .etiqueta {
            padding: .2rem .75rem;
            border: 1px solid var(--borde);
            border-radius: 999px;
            background: var(--tarjeta);
            color: var(--suave);
            font-size: .85rem;
        }
        .tarjetas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1rem;
        }
        .tarjeta {
            display: flex;
            flex-direction: column;
            padding: 1.25rem;
            background: var(--tarjeta);
            border: 1px solid var(--borde);
            border-radius: 12px;
        }
        .tarjeta h3 { margin: 0 0 .25rem; font-size: 1.05rem; }
        .tarjeta p { flex: 1; margin: .75rem 0 1rem; color: var(--suave); font-size: .95rem; }
        .paquete { color: var(--suave); font-family: ui-monospace, Consolas, monospace; font-size: .8rem; }
        .boton {
            align-self: flex-start;
            padding: .5rem 1rem;
            background: var(--acento);
            color: var(--boton-texto);
            border-radius: 8px;
            font-size: .9rem;
            font-weight: 600;
            text-decoration: none;
        }
        .boton:hover, .boton:focus-visible { background: var(--acento-hover); }
        .tabla {
            overflow-x: auto;
            background: var(--tarjeta);
            border: 1px solid var(--borde);
            border-radius: 12px;
        }
        table { width: 100%; border-collapse: collapse; font-size: .95rem; }
        th, td { padding: .7rem 1rem; border-bottom: 1px solid var(--borde); text-align: left; }
        th { color: var(--suave); font-size: .78rem; letter-spacing: .05em; text-transform: uppercase; }
        tr:last-child td { border-bottom: 0; }
        td.mono { font-family: ui-monospace, Consolas, monospace; font-size: .9rem; }
        footer { margin-top: 2.5rem; color: var(--suave); font-size: .85rem; }
    </style>
</head>
<body>
    <main>
        <header>
            <h1>Composer + Docker</h1>
            <p class="subtitulo">
                Las librerías se instalaron automáticamente al ejecutar <code>docker compose up</code>.
            </p>
            <div class="etiquetas">
                <span class="etiqueta">PHP <?= e(PHP_VERSION) ?></span>
                <span class="etiqueta">Servidor integrado de PHP</span>
                <span class="etiqueta">Puerto 8080</span>
            </div>
        </header>

        <section>
            <h2>Pruebas</h2>
            <div class="tarjetas">
                <?php foreach ($demos as $demo): ?>
                    <article class="tarjeta">
                        <h3><?= e($demo['titulo']) ?></h3>
                        <span class="paquete"><?= e($demo['paquete']) ?></span>
                        <p><?= e($demo['descripcion']) ?></p>
                        <a class="boton" href="<?= e($demo['url']) ?>"<?= $demo['nueva_pestana'] ? ' target="_blank" rel="noopener"' : '' ?>>
                            <?= e($demo['boton']) ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <h2>Librerías instaladas con Composer</h2>
            <div class="tabla">
                <table>
                    <thead>
                        <tr><th>Paquete</th><th>Versión instalada</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($instaladas as $paquete => $version): ?>
                            <tr>
                                <td class="mono"><?= e($paquete) ?></td>
                                <td><?= e($version) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <footer>Práctica de programación web: Composer con Docker.</footer>
    </main>
</body>
</html>