<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\Teacher;
use App\Support\FaceDescriptors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds face data for members whose photo predates face login. The browser reads each photo
 * (the server can not) and posts the resulting descriptor back.
 */
class FaceEnrollmentController extends Controller
{
    public function index(): View
    {
        $pending = collect(['generus' => Generus::class, 'teacher' => Teacher::class])
            ->flatMap(fn (string $model, string $type) => $model::query()
                ->whereNotNull('photo')->whereNull('face_descriptor')
                ->get()
                ->map(fn (Model $member): array => [
                    'type' => $type,
                    'id' => $member->id,
                    'name' => $member->full_name ?? $member->name,
                    'photo' => route('face-enrollment.photo', [$type, $member->id]),
                    'store' => route('face-enrollment.store', [$type, $member->id]),
                ]))
            ->values();

        return view('face-enrollment.index', [
            'pending' => $pending,
            'enrolled' => Generus::whereNotNull('face_descriptor')->count() + Teacher::whereNotNull('face_descriptor')->count(),
        ]);
    }

    public function photo(string $type, int $id): StreamedResponse
    {
        $member = $this->member($type, $id);

        abort_if(blank($member->photo) || ! Storage::exists($member->photo), 404);

        return Storage::response($member->photo);
    }

    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $member = $this->member($type, $id);
        $validated = $request->validate(['descriptor' => ['required', FaceDescriptors::rule()]]);

        $member->update(['face_descriptor' => FaceDescriptors::parse($validated['descriptor'])]);

        return response()->json(['ok' => true]);
    }

    private function member(string $type, int $id): Generus|Teacher
    {
        return match ($type) {
            'generus' => Generus::findOrFail($id),
            'teacher' => Teacher::findOrFail($id),
            default => abort(404),
        };
    }
}
