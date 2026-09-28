<?php

namespace Tests\Feature;

use App\Models\Generus;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_generus_can_log_in_with_registration_number_as_username_and_password(): void
    {
        $generus = Generus::create(['registration_number' => '26090001', 'full_name' => 'Ayu', 'status' => 'active']);

        $this->post('/login', ['login' => '26090001', 'password' => '26090001'])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs(User::where('generus_id', $generus->id)->firstOrFail());
    }

    public function test_new_teacher_can_log_in_and_inactive_teacher_cannot(): void
    {
        $teacher = Teacher::create(['registration_number' => '260999001', 'name' => 'Ustadz', 'status' => 'active']);

        $this->post('/login', ['login' => '260999001', 'password' => '260999001'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        auth()->logout();
        $teacher->update(['status' => 'inactive']);

        $this->assertSame('inactive', User::where('teacher_id', $teacher->id)->value('status'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        Generus::create(['registration_number' => '26090002', 'full_name' => 'Budi', 'status' => 'active']);

        $this->post('/login', ['login' => '26090002', 'password' => 'salah'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_deleted_generus_account_is_disabled(): void
    {
        $generus = Generus::create(['registration_number' => '26090003', 'full_name' => 'Citra', 'status' => 'active']);

        $generus->delete();

        $this->assertSame('inactive', User::where('generus_id', $generus->id)->value('status'));
    }

    public function test_member_can_change_password(): void
    {
        Generus::create(['registration_number' => '26090004', 'full_name' => 'Dewi', 'status' => 'active']);
        $user = User::where('username', '26090004')->firstOrFail();

        $this->actingAs($user)
            ->put('/password', ['current_password' => '26090004', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertRedirect('/password')
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('passwordbaru1', $user->fresh()->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        Generus::create(['registration_number' => '26090005', 'full_name' => 'Eka', 'status' => 'active']);
        $user = User::where('username', '26090005')->firstOrFail();

        $this->actingAs($user)
            ->put('/password', ['current_password' => 'salah', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertSessionHasErrors('current_password');
    }
}
