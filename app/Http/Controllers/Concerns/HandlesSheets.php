<?php

namespace App\Http\Controllers\Concerns;

use App\Exports\SheetExport;
use App\Imports\SheetImport;
use App\Support\Sheets\Sheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait HandlesSheets
{
    protected function exportSheet(Sheet $sheet): BinaryFileResponse
    {
        return Excel::download(new SheetExport($sheet), $sheet->filename());
    }

    /**
     * All-or-nothing: any invalid row rolls back the whole file and is reported by row number.
     */
    protected function importSheet(Request $request, Sheet $sheet): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        DB::transaction(function () use ($validated, $sheet): void {
            $import = new SheetImport($sheet);

            Excel::import($import, $validated['file']);

            if ($import->errors() !== []) {
                throw ValidationException::withMessages($import->errors());
            }
        });

        return back()->with('success', 'Data berhasil diimpor dari XLSX.');
    }
}
