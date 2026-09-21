# Práctica

## Introducción a Composer con Docker

### Instalación, Arquitectura y Librerías (PDF, QR y Excel)

---

## Objetivo general

1. Configurar un entorno PHP con Docker y Docker Compose, sin instalar PHP ni Composer en la máquina.
2. Identificar y explicar los componentes principales de Composer (`composer.json`, `composer.lock`, `vendor/`, autoload) y de Docker (Dockerfile, docker-compose, volúmenes).
3. Instalar librerías con Composer de forma automática al ejecutar `docker compose up`.
4. Probar librerías para generar **PDF**, **QR** y **hojas de cálculo (Excel)**, y aprender a agregar nuevas librerías mediante archivos.

---

## 1. Instalación del entorno

### 1.1 Instalar Docker

- Windows / macOS: [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Linux: Docker Engine + plugin Docker Compose

### 1.2 Verificar la instalación

```bash
docker --version
docker compose version
```

**Nota:** asegúrense de que Docker Desktop esté **abierto y corriendo** antes de continuar.

### 1.3 Crear la carpeta del proyecto

```bash
mkdir composer-docker-demo
cd composer-docker-demo
mkdir src public
```

En las siguientes secciones crearán los archivos dentro de esta carpeta.

---

## 2. Conociendo la arquitectura

### 2.1 ¿Qué es Composer?

**Composer** es el gestor de dependencias de PHP (como `npm` en Node o `pip` en Python):

- Las librerías se declaran en un archivo `composer.json`.
- Composer las descarga desde [packagist.org](https://packagist.org) a la carpeta `vendor/`.
- Genera `vendor/autoload.php`: con un solo `require` se pueden usar todas las clases sin hacer `include` de cada archivo.

¿Por qué usarlo con Docker? Todos los compañeros tendrán la misma versión de PHP, las mismas extensiones y las mismas librerías, sin instalar nada en su sistema.

### 2.2 Estructura del proyecto

```
composer-docker-demo/
├── Dockerfile            ← Receta de la imagen (PHP + extensiones + Composer)
├── docker-compose.yml    ← Cómo se ejecuta el contenedor (puertos, volúmenes, comando)
├── composer.json         ← Librerías que necesita el proyecto (lo editamos nosotros)
├── composer.lock         ← (se genera solo) versiones exactas instaladas
├── .gitignore
├── vendor/               ← (se genera solo) código de las librerías + autoload.php
├── src/                  ← Clases propias (namespace App\)
│   └── DataGenerator.php
└── public/               ← Raíz web: punto de entrada del servidor
    ├── index.php
    ├── pdf.php
    ├── qr.php
    └── excel.php
```

**Nota:** `composer.lock` y `vendor/` **no se crean a mano**: aparecerán solos la primera vez que levanten el contenedor (sección 3.5).

### 2.3 Componentes principales

| Componente | Rol | Ubicación |
|---|---|---|
| `Dockerfile` | Define la **imagen**: PHP, extensiones (gd, zip) y Composer | Raíz del proyecto |
| `docker-compose.yml` | Define cómo **correr** el contenedor: puertos, volúmenes y comando de arranque | Raíz del proyecto |
| `composer.json` | Lista de librerías y rangos de versión que se **piden** | Raíz del proyecto |
| `composer.lock` | Versiones **exactas** que se instalaron | Raíz del proyecto (generado) |
| `vendor/` | Código descargado de las librerías y `autoload.php` | Raíz del proyecto (generado) |

### 2.4 Flujo de arranque

```
docker compose up
      │
      ▼
docker-compose.yml ──build──> Dockerfile (imagen: PHP 8.3 + gd + zip + Composer)
      │
      ▼
Contenedor arranca ──> composer install (llena vendor/) ──> php -S (servidor web)
      │
      ▼
http://localhost:8080   (puerto 8080 de tu equipo → puerto 8000 del contenedor)
```

Flujo de una petición:

```
Cliente (Navegador) → public/pdf.php → vendor/autoload.php → Librería (dompdf) → Respuesta (PDF)
```

---

## 3. Configurar el entorno Docker

### 3.1 Crear el `Dockerfile`

```dockerfile
FROM php:8.3-cli

# Herramientas del sistema y librerías necesarias para las extensiones de PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd zip \
    && rm -rf /var/lib/apt/lists/*

# Copiamos el binario de Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app
EXPOSE 8000
```

**Nota:** las extensiones de PHP (`gd`, `zip`) se instalan en el `Dockerfile`, **no** con Composer. Varias librerías las necesitan (PhpSpreadsheet requiere `gd` y `zip`).

### 3.2 Crear el `docker-compose.yml`

```yaml
services:
  web:
    build: .
    container_name: composer-demo
    ports:
      - "8080:8000"                # equipo:contenedor
    volumes:
      - .:/app                     # la carpeta del proyecto se comparte con /app
      - composer_cache:/root/.cache/composer   # caché de Composer (descargas más rápidas)
    command: >
      sh -c "(composer install --no-interaction --prefer-dist ||
      composer update --no-interaction --prefer-dist) &&
      echo '>> Listo. Abre http://localhost:8080' &&
      php -S 0.0.0.0:8000 -t public"

volumes:
  composer_cache:
```

Qué hace cada parte:

- `build: .` → construye la imagen con el `Dockerfile` de esta carpeta.
- `ports` → el puerto 8080 de su equipo apunta al 8000 del contenedor.
- `volumes: .:/app` → comparten el código con el contenedor: editan en su editor y se refleja al instante. También hace que `vendor/` aparezca en su equipo.
- `command` → se ejecuta cada vez que arranca el contenedor: primero instala las librerías con Composer y luego levanta el servidor web integrado de PHP sirviendo la carpeta `public/`.

**Nota:** `composer install` usa el `composer.lock` si existe. Si no existe, o si ya no coincide con `composer.json` (por ejemplo, agregaron una librería), cae a `composer update`, que recalcula las versiones y regenera el lock.

### 3.3 Crear el `composer.json`

Es **el archivo clave**: aquí se declaran las librerías.

```json
{
    "name": "alumno/composer-docker-demo",
    "description": "Demo de Composer con Docker",
    "type": "project",
    "require": {
        "php": "^8.2",
        "dompdf/dompdf": "^3.0",
        "chillerlan/php-qrcode": "^5.0",
        "phpoffice/phpspreadsheet": "^5.0",
        "fakerphp/faker": "^1.23"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

| Librería | Para qué sirve |
|---|---|
| `dompdf/dompdf` | Generar **PDF** a partir de HTML/CSS |
| `chillerlan/php-qrcode` | Generar códigos **QR** |
| `phpoffice/phpspreadsheet` | Crear hojas de cálculo **Excel (.xlsx)** |
| `fakerphp/faker` | Generar **datos de prueba** (nombres, precios, etc.) |

`autoload.psr-4` indica que el namespace `App\` apunta a la carpeta `src/`: así, `App\DataGenerator` se carga desde `src/DataGenerator.php` sin ningún `require` manual.

Versiones en `composer.json`:

| Constraint | Significa |
|---|---|
| `^3.0` | Cualquier `3.x` (≥3.0 y <4.0). **El más usado.** |
| `~3.1.0` | Cualquier `3.1.x` |
| `3.1.2` | Exactamente esa versión |
| `*` | La que sea (no recomendado) |

### 3.4 Crear el `.gitignore`

```
vendor/
composer.lock
```

**Nota:** en un proyecto real el `composer.lock` **sí se sube** a Git, para que todo el equipo tenga exactamente las mismas versiones. En esta práctica lo ignoramos a propósito para que cada quien vea cómo Composer lo genera en su propia máquina.

### 3.5 Levantar el contenedor

Aún faltan los archivos de la sección 4, pero pueden dejar el servidor listo desde ahora. Ejecuten en la carpeta del proyecto:

```bash
docker compose up
```

La **primera vez** tarda unos minutos porque:

1. Descarga la imagen base de PHP.
2. Construye la imagen (instala extensiones y Composer).
3. Ejecuta `composer install` y descarga las librerías.

Verán en los logs un aviso como *"No composer.lock file present. Updating dependencies to latest instead of installing from lock file"*. Es normal: Composer resuelve las versiones desde cero.

Al terminar aparecerá `>> Listo. Abre http://localhost:8080`.

**Observen** la carpeta del proyecto: ahora existen `vendor/` y `composer.lock`. Abran `composer.lock` y busquen la versión exacta que se instaló de `dompdf/dompdf`.

Para detener el servidor: `Ctrl + C`.

---

## 4. Construyendo la aplicación de prueba

### 4.1 Clase propia con datos falsos

`src/DataGenerator.php`

```php
<?php
declare(strict_types=1);

namespace App;

use Faker\Factory;

final class DataGenerator
{
    /** Devuelve una lista de productos de ejemplo. */
    public static function productos(int $cantidad = 10): array
    {
        $faker = Factory::create('es_MX');
        $catalogo = ['Laptop', 'Mouse', 'Teclado', 'Monitor', 'Audífonos', 'Webcam', 'Disco SSD', 'Memoria USB'];

        $filas = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $filas[] = [
                'id'       => $i,
                'producto' => $faker->randomElement($catalogo),
                'cantidad' => $faker->numberBetween(1, 20),
                'precio'   => $faker->randomFloat(2, 150, 9000),
            ];
        }
        return $filas;
    }
}
```

**Nota:** no lleva `require`. Composer carga la clase automáticamente gracias al `autoload` de `composer.json`.

### 4.2 Menú principal

`public/index.php`

```php
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
```

**Nota:** `Composer\InstalledVersions` es una clase que Composer incluye en `vendor/` y permite consultar en tiempo de ejecución qué paquetes y versiones se instalaron. Por eso esta página carga primero el `autoload.php`.

### 4.3 Generar un PDF (dompdf)

`public/pdf.php`

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\DataGenerator;
use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('America/Mexico_City');

$productos = DataGenerator::productos(10);
$total = 0.0;
$filas = '';

foreach ($productos as $p) {
    $subtotal = $p['cantidad'] * $p['precio'];
    $total += $subtotal;
    $filas .= sprintf(
        '<tr><td>%d</td><td>%s</td><td class="r">%d</td><td class="r">$%s</td><td class="r">$%s</td></tr>',
        $p['id'],
        htmlspecialchars($p['producto']),
        $p['cantidad'],
        number_format($p['precio'], 2),
        number_format($subtotal, 2)
    );
}

$fecha = date('d/m/Y H:i');
$totalFmt = number_format($total, 2);

$html = <<<HTML
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
    h1 { color: #2c3e50; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th { background: #2c3e50; color: #fff; padding: 6px; text-align: left; }
    td { border-bottom: 1px solid #ddd; padding: 6px; }
    .r { text-align: right; }
    .total { font-weight: bold; font-size: 14px; }
</style>
</head>
<body>
    <h1>Reporte de ventas</h1>
    <p>Generado con Composer + Docker el {$fecha}</p>
    <table>
        <tr><th>#</th><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr>
        {$filas}
        <tr class="total"><td colspan="4" class="r">TOTAL</td><td class="r">\${$totalFmt}</td></tr>
    </table>
</body>
</html>
HTML;

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Attachment => false: se muestra en el navegador; true: se descarga
$dompdf->stream('reporte.pdf', ['Attachment' => false]);
```

**Nota:** los archivos que solo contienen PHP **no llevan la etiqueta de cierre `?>`**. Si después de ella queda un salto de línea, PHP lo envía como salida y rompe los `header()` (error *headers already sent*).

### 4.4 Generar un código QR (chillerlan/php-qrcode)

`public/qr.php`

```php
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
```

**Nota:** este archivo mezcla PHP y HTML, por eso aquí sí se cierran las etiquetas `?>`.

### 4.5 Generar una hoja de cálculo (PhpSpreadsheet)

`public/excel.php`

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\DataGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$productos = DataGenerator::productos(10);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ventas');

// Encabezados
$sheet->fromArray(['#', 'Producto', 'Cantidad', 'Precio', 'Subtotal'], null, 'A1');
$sheet->getStyle('A1:E1')->getFont()->setBold(true);

// Datos (la columna E usa una fórmula de Excel)
$fila = 2;
foreach ($productos as $p) {
    $sheet->setCellValue("A{$fila}", $p['id']);
    $sheet->setCellValue("B{$fila}", $p['producto']);
    $sheet->setCellValue("C{$fila}", $p['cantidad']);
    $sheet->setCellValue("D{$fila}", $p['precio']);
    $sheet->setCellValue("E{$fila}", "=C{$fila}*D{$fila}");
    $fila++;
}

// Total con fórmula SUM
$ultima = $fila - 1;
$sheet->setCellValue("D{$fila}", 'TOTAL');
$sheet->setCellValue("E{$fila}", "=SUM(E2:E{$ultima})");
$sheet->getStyle("D{$fila}:E{$fila}")->getFont()->setBold(true);

// Formato moneda y ancho automático
$sheet->getStyle("D2:E{$fila}")->getNumberFormat()->setFormatCode('"$"#,##0.00');
foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Enviar al navegador como descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte.xlsx"');
header('Cache-Control: max-age=0');

(new Xlsx($spreadsheet))->save('php://output');
exit;
```

---

## 5. Probar la aplicación

Con el servidor corriendo (`docker compose up`), abran **http://localhost:8080** y prueben cada enlace:

| URL | Resultado esperado |
|---|---|
| `http://localhost:8080/` | Menú con las tres pruebas y la tabla de librerías instaladas con su versión |
| `http://localhost:8080/pdf.php` | Se muestra un PDF con una tabla de 10 productos y su total |
| `http://localhost:8080/qr.php` | Se muestra un código QR; escriban otro texto y generen uno nuevo |
| `http://localhost:8080/excel.php` | Se descarga `reporte.xlsx` con fórmulas y formato de moneda |

También pueden probar desde la terminal con `curl`:

```bash
curl -o reporte.pdf  http://localhost:8080/pdf.php
curl -o reporte.xlsx http://localhost:8080/excel.php
```

Para ver las librerías que instaló Composer:

```bash
docker compose exec web composer show --direct
```

**Nota:** cada vez que recarguen `pdf.php` o `excel.php` los datos cambian, porque Faker genera valores aleatorios distintos.

---

## 6. Agregar más librerías

Las librerías se agregan **editando archivos**, sin instalar nada a mano.

### 6.1 Modificar el `composer.json`

Como ejemplo agreguen **Monolog** (logs) y **Carbon** (fechas) dentro de `require`:

```json
"require": {
    "php": "^8.2",
    "dompdf/dompdf": "^3.0",
    "chillerlan/php-qrcode": "^5.0",
    "phpoffice/phpspreadsheet": "^5.0",
    "fakerphp/faker": "^1.23",
    "monolog/monolog": "^3.0",
    "nesbot/carbon": "^3.0"
}
```

### 6.2 Reiniciar el contenedor

```bash
docker compose restart web
```

Al arrancar, el `command` del compose ejecuta otra vez `composer install`; como el lock ya no coincide con el `composer.json`, hace `composer update` e instala lo nuevo.

### 6.3 Usar la librería

`public/prueba.php`

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

Carbon::setLocale('es');
echo Carbon::now('America/Mexico_City')->translatedFormat('l j \d\e F \d\e Y');

$log = new Logger('demo');
$log->pushHandler(new StreamHandler('php://stderr', Level::Info));
$log->info('Hola desde Monolog');   // se ve con: docker compose logs -f
```

Abran `http://localhost:8080/prueba.php` y revisen los logs con `docker compose logs -f`.

### 6.4 Si la librería necesita una extensión de PHP

Si Composer falla con un mensaje como `requires ext-intl` (o `ext-bcmath`, etc.), la extensión se agrega en el **`Dockerfile`**:

```dockerfile
RUN apt-get update && apt-get install -y libicu-dev \
    && docker-php-ext-install intl bcmath \
    && rm -rf /var/lib/apt/lists/*
```

y en este caso sí hay que reconstruir la imagen:

```bash
docker compose up --build
```

### 6.5 Librerías de desarrollo

Las que solo se usan al programar (pruebas, depuración) van en `require-dev`:

```json
"require-dev": {
    "phpunit/phpunit": "^11.0"
}
```

En producción se omiten con `composer install --no-dev`.

---

## 7. Práctica: Agreguen tres librerías

Elijan **tres librerías** de su preferencia en [packagist.org](https://packagist.org), distintas a las que ya se usaron en esta guía. Para cada una:

1. Agréguenla al `composer.json`.
2. Reinicien el contenedor (sección 6.2).
3. Creen una página en `public/` que muestre su funcionamiento.

**Nota:** si alguna librería necesita una extensión de PHP, modifiquen también el `Dockerfile` (sección 6.4).

---

## 8. Comandos útiles y solución de problemas

### 8.1 Comandos útiles

| Comando | Qué hace |
|---|---|
| `docker compose up` | Construye (si hace falta) y arranca; muestra logs |
| `docker compose up -d` | Igual, pero en segundo plano |
| `docker compose up --build` | Fuerza reconstruir la imagen (tras cambiar el `Dockerfile`) |
| `docker compose restart web` | Reinicia el contenedor (vuelve a correr `composer install`) |
| `docker compose logs -f` | Ver logs en vivo |
| `docker compose exec web composer show --direct` | Lista las librerías instaladas |
| `docker compose exec web bash` | Entra a una terminal dentro del contenedor |
| `docker compose down` | Detiene y elimina el contenedor |

### 8.2 Solución de problemas

| Problema | Solución |
|---|---|
| `Cannot connect to the Docker daemon` | Abran Docker Desktop y esperen a que inicie |
| `port is already allocated` (8080 ocupado) | Cambien `"8080:8000"` por `"8081:8000"` en el compose y entren a `localhost:8081` |
| `requires ext-xxx` al instalar | Agreguen la extensión en el `Dockerfile` (sección 6.4) y usen `--build` |
| En Linux `vendor/` es de `root` y no se puede borrar | `sudo chown -R $USER:$USER .` |
| Quieren empezar de cero | `docker compose down -v` y borren `vendor/` y `composer.lock` |
| Error de red al descargar paquetes | Revisen la conexión / VPN y vuelvan a ejecutar `docker compose up` |

---

## Recursos adicionales

- Composer: <https://getcomposer.org/doc/>
- Packagist (buscador de paquetes): <https://packagist.org>
- Docker Compose: <https://docs.docker.com/compose/>
- Imagen oficial de PHP: <https://hub.docker.com/_/php>
- dompdf: <https://github.com/dompdf/dompdf>
- chillerlan/php-qrcode: <https://github.com/chillerlan/php-qrcode>
- PhpSpreadsheet: <https://phpspreadsheet.readthedocs.io>
- Faker: <https://fakerphp.org>
