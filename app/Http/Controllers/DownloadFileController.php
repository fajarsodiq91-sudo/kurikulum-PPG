<?php

namespace App\Http\Controllers;

use App\Models\DownloadFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DownloadFileController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $files = DownloadFile::query()
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where('title', 'like', $like);
            })
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(25)
            ->withQueryString();

        return view('download-files.index', [
            'files' => $files,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file_url' => ['required', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sort_order'] ??= 0;

        DownloadFile::create($validated);

        return redirect()->route('download-files.index')->with('success', 'File unduhan berhasil ditambahkan.');
    }

    public function update(Request $request, DownloadFile $download_file): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file_url' => ['required', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sort_order'] ??= 0;

        $download_file->update($validated);

        return redirect()->route('download-files.index')->with('success', 'File unduhan berhasil diperbarui.');
    }

    public function destroy(DownloadFile $download_file): RedirectResponse
    {
        $download_file->delete();

        return redirect()->route('download-files.index')->with('success', 'File unduhan berhasil dihapus.');
    }
}
