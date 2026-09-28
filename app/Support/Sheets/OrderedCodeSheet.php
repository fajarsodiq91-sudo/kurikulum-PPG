<?php

namespace App\Support\Sheets;

use Illuminate\Database\Eloquent\Model;

/**
 * Master lists identified by a unique code and shown in a fixed order (jenjang, kelas).
 */
abstract class OrderedCodeSheet extends Sheet
{
    /**
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    public function headings(): array
    {
        return ['name', 'code', 'sort_order', 'status', 'description'];
    }

    public function rows(): iterable
    {
        foreach ($this->model()::orderBy('sort_order')->orderBy('name')->cursor() as $item) {
            yield [$item->name, $item->code, $item->sort_order, self::activeLabel($item->is_active), $item->description];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $this->model()::updateOrCreate(['code' => trim($data['code'])], [
            'name' => trim($data['name']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => self::parseActive($data['status'] ?? 'aktif'),
            'description' => $data['description'] ?? null,
        ]);

        return null;
    }
}
