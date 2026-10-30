<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_their_own_profile_with_photo_and_whatsapp_number(): void
    {
        Storage::fake();

        $user = User::create([
            'name' => 'Pengurus Lama',
            'email' => 'pengurus@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->put('/profile', [
                'name' => 'Pengurus Baru',
                'whatsapp' => '081234567890',
                'photo' => UploadedFile::fake()->createWithContent('foto.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==')),
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Pengurus Baru', $user->name);
        $this->assertSame('081234567890', $user->whatsapp);
        Storage::assertExists($user->photo);

        $this->actingAs($user)
            ->get(route('users.photo', $user))
            ->assertOk();
    }

    public function test_whatsapp_number_is_normalised_to_a_wa_me_link_with_indonesia_country_code(): void
    {
        $this->assertSame('https://wa.me/6281234567890', User::whatsappUrl('081234567890'));
        $this->assertSame('https://wa.me/6281234567890', User::whatsappUrl('+62 812-3456-7890'));
        $this->assertNull(User::whatsappUrl(null));
        $this->assertNull(User::whatsappUrl(''));
    }
}
