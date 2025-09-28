<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithColumnFormatting,
    WithStyles
{
    protected int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function collection()
    {
        // We can fetch in any order; mapping controls the output order
        return Expense::where('user_id', $this->userId)
            ->orderBy('date', 'desc')
            ->get(['date', 'category', 'description', 'amount']);
    }

    /** Column titles (and their order) */
    public function headings(): array
    {
        // 👉 Amount, Category, Description, Date
        return ['Amount', 'Category', 'Description', 'Date'];
    }

    /** Row mapping to match the order above */
    public function map($row): array
    {
        $amount = number_format((float)$row->amount, 2, '.', '');
        $date   = $row->date ? (string) \Carbon\Carbon::parse($row->date)->format('Y-m-d') : '';

        return [
            $amount,            // A
            $row->category,     // B
            $row->description,  // C
            $date,              // D
        ];
    }

    /** Auto-size number/date formatting (optional but nice) */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER_00,       // Amount
            'D' => NumberFormat::FORMAT_DATE_YYYYMMDD2,  // Date
        ];
    }

    /** Bold header row */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
