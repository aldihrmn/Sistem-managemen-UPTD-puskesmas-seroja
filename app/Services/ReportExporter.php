<?php

namespace App\Services;

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportExporter
{
    public static function pdf(array $meta): string
    {
        $pdf = new Dompdf();
        $pdf->loadHtml(view('reports.document', $meta + ['autoPrint' => false])->render());
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return $pdf->output();
    }

    public static function excel(array $meta): Xlsx
    {
        $report = $meta['report'];
        $cols = ReportService::columns();

        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Laporan Bulanan');

        $sh->setCellValue('A1', $meta['institution']);
        $sh->setCellValue('A2', 'Laporan Bulanan Kehadiran Pegawai');
        $sh->setCellValue('A3', 'Periode: '.$meta['periodLabel']);
        $sh->setCellValue('A4', 'Dibuat: '.$meta['generatedAt']->format('d/m/Y H:i').' oleh '.$meta['creator']);
        $sh->getStyle('A1:A2')->getFont()->setBold(true);

        $data = [array_merge(['No'], array_keys($cols))];
        foreach ($report['rows'] as $i => $row) {
            $data[] = array_merge([$i + 1], array_map(fn ($k) => $row[$k], array_values($cols)));
        }
        $sh->fromArray($data, null, 'A6');

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($data[0]));
        $sh->getStyle("A6:{$lastCol}6")->getFont()->setBold(true);
        for ($i = 1; $i <= count($data[0]); $i++) {
            $sh->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $sheet2 = $ss->createSheet();
        $sheet2->setTitle('Per Unit Kerja');
        $d2 = [['Unit Kerja', 'Pegawai', 'Hari Kerja', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Cuti', 'Tidak Hadir', 'Lembur (jam)']];
        foreach ($report['byDept'] as $r) {
            $d2[] = [$r['department'], $r['employees'], $r['workdays'], $r['present'], $r['late'], $r['izin'], $r['sakit'], $r['cuti'], $r['absent'], $r['overtime_hours']];
        }
        $sheet2->fromArray($d2, null, 'A1');
        $sheet2->getStyle('A1:J1')->getFont()->setBold(true);

        return new Xlsx($ss);
    }
}
