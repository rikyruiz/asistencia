<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use TCPDF;

/**
 * Report Exporter Class
 * Handles PDF, Excel, and CSV exports for attendance reports
 */
class ReportExporter {

    /**
     * Export attendance detail report
     */
    public static function exportAttendanceReport($data, $filters, $format = 'pdf') {
        switch ($format) {
            case 'pdf':
                return self::exportAttendancePDF($data, $filters);
            case 'excel':
                return self::exportAttendanceExcel($data, $filters);
            case 'csv':
                return self::exportAttendanceCSV($data, $filters);
            default:
                throw new Exception('Invalid export format');
        }
    }

    /**
     * Export summary report
     */
    public static function exportSummaryReport($data, $filters, $format = 'pdf') {
        switch ($format) {
            case 'pdf':
                return self::exportSummaryPDF($data, $filters);
            case 'excel':
                return self::exportSummaryExcel($data, $filters);
            case 'csv':
                return self::exportSummaryCSV($data, $filters);
            default:
                throw new Exception('Invalid export format');
        }
    }

    /**
     * Export location report
     */
    public static function exportLocationReport($data, $filters, $format = 'pdf') {
        switch ($format) {
            case 'pdf':
                return self::exportLocationPDF($data, $filters);
            case 'excel':
                return self::exportLocationExcel($data, $filters);
            case 'csv':
                return self::exportLocationCSV($data, $filters);
            default:
                throw new Exception('Invalid export format');
        }
    }

    /**
     * Export Attendance Report to PDF
     */
    private static function exportAttendancePDF($data, $filters) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Document info
        $pdf->SetCreator('Sistema de Asistencia - Alpe Fresh');
        $pdf->SetAuthor('Alpe Fresh Mexico');
        $pdf->SetTitle('Reporte de Asistencia');
        $pdf->SetSubject('Reporte Detallado de Asistencia');

        // Header/Footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Add page
        $pdf->AddPage();

        // Logo and Title
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(0, 51, 102); // Navy
        $pdf->Cell(0, 10, 'Alpe Fresh Mexico', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 16);
        $pdf->Cell(0, 8, 'Reporte de Asistencia', 0, 1, 'C');

        // Filters info
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $filterText = 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']);
        if (!empty($filters['user_name'])) {
            $filterText .= ' | Usuario: ' . $filters['user_name'];
        }
        if (!empty($filters['location_name'])) {
            $filterText .= ' | Ubicación: ' . $filters['location_name'];
        }
        $pdf->Cell(0, 6, $filterText, 0, 1, 'C');
        $pdf->Ln(3);

