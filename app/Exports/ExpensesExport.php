<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ExpensesExport implements FromCollection, WithHeadings, WithEvents
{
    public function __construct(protected Collection $rows) {}

    public function collection()
    {

        return $this->rows->map(function ($e) {
            return [
                (string) $e->id,
                (string) $e->amount,
                (string) $e->category,
                (string) $e->description,
                is_string($e->date) ? $e->date : optional($e->date)->toDateString(),
                optional($e->created_at)->toDateTimeString(),
            ];
        });
    }

    public function headings(): array
    {
        return ['ID', 'Amount', 'Category', 'Description', 'Date', 'Created At'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();


                $data = collect([$this->headings()])->merge($this->collection());


                $columns = ['A','B','C','D','E','F'];


                $maxLens = array_fill_keys($columns, 0);

                foreach ($data as $r) {
                    foreach ($columns as $i => $col) {
                        $val = isset($r[$i]) ? (string)$r[$i] : '';
                        $len = mb_strlen($val);
                        if ($len > $maxLens[$col]) {
                            $maxLens[$col] = $len;
                        }
                    }
                }


                foreach ($columns as $col) {
                    $padding  = 2;
                    $maxWidth = ($col === 'D') ? 60 : 30; // Description can be wider
                    $width    = min($maxLens[$col] + $padding, $maxWidth);
                    if ($width < 8) $width = 8; // minimum width
                    $sheet->getColumnDimension($col)->setWidth($width);
                }


                $sheet->getStyle('D1:D' . (count($data)))
                      ->getAlignment()
                      ->setWrapText(true);


                $sheet->getStyle('A1:F1')->getFont()->setBold(true);
            },
        ];
    }
}
