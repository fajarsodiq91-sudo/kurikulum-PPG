<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\Guardian;
use App\Support\Sheets\GuardianSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuardianController extends Controller
{
    use HandlesSheets;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $relationship = $request->query('relationship', '');
        $status = $request->query('status', '');

        $guardians = Guardian::visibleTo($request->user())
            ->with('generus:id,full_name')
            ->when($search !== '', fn ($query) => $query->where('full_name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($relationship !== '', fn ($query) => $query->where('relationship', $relationship))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('guardians.index', [
            'guardians' => $guardians,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Guardian::create($this->validateGuardian($request));

        return redirect()->route('guardians.index')->with('success', 'Data orang tua/wali berhasil ditambahkan.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportSheet(new GuardianSheet($request->user()));
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new GuardianSheet($request->user()));
    }

    public function edit(Request $request, Guardian $guardian): View
    {
        $this->ensureManageable($request, $guardian);

        return view('guardians.edit', ['guardian' => $guardian]);
    }

    public function update(Request $request, Guardian $guardian): RedirectResponse
    {
        $this->ensureManageable($request, $guardian);

        $guardian->update($this->validateGuardian($request));

        return redirect()->route('guardians.index')->with('success', 'Data orang tua/wali berhasil diperbarui.');
    }

    public function destroy(Request $request, Guardian $guardian): RedirectResponse
    {
        $this->ensureManageable($request, $guardian);

        $guardian->delete();

        return redirect()->route('guardians.index')->with('success', 'Data orang tua/wali berhasil dihapus.');
    }

    /**
     * Narrower than Guardian::visibleTo(): a kelompok executor's view-guardians permission
     * widens to their whole desa (see User::scopedPlacementIdsAny()), but editing or deleting a
     * guardian must stay within their manage-guardians scope alone.
     */
    private function ensureManageable(Request $request, Guardian $guardian): void
    {
        $user = $request->user();

        if ($user?->hasGlobalAccess('manage-guardians')) {
            return;
        }

        $placementIds = $user?->scopedPlacementIds('manage-guardians') ?? [];

        abort_unless($placementIds !== [] && $guardian->generus()->inPlacement($placementIds)->exists(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGuardian(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
