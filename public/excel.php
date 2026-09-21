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