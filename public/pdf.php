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