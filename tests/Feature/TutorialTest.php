<?php

namespace Tests\Feature;

use App\Models\Generus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_only_general_tutorial_topics(): void
    {
        $this->get('/tutorial')
            ->assertOk()
            ->assertSee('Mulai Menggunakan Aplikasi')
            ->assertSee('Pertanyaan Umum')
            ->assertDontSee('Pengguna dan Peran')
            ->assertDontSee('Menambah data master');
    }

    public function test_topics_follow_the_users_permissions(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@ppg.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/tutorial')
            ->assertOk()
            ->assertSee('Pengguna dan Peran')
            ->assertSee('Menambah data master')
            ->assertSee('Menambah generus baru');
    }

    public function test_member_account_sees_teacher_topics_but_not_admin_topics(): void
    {
        $this->seed();
        Generus::create(['registration_number' => '26090001', 'full_name' => 'Murid', 'status' => 'active']);
        $member = User::where('username', '26090001')->firstOrFail();

        $this->actingAs($member)
            ->get('/tutorial')
            ->assertOk()
            ->assertSee('Generus')
            ->assertDontSee('Pengguna dan Peran')
            ->assertDontSee('Menambah generus baru');
    }
}