        // Table header
        $pdf->SetFillColor(0, 51, 102); // Navy
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);

        $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Empleado', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Tipo', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Hora', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'Ubicación', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Duración', 1, 1, 'C', true);

        // Table data
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);

        $fill = false;
        foreach ($data as $record) {
            $pdf->SetFillColor(245, 245, 245);

            $pdf->Cell(25, 7, formatDate($record['fecha_hora']), 1, 0, 'C', $fill);
            $pdf->Cell(50, 7, substr($record['nombre'] . ' ' . $record['apellidos'], 0, 30), 1, 0, 'L', $fill);
            $pdf->Cell(20, 7, ucfirst($record['tipo']), 1, 0, 'C', $fill);
            $pdf->Cell(20, 7, formatDateTime($record['fecha_hora'], 'H:i'), 1, 0, 'C', $fill);
            $pdf->Cell(45, 7, substr($record['ubicacion_nombre'] ?? 'N/A', 0, 25), 1, 0, 'L', $fill);

            $duration = '-';
            if ($record['tipo'] === 'salida' && !empty($record['duracion_minutos'])) {
                $hours = floor($record['duracion_minutos'] / 60);
                $mins = $record['duracion_minutos'] % 60;
                $duration = sprintf("%02d:%02d", $hours, $mins);
            }
            $pdf->Cell(20, 7, $duration, 1, 1, 'C', $fill);

            $fill = !$fill;
        }

        // Footer with timestamp
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Generado: ' . formatDateTime(getCurrentDateTime()) . ' | Total de registros: ' . count($data), 0, 1, 'R');

        // Output
        $filename = 'reporte_asistencia_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Export Attendance Report to Excel
     */
    private static function exportAttendanceExcel($data, $filters) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'Reporte de Asistencia - Alpe Fresh Mexico');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Filters
        $filterText = 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']);
        if (!empty($filters['user_name'])) {
            $filterText .= ' | Usuario: ' . $filters['user_name'];
        }
        if (!empty($filters['location_name'])) {
            $filterText .= ' | Ubicación: ' . $filters['location_name'];
        }
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', $filterText);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['Fecha', 'Empleado', 'Tipo', 'Hora', 'Ubicación', 'Duración'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        // Header style
        $sheet->getStyle('A4:F4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Data
        $row = 5;
        foreach ($data as $record) {
            $sheet->setCellValue('A' . $row, formatDate($record['fecha_hora']));
            $sheet->setCellValue('B' . $row, $record['nombre'] . ' ' . $record['apellidos']);
            $sheet->setCellValue('C' . $row, ucfirst($record['tipo']));
            $sheet->setCellValue('D' . $row, formatDateTime($record['fecha_hora'], 'H:i'));
            $sheet->setCellValue('E' . $row, $record['ubicacion_nombre'] ?? 'N/A');

            $duration = '-';
            if ($record['tipo'] === 'salida' && !empty($record['duracion_minutos'])) {
                $hours = floor($record['duracion_minutos'] / 60);
                $mins = $record['duracion_minutos'] % 60;
                $duration = sprintf("%02d:%02d", $hours, $mins);
            }
            $sheet->setCellValue('F' . $row, $duration);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Borders
        $sheet->getStyle('A4:F' . ($row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
            ]
        ]);

        // Output
        $filename = 'reporte_asistencia_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Attendance Report to CSV
     */
    private static function exportAttendanceCSV($data, $filters) {
        $filename = 'reporte_asistencia_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Title
        fputcsv($output, ['Reporte de Asistencia - Alpe Fresh Mexico']);
        fputcsv($output, ['Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date'])]);
        fputcsv($output, []); // Empty line

        // Headers
        fputcsv($output, ['Fecha', 'Empleado', 'Tipo', 'Hora', 'Ubicación', 'Duración']);

        // Data
        foreach ($data as $record) {
            $duration = '-';
            if ($record['tipo'] === 'salida' && !empty($record['duracion_minutos'])) {
                $hours = floor($record['duracion_minutos'] / 60);
                $mins = $record['duracion_minutos'] % 60;
                $duration = sprintf("%02d:%02d", $hours, $mins);
            }

            fputcsv($output, [
                formatDate($record['fecha_hora']),
                $record['nombre'] . ' ' . $record['apellidos'],
                ucfirst($record['tipo']),
                formatDateTime($record['fecha_hora'], 'H:i'),
                $record['ubicacion_nombre'] ?? 'N/A',
                $duration
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export Summary Report to PDF
     */
    private static function exportSummaryPDF($data, $filters) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('Sistema de Asistencia - Alpe Fresh');
        $pdf->SetAuthor('Alpe Fresh Mexico');
        $pdf->SetTitle('Reporte Resumen');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->Cell(0, 10, 'Alpe Fresh Mexico', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 16);
        $pdf->Cell(0, 8, 'Reporte Resumen de Horas', 0, 1, 'C');

        // Filters
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 6, 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']), 0, 1, 'C');
        $pdf->Ln(3);

        // Table header
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);

        $pdf->Cell(60, 8, 'Empleado', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Núm. Empleado', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Días Trab.', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Horas Totales', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Promedio Diario', 1, 1, 'C', true);

        // Data
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);

        $fill = false;
        foreach ($data as $record) {
            $pdf->SetFillColor(245, 245, 245);

            $pdf->Cell(60, 7, substr($record['nombre'] . ' ' . $record['apellidos'], 0, 35), 1, 0, 'L', $fill);
            $pdf->Cell(30, 7, $record['numero_empleado'], 1, 0, 'C', $fill);
            $pdf->Cell(25, 7, $record['dias_trabajados'], 1, 0, 'C', $fill);

            $totalHours = floor($record['total_minutos'] / 60);
            $totalMins = $record['total_minutos'] % 60;
            $pdf->Cell(30, 7, sprintf("%dh %dm", $totalHours, $totalMins), 1, 0, 'C', $fill);

            $avgHours = $record['dias_trabajados'] > 0 ? $record['total_minutos'] / $record['dias_trabajados'] : 0;
            $pdf->Cell(35, 7, sprintf("%.1fh", $avgHours / 60), 1, 1, 'C', $fill);

            $fill = !$fill;
        }

        // Footer
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Generado: ' . formatDateTime(getCurrentDateTime()), 0, 1, 'R');

        $filename = 'reporte_resumen_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Export Summary Report to Excel
     */
    private static function exportSummaryExcel($data, $filters) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'Reporte Resumen de Horas - Alpe Fresh Mexico');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Headers
        $headers = ['Empleado', 'Núm. Empleado', 'Días Trabajados', 'Horas Totales', 'Promedio Diario'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        $sheet->getStyle('A4:E4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Data
        $row = 5;
        foreach ($data as $record) {
            $sheet->setCellValue('A' . $row, $record['nombre'] . ' ' . $record['apellidos']);
            $sheet->setCellValue('B' . $row, $record['numero_empleado']);
            $sheet->setCellValue('C' . $row, $record['dias_trabajados']);

            $totalHours = floor($record['total_minutos'] / 60);
            $totalMins = $record['total_minutos'] % 60;
            $sheet->setCellValue('D' . $row, sprintf("%dh %dm", $totalHours, $totalMins));

            $avgHours = $record['dias_trabajados'] > 0 ? $record['total_minutos'] / $record['dias_trabajados'] : 0;
            $sheet->setCellValue('E' . $row, sprintf("%.1fh", $avgHours / 60));

            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A4:E' . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $filename = 'reporte_resumen_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Summary Report to CSV
     */
    private static function exportSummaryCSV($data, $filters) {
        $filename = 'reporte_resumen_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Reporte Resumen de Horas - Alpe Fresh Mexico']);
        fputcsv($output, ['Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date'])]);
        fputcsv($output, []);

        fputcsv($output, ['Empleado', 'Núm. Empleado', 'Días Trabajados', 'Horas Totales', 'Promedio Diario']);

        foreach ($data as $record) {
            $totalHours = floor($record['total_minutos'] / 60);
            $totalMins = $record['total_minutos'] % 60;
            $avgHours = $record['dias_trabajados'] > 0 ? $record['total_minutos'] / $record['dias_trabajados'] : 0;

            fputcsv($output, [
                $record['nombre'] . ' ' . $record['apellidos'],
                $record['numero_empleado'],
                $record['dias_trabajados'],
                sprintf("%dh %dm", $totalHours, $totalMins),
                sprintf("%.1fh", $avgHours / 60)
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export Location Report to PDF
     */
    private static function exportLocationPDF($data, $filters) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('Sistema de Asistencia - Alpe Fresh');
        $pdf->SetAuthor('Alpe Fresh Mexico');
        $pdf->SetTitle('Reporte por Ubicación');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->Cell(0, 10, 'Alpe Fresh Mexico', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 16);
        $pdf->Cell(0, 8, 'Reporte por Ubicación', 0, 1, 'C');

        // Filters
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 6, 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']), 0, 1, 'C');
        $pdf->Ln(3);

        // Table header
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);

        $pdf->Cell(70, 8, 'Ubicación', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Total Entradas', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Total Salidas', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Empleados Únicos', 1, 1, 'C', true);

        // Data
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);

        $fill = false;
        foreach ($data as $record) {
            $pdf->SetFillColor(245, 245, 245);

            $pdf->Cell(70, 7, substr($record['ubicacion_nombre'], 0, 40), 1, 0, 'L', $fill);
            $pdf->Cell(30, 7, $record['total_entradas'], 1, 0, 'C', $fill);
            $pdf->Cell(30, 7, $record['total_salidas'], 1, 0, 'C', $fill);
            $pdf->Cell(40, 7, $record['empleados_unicos'], 1, 1, 'C', $fill);

            $fill = !$fill;
        }

        // Footer
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Generado: ' . formatDateTime(getCurrentDateTime()), 0, 1, 'R');

        $filename = 'reporte_ubicacion_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Export Location Report to Excel
     */
    private static function exportLocationExcel($data, $filters) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'Reporte por Ubicación - Alpe Fresh Mexico');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['Ubicación', 'Total Entradas', 'Total Salidas', 'Empleados Únicos'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        $sheet->getStyle('A4:D4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $row = 5;
        foreach ($data as $record) {
            $sheet->setCellValue('A' . $row, $record['ubicacion_nombre']);
            $sheet->setCellValue('B' . $row, $record['total_entradas']);
            $sheet->setCellValue('C' . $row, $record['total_salidas']);
            $sheet->setCellValue('D' . $row, $record['empleados_unicos']);
            $row++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A4:D' . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $filename = 'reporte_ubicacion_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Location Report to CSV
     */
    private static function exportLocationCSV($data, $filters) {
        $filename = 'reporte_ubicacion_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Reporte por Ubicación - Alpe Fresh Mexico']);
        fputcsv($output, ['Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date'])]);
        fputcsv($output, []);

        fputcsv($output, ['Ubicación', 'Total Entradas', 'Total Salidas', 'Empleados Únicos']);

        foreach ($data as $record) {
            fputcsv($output, [
                $record['ubicacion_nombre'],
                $record['total_entradas'],
                $record['total_salidas'],
                $record['empleados_unicos']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export Incomplete Sessions Report (Missing Clock-Outs)
     */
    public static function exportIncompleteSessionsReport($data, $filters, $format = 'pdf') {
        switch ($format) {
            case 'pdf':
                self::exportIncompleteSessionsPDF($data, $filters);
                break;
            case 'excel':
                self::exportIncompleteSessionsExcel($data, $filters);
                break;
            case 'csv':
                self::exportIncompleteSessionsCSV($data, $filters);
                break;
        }
    }

    private static function exportIncompleteSessionsPDF($data, $filters) {
        $pdf = self::createPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetTitle('Reporte de Salidas Faltantes');
        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Reporte de Salidas Faltantes', 0, 1, 'C');
        $pdf->Ln(5);

        // Filters
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']), 0, 1);
        $pdf->Ln(5);

        // Description
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->MultiCell(0, 5, 'Este reporte muestra empleados que registraron entrada pero no han registrado salida (sesiones incompletas).', 0, 'L');
        $pdf->Ln(3);

        if (empty($data)) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No se encontraron registros de salidas faltantes en el período seleccionado.', 0, 1, 'C');
        } else {
            // Table Header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(0, 51, 102);
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Cell(20, 7, 'No. Emp.', 1, 0, 'C', true);
            $pdf->Cell(45, 7, 'Empleado', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Fecha Entrada', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Hora Entrada', 1, 0, 'C', true);
            $pdf->Cell(40, 7, 'Ubicación', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Hrs Transcurridas', 1, 1, 'C', true);

            // Table Body
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;

            foreach ($data as $record) {
                $pdf->Cell(20, 6, $record['numero_empleado'], 1, 0, 'C', $fill);
                $pdf->Cell(45, 6, mb_substr($record['nombre'] . ' ' . $record['apellidos'], 0, 30), 1, 0, 'L', $fill);
                $pdf->Cell(30, 6, formatDate($record['fecha_entrada']), 1, 0, 'C', $fill);
                $pdf->Cell(25, 6, date('H:i', strtotime($record['hora_entrada'])), 1, 0, 'C', $fill);
                $pdf->Cell(40, 6, mb_substr($record['ubicacion_nombre'] ?? 'N/A', 0, 25), 1, 0, 'L', $fill);
                $pdf->Cell(30, 6, $record['horas_transcurridas'] . ' hrs', 1, 1, 'C', $fill);
                $fill = !$fill;
            }

            // Summary
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'Total de sesiones incompletas: ' . count($data), 0, 1);
        }

        $pdf->Output('reporte_salidas_faltantes_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    private static function exportIncompleteSessionsExcel($data, $filters) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Salidas Faltantes');

        // Title
        $sheet->setCellValue('A1', 'REPORTE DE SALIDAS FALTANTES');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Filters
        $sheet->setCellValue('A3', 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']));

        // Headers
        $headers = ['No. Emp.', 'Empleado', 'Fecha Entrada', 'Hora Entrada', 'Ubicación', 'Hrs Transcurridas'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true);
            $sheet->getStyle($col . '5')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('003366');
            $sheet->getStyle($col . '5')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Data
        $row = 6;
        foreach ($data as $record) {
            $sheet->setCellValue('A' . $row, $record['numero_empleado']);
            $sheet->setCellValue('B' . $row, $record['nombre'] . ' ' . $record['apellidos']);
            $sheet->setCellValue('C' . $row, formatDate($record['fecha_entrada']));
            $sheet->setCellValue('D' . $row, date('H:i', strtotime($record['hora_entrada'])));
            $sheet->setCellValue('E' . $row, $record['ubicacion_nombre'] ?? 'N/A');
            $sheet->setCellValue('F' . $row, $record['horas_transcurridas'] . ' hrs');
            $row++;
        }

        // Summary
        $row++;
        $sheet->setCellValue('A' . $row, 'Total: ' . count($data) . ' sesiones incompletas');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        $filename = 'reporte_salidas_faltantes_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private static function exportIncompleteSessionsCSV($data, $filters) {
        $filename = 'reporte_salidas_faltantes_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        fputcsv($output, ['REPORTE DE SALIDAS FALTANTES']);
        fputcsv($output, []);
        fputcsv($output, ['Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date'])]);
        fputcsv($output, []);

        fputcsv($output, ['No. Emp.', 'Empleado', 'Fecha Entrada', 'Hora Entrada', 'Ubicación', 'Hrs Transcurridas']);

        foreach ($data as $record) {
            fputcsv($output, [
                $record['numero_empleado'],
                $record['nombre'] . ' ' . $record['apellidos'],
                formatDate($record['fecha_entrada']),
                date('H:i', strtotime($record['hora_entrada'])),
                $record['ubicacion_nombre'] ?? 'N/A',
                $record['horas_transcurridas'] . ' hrs'
            ]);
        }

        fputcsv($output, []);
        fputcsv($output, ['Total', count($data) . ' sesiones incompletas']);

        fclose($output);
        exit;
    }

    /**
     * Export Geofence Violations Report
     */
    public static function exportGeofenceViolationsReport($data, $filters, $format = 'pdf') {
        switch ($format) {
            case 'pdf':
                self::exportGeofenceViolationsPDF($data, $filters);
                break;
            case 'excel':
                self::exportGeofenceViolationsExcel($data, $filters);
                break;
            case 'csv':
                self::exportGeofenceViolationsCSV($data, $filters);
                break;
        }
    }

    private static function exportGeofenceViolationsPDF($data, $filters) {
        $pdf = self::createPDF('L', 'mm', 'A4', true, 'UTF-8'); // Landscape for more columns
        $pdf->SetTitle('Reporte de Violaciones de Geovalla');
        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Reporte de Violaciones de Geovalla', 0, 1, 'C');
        $pdf->Ln(5);

        // Filters
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']), 0, 1);
        $pdf->Ln(5);

        // Description
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->MultiCell(0, 5, 'Este reporte muestra salidas registradas fuera de las áreas autorizadas (geovalla).', 0, 'L');
        $pdf->Ln(3);

        if (empty($data)) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No se encontraron violaciones de geovalla en el período seleccionado.', 0, 1, 'C');
        } else {
            // Table Header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(220, 53, 69); // Red theme for violations
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Cell(20, 7, 'No. Emp.', 1, 0, 'C', true);
            $pdf->Cell(50, 7, 'Empleado', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Fecha', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Hora Salida', 1, 0, 'C', true);
            $pdf->Cell(50, 7, 'Ubicación Asignada', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Distancia', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Precisión GPS', 1, 1, 'C', true);

            // Table Body
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            $fill = false;

            foreach ($data as $record) {
                $pdf->Cell(20, 6, $record['numero_empleado'], 1, 0, 'C', $fill);
                $pdf->Cell(50, 6, mb_substr($record['nombre'] . ' ' . $record['apellidos'], 0, 35), 1, 0, 'L', $fill);
                $pdf->Cell(30, 6, formatDate($record['fecha_registro']), 1, 0, 'C', $fill);
                $pdf->Cell(25, 6, date('H:i', strtotime($record['fecha_hora'])), 1, 0, 'C', $fill);
                $pdf->Cell(50, 6, mb_substr($record['ubicacion_nombre'] ?? 'N/A', 0, 35), 1, 0, 'L', $fill);
                $pdf->Cell(30, 6, round($record['distancia_ubicacion'] ?? 0) . ' m', 1, 0, 'C', $fill);
                $pdf->Cell(25, 6, round($record['precision_gps']) . ' m', 1, 1, 'C', $fill);
                $fill = !$fill;
            }

            // Summary
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'Total de violaciones: ' . count($data), 0, 1);
        }

        $pdf->Output('reporte_violaciones_geovalla_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }

    private static function exportGeofenceViolationsExcel($data, $filters) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Violaciones Geovalla');

        // Title
        $sheet->setCellValue('A1', 'REPORTE DE VIOLACIONES DE GEOVALLA');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Filters
        $sheet->setCellValue('A3', 'Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date']));

        // Headers
        $headers = ['No. Emp.', 'Empleado', 'Fecha', 'Hora Salida', 'Ubicación Asignada', 'Distancia', 'Precisión GPS'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true);
            $sheet->getStyle($col . '5')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DC3545'); // Red for violations
            $sheet->getStyle($col . '5')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Data
        $row = 6;
        foreach ($data as $record) {
            $sheet->setCellValue('A' . $row, $record['numero_empleado']);
            $sheet->setCellValue('B' . $row, $record['nombre'] . ' ' . $record['apellidos']);
            $sheet->setCellValue('C' . $row, formatDate($record['fecha_registro']));
            $sheet->setCellValue('D' . $row, date('H:i', strtotime($record['fecha_hora'])));
            $sheet->setCellValue('E' . $row, $record['ubicacion_nombre'] ?? 'N/A');
            $sheet->setCellValue('F' . $row, round($record['distancia_ubicacion'] ?? 0) . ' m');
            $sheet->setCellValue('G' . $row, round($record['precision_gps']) . ' m');
            $row++;
        }

        // Summary
        $row++;
        $sheet->setCellValue('A' . $row, 'Total: ' . count($data) . ' violaciones');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        $filename = 'reporte_violaciones_geovalla_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private static function exportGeofenceViolationsCSV($data, $filters) {
        $filename = 'reporte_violaciones_geovalla_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        fputcsv($output, ['REPORTE DE VIOLACIONES DE GEOVALLA']);
        fputcsv($output, []);
        fputcsv($output, ['Período: ' . formatDate($filters['start_date']) . ' - ' . formatDate($filters['end_date'])]);
        fputcsv($output, []);

        fputcsv($output, ['No. Emp.', 'Empleado', 'Fecha', 'Hora Salida', 'Ubicación Asignada', 'Distancia', 'Precisión GPS']);

        foreach ($data as $record) {
            fputcsv($output, [
                $record['numero_empleado'],
                $record['nombre'] . ' ' . $record['apellidos'],
                formatDate($record['fecha_registro']),
                date('H:i', strtotime($record['fecha_hora'])),
                $record['ubicacion_nombre'] ?? 'N/A',
                round($record['distancia_ubicacion'] ?? 0) . ' m',
                round($record['precision_gps']) . ' m'
            ]);
        }

        fputcsv($output, []);
        fputcsv($output, ['Total', count($data) . ' violaciones']);

        fclose($output);
        exit;
    }
}
