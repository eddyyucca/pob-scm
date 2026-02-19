<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TemplateController extends Controller
{
    public function employeeTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Karyawan POB');

        // Header styling
        $headers = ['ID / MinePermit / KTP', 'Nama Lengkap', 'Jabatan', 'Departemen', 'Tipe (employee/visitor)'];
        $cols = ['A','B','C','D','E'];

        foreach ($headers as $i => $h) {
            $cell = $cols[$i] . '1';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a3c5e']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']]],
            ]);
            $sheet->getColumnDimension($cols[$i])->setWidth($i === 1 ? 30 : ($i === 0 ? 22 : 20));
        }

        // Contoh data
        $samples = [
            ['MP-2024-001234', 'Ahmad Fauzi', 'Operator Alat Berat', 'Mining Operations', 'employee'],
            ['MP-2024-005678', 'Budi Santoso', 'Supervisor', 'Safety', 'employee'],
            ['3212345678901234', 'Cindy Wijaya', 'Tamu / Auditor', 'Eksternal', 'visitor'],
            ['MP-2024-009999', 'Dedi Kurniawan', 'Mekanik', 'Maintenance', 'employee'],
        ];

        foreach ($samples as $r => $row) {
            foreach ($row as $c => $val) {
                $cell = $cols[$c] . ($r + 2);
                $sheet->setCellValue($cell, $val);
                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $r % 2 === 0 ? 'F8FAFC' : 'FFFFFF']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
                ]);
            }
        }

        // Instruksi di bawah
        $sheet->setCellValue('A7', 'CATATAN:');
        $sheet->getStyle('A7')->getFont()->setBold(true);
        $sheet->setCellValue('A8', '- Kolom Nama Lengkap WAJIB diisi');
        $sheet->setCellValue('A9', '- ID: isi dengan nomor MinePermit (contoh: MP-2024-XXXXXX) atau KTP (16 digit angka) untuk visitor');
        $sheet->setCellValue('A10','- Tipe: isi "employee" untuk karyawan, "visitor" untuk tamu/non-karyawan');
        $sheet->mergeCells('A7:E7');
        $sheet->mergeCells('A8:E8');
        $sheet->mergeCells('A9:E9');
        $sheet->mergeCells('A10:E10');

        $writer = new Xlsx($spreadsheet);
        $filename = 'Template_Karyawan_POB.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
