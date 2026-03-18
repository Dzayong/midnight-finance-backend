<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class TransactionTemplateExport implements WithHeadings, WithEvents
{
    protected $categories;
    protected $accounts;

    public function __construct($categories, $accounts)
    {
        $this->categories = $categories;
        $this->accounts = $accounts;
    }

    public function headings(): array
    {
        return [
            'Tanggal (YYYY-MM-DD)',
            'Kategori', // 👈 Namanya kita ganti, nggak pakai kata 'ID' lagi
            'Dompet',   // 👈 Ini juga
            'Nominal',
            'Tipe',
            'Keterangan',
            '',
            '👉 REFERENSI KATEGORI',
            '👉 REFERENSI DOMPET'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1. TULIS DAFTAR REFERENSI DULU DI KOLOM H & I
                $rowCat = 2;
                foreach($this->categories as $cat) {
                    $sheet->setCellValue('H'.$rowCat, $cat->id . ' - ' . $cat->name . ' (' . $cat->type . ')');
                    $rowCat++;
                }

                $rowAcc = 2;
                foreach($this->accounts as $acc) {
                    $sheet->setCellValue('I'.$rowAcc, $acc->id . ' - ' . $acc->name);
                    $rowAcc++;
                }

                // 2. BIKIN DROPDOWN KATEGORI (KOLOM B) MENGAMBIL DATA DARI KOLOM H
                $valKategori = $sheet->getCell('B2')->getDataValidation();
                $valKategori->setType(DataValidation::TYPE_LIST);
                $valKategori->setErrorStyle(DataValidation::STYLE_STOP);
                $valKategori->setAllowBlank(false);
                $valKategori->setShowDropDown(true);
                // Rumus Excel: Ambil daftar dari H2 sampai H(jumlah kategori)
                $valKategori->setFormula1('=$H$2:$H$' . ($rowCat - 1));
                $sheet->setDataValidation('B2:B500', $valKategori);

                // 3. BIKIN DROPDOWN DOMPET (KOLOM C) MENGAMBIL DATA DARI KOLOM I
                $valDompet = $sheet->getCell('C2')->getDataValidation();
                $valDompet->setType(DataValidation::TYPE_LIST);
                $valDompet->setErrorStyle(DataValidation::STYLE_STOP);
                $valDompet->setAllowBlank(false);
                $valDompet->setShowDropDown(true);
                // Rumus Excel: Ambil daftar dari I2 sampai I(jumlah dompet)
                $valDompet->setFormula1('=$I$2:$I$' . ($rowAcc - 1));
                $sheet->setDataValidation('C2:C500', $valDompet);

                // 4. DROPDOWN TIPE (KOLOM E)
                $valTipe = $sheet->getCell('E2')->getDataValidation();
                $valTipe->setType(DataValidation::TYPE_LIST);
                $valTipe->setErrorStyle(DataValidation::STYLE_STOP);
                $valTipe->setAllowBlank(false);
                $valTipe->setShowDropDown(true);
                $valTipe->setFormula1('"income,expense"');
                $sheet->setDataValidation('E2:E500', $valTipe);

                // 5. PERCANTIK LEBAR KOLOM
                $sheet->getColumnDimension('A')->setWidth(22);
                $sheet->getColumnDimension('B')->setWidth(35);
                $sheet->getColumnDimension('C')->setWidth(25);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(30);
                $sheet->getColumnDimension('H')->setWidth(40);
                $sheet->getColumnDimension('I')->setWidth(25);

                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FFFFFF00']]
                ]);
            }
        ];
    }
}
