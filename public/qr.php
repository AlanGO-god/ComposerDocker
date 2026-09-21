<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;

/** Escapa texto para imprimirlo de forma segura en HTML. */
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

$texto = (isset($_GET['texto']) && is_string($_GET['texto'])) ? trim($_GET['texto']) : '';
if ($texto === '') {
    $texto = 'https://getcomposer.org';
}

try {
    // render() devuelve un data URI listo para usar en <img src="...">
    $qr = (new QRCode())->render($texto);
    $error = null;
} catch (Throwable $excepcion) {
    $qr = null;
    $error = $excepcion->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generador de QR</title>
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
            --error: #b42318;
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
                --error: #ff8a80;
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
        main { max-width: 640px; margin: 0 auto; padding: 3rem 1.25rem; }
        h1 { margin: 0 0 .5rem; font-size: 2rem; }
        .subtitulo { margin: 0 0 2rem; color: var(--suave); }
        .enlace-volver { display: inline-block; margin-bottom: 1.5rem; color: var(--acento); font-size: .9rem; text-decoration: none; }
        .enlace-volver:hover, .enlace-volver:focus-visible { color: var(--acento-hover); text-decoration: underline; }
        .tarjeta {
            padding: 1.25rem;
            background: var(--tarjeta);
            border: 1px solid var(--borde);
            border-radius: 12px;
        }
        label { display: block; margin-bottom: .4rem; font-size: .9rem; font-weight: 600; }
        .formulario { display: flex; flex-wrap: wrap; gap: .5rem; }
        input[type=text] {
            flex: 1 1 260px;
            padding: .6rem .8rem;
            background: var(--fondo);
            border: 1px solid var(--borde);
            border-radius: 8px;
            color: var(--texto);
            font: inherit;
        }
        input[type=text]:focus-visible { outline: 2px solid var(--acento); outline-offset: 1px; }
        button {
            padding: .6rem 1.2rem;
            background: var(--acento);
            border: 0;
            border-radius: 8px;
            color: var(--boton-texto);
            cursor: pointer;
            font: inherit;
            font-weight: 600;
        }
        button:hover, button:focus-visible { background: var(--acento-hover); }
        .resultado { margin-top: 1rem; text-align: center; }
        .resultado img {
            width: 260px;
            max-width: 100%;
            height: auto;
            padding: 1rem;
            background: #ffffff;
            border-radius: 8px;
        }
        .contenido { margin: .75rem 0 0; color: var(--suave); font-size: .85rem; overflow-wrap: anywhere; }
        .error { margin-top: 1rem; border-left: 4px solid var(--error); }
        .error strong { color: var(--error); }
        footer { margin-top: 2.5rem; color: var(--suave); font-size: .85rem; }
    </style>
</head>
<body>
    <main>
        <a class="enlace-volver" href="index.php">&larr; Volver al menú</a>

        <h1>Generador de QR</h1>
        <p class="subtitulo">
            Escribe un texto o un enlace y genera su código con <code>chillerlan/php-qrcode</code>.
        </p>

        <form class="tarjeta" method="get">
            <label for="texto">Texto o enlace</label>
            <div class="formulario">
                <input type="text" id="texto" name="texto" maxlength="500" value="<?= e($texto) ?>">
                <button type="submit">Generar</button>
            </div>
        </form>

        <?php if ($error !== null): ?>
            <div class="tarjeta error">
                <strong>No se pudo generar el código.</strong>
                <p class="contenido"><?= e($error) ?></p>
            </div>
        <?php else: ?>
            <div class="tarjeta resultado">
                <img src="<?= $qr ?>" alt="Código QR de: <?= e($texto) ?>">
                <p class="contenido"><?= e($texto) ?></p>
            </div>
        <?php endif; ?>

        <footer>Práctica de programación web: Composer con Docker.</footer>
    </main>
</body>
</html>