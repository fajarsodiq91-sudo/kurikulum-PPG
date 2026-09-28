<?php

namespace App\Support;

use App\Models\Generus;
use App\Models\Teacher;
use App\Models\User;

/**
 * Resolves the login account behind an RFID card or a face, for signing in without a password.
 * Only member accounts (generus and teachers) qualify, and never one that can manage users or
 * roles, so a cloned card or a photo can not open an administrator's account.
 */
class MemberLogin
{
    public static function forRfid(?string $uid): ?User
    {
        $uid = Generus::normalizeRfidUid($uid);

        if ($uid === null) {
            return null;
        }

        $generus = Generus::where('rfid_uid', $uid)->where('status', 'active')->first();
        $teacher = $generus === null ? Teacher::where('rfid_uid', $uid)->where('status', 'active')->first() : null;

        return self::eligible(self::accountOf($generus, $teacher));
    }

    /**
     * @param  list<float>  $descriptor
     */
    public static function forFace(array $descriptor): ?User
    {
        $candidates = collect()
            ->concat(Generus::where('status', 'active')->whereNotNull('face_descriptor')->get(['id', 'face_descriptor']))
            ->concat(Teacher::where('status', 'active')->whereNotNull('face_descriptor')->get(['id', 'face_descriptor']))
            ->map(fn (Generus|Teacher $member): array => [
                'member' => $member,
                'distance' => FaceDescriptors::distance($descriptor, FaceDescriptors::parse($member->face_descriptor) ?? array_fill(0, FaceDescriptors::SIZE, 9.0)),
            ])
            ->sortBy('distance')
            ->values();

        $best = $candidates->first();

        if ($best === null || $best['distance'] > FaceDescriptors::LOGIN_THRESHOLD) {
            return null;
        }

        // A close runner-up means the face is ambiguous (e.g. siblings); refuse instead of guessing.
        $runnerUp = $candidates->get(1);

        if ($runnerUp !== null && $runnerUp['distance'] - $best['distance'] < 0.04) {
            return null;
        }

        $member = $best['member'];

        return self::eligible(self::accountOf($member instanceof Generus ? $member : null, $member instanceof Teacher ? $member : null));
    }

    private static function accountOf(?Generus $generus, ?Teacher $teacher): ?User
    {
        return match (true) {
            $generus !== null => User::where('generus_id', $generus->id)->first(),
            $teacher !== null => User::where('teacher_id', $teacher->id)->first(),
            default => null,
        };
    }

    private static function eligible(?User $user): ?User
    {
        if ($user === null || $user->status !== 'active' || $user->hasPermission('manage-users')) {
            return null;
        }

        return $user;
    }
}
