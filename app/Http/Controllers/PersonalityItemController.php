<?php

namespace App\Http\Controllers;

use App\Models\PersonalityItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonalityItemController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $items = PersonalityItem::query()
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('personality-items.index', [
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:personality_items,code'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sort_order'] ??= 0;

        PersonalityItem::create($validated);

        return redirect()->route('personality-items.index')->with('success', 'Item kepribadian berhasil ditambahkan.');
    }

    public function update(Request $request, PersonalityItem $personality_item): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('personality_items', 'code')->ignore($personality_item->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sort_order'] ??= 0;

        $personality_item->update($validated);

        return redirect()->route('personality-items.index')->with('success', 'Item kepribadian berhasil diperbarui.');
    }

    public function destroy(PersonalityItem $personality_item): RedirectResponse
    {
        try {
            $personality_item->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return redirect()->route('personality-items.index')->withErrors([
                    'delete' => 'Item kepribadian tidak dapat dihapus karena masih digunakan oleh data lain.',
                ]);
            }

            throw $e;
        }

        return redirect()->route('personality-items.index')->with('success', 'Item kepribadian berhasil dihapus.');
    }
}
