<?php
/**
 * Development proof for plugin-local TCPDF availability.
 *
 * Usage: php scripts/prove-tcpdf.php
 */

define('ABSPATH', dirname(__DIR__) . '/');
define('SVDP_VOUCHERS_PLUGIN_DIR', dirname(__DIR__) . '/');

require_once dirname(__DIR__) . '/includes/class-pdf-dependency.php';

if (!SVDP_PDF_Dependency::bootstrap()) {
    fwrite(STDERR, "TCPDF bootstrap failed.\n");
    exit(1);
}

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('SVdP Vouchers');
$pdf->SetAuthor('SVdP Vouchers');
$pdf->SetTitle('TCPDF Dependency Proof');
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->Write(0, 'SVdP Vouchers TCPDF proof');

$target_path = sys_get_temp_dir() . '/svdp-vouchers-tcpdf-proof.pdf';
$bytes = file_put_contents($target_path, $pdf->Output('', 'S'));

if ($bytes === false) {
    fwrite(STDERR, "Failed to write proof PDF.\n");
    exit(1);
}

fwrite(STDOUT, $target_path . PHP_EOL);
fwrite(STDOUT, 'bytes=' . $bytes . PHP_EOL);
