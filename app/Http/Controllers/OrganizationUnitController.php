<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\OrganizationUnit;
use App\Models\Region;
use App\Models\User;
use App\Models\Village;
use App\Support\Access;
use App\Support\Sheets\OrganizationUnitSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrganizationUnitController extends Controller
{
    use HandlesSheets;

    public function index(Request $request): View
    {
        $user = $request->user();
        $region = Region::query()->where('is_active', true)->orderBy('name')->first();
        $isGlobal = $user->hasGlobalAccess('view-organization-units');

        $villagesQuery = $region
            ? Village::query()
                ->where('region_id', $region->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->with(['groups' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            : null;

        if ($villagesQuery !== null && ! $isGlobal) {
            $villagesQuery->whereIn('id', $user->scopedPlacementIds('view-organization-units')['village_id'] ?? []);
        }

        return view('organization-units.index', [
            'region' => $region,
            'isGlobal' => $isGlobal,
            'villages' => $villagesQuery?->get() ?? collect(),
            'canManageTasks' => Access::can('manage-tasks'),
            'canManageOrganizationUnits' => Access::can('manage-organization-units'),
            'organizationUnits' => OrganizationUnit::query()->orderBy('name')->get(),
            'pengurusDaerah' => $this->activePengurus('ppg', 'global'),
            'perwakilanDesa' => $this->activePengurus('perwakilan-ppg-desa', 'village'),
            'pelaksanaKelompok' => $this->activePengurus('pelaksana-ppg-kelompok', 'group'),
        ]);
    }

    /**
     * Active users holding the given role at the given scope level, grouped by scope id
     * (or 'global' when unscoped), ordered by their jabatan's sort order.
     *
     * @return Collection<int|string, Collection<int, object>>
     */
    private function activePengurus(string $roleSlug, string $scopeType): Collection
    {
        return DB::table('user_roles')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->leftJoin('dapukans', 'dapukans.id', '=', 'user_roles.dapukan_id')
            ->where('users.status', 'active')
            ->where('roles.slug', $roleSlug)
            ->where('roles.is_active', true)
            ->where('user_roles.is_active', true)
            ->where('user_roles.scope_type', $scopeType)
            ->orderBy('dapukans.sort_order')
            ->orderBy('users.name')
            ->get([
                'users.id as user_id',
                'users.name as user_name',
                'users.email as email',
                'users.photo as photo',
                'users.whatsapp as whatsapp',
                'user_roles.scope_id as scope_id',
                'dapukans.name as jabatan',
                'dapukans.group_label as group_label',
            ])
            ->map(function (object $row): object {
                $row->whatsapp_url = User::whatsappUrl($row->whatsapp);

                return $row;
            })
            ->groupBy(fn (object $row): int|string => $row->scope_id ?? 'global');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:organization_units,code'],
            'leader_name' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        OrganizationUnit::create($validated);

        return redirect()->route('organization-units.index')->with('success', 'Unit organisasi berhasil ditambahkan.');
    }

    public function export(): BinaryFileResponse
    {
        return $this->exportSheet(new OrganizationUnitSheet);
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new OrganizationUnitSheet);
    }
}
