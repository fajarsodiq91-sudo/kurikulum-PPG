<?php

namespace App\Console\Commands;

use App\Models\Generus;
use App\Models\Teacher;
use App\Support\MemberAccounts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('accounts:backfill')]
#[Description('Create login accounts (username and password = registration number) for generus and teachers that have none')]
class BackfillMemberAccounts extends Command
{
    public function handle(): int
    {
        Generus::query()->each(fn (Generus $generus) => MemberAccounts::forGenerus($generus));
        Teacher::query()->each(fn (Teacher $teacher) => MemberAccounts::forTeacher($teacher));

        $this->info('Akun anggota selesai disinkronkan.');

        return self::SUCCESS;
    }
}
