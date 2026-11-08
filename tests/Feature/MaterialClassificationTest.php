<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\LearningMaterial;
use App\Models\MaterialCategory;
use App\Models\MaterialChapter;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MaterialClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_classify_a_material(): void
    {
        $material = $this->makeLearningMaterial();
        $user = $this->userWithPermission('manage-learning-materials');

        $this->actingAs($user)
            ->put("/material-classifications/{$material->id}", ['jenis' => 'hafalan'])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'jenis' => 'hafalan',
        ]);
    }

    public function test_classification_rejects_an_unknown_jenis_value(): void
    {
        $material = $this->makeLearningMaterial();
        $user = $this->userWithPermission('manage-learning-materials');

        $this->actingAs($user)
            ->put("/material-classifications/{$material->id}", ['jenis' => 'tidak-dikenal'])
            ->assertSessionHasErrors('jenis');

        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'jenis' => null,
        ]);
    }

    public function test_user_without_permission_cannot_classify_a_material(): void
    {
        $material = $this->makeLearningMaterial();

        $user = User::create([
            'name' => 'Pengguna Biasa',
            'email' => 'biasa@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->put("/material-classifications/{$material->id}", ['jenis' => 'hafalan'])
            ->assertForbidden();

        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'jenis' => null,
        ]);
    }

    private function makeLearningMaterial(): LearningMaterial
    {
        $classGrade = ClassGrade::create(['name' => 'Kelas 1', 'code' => 'k1', 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => AcademicYear::create([
            'name' => '2026/2027',
            'code' => '2026-2027',
            'start_year' => 2026,
            'end_year' => 2027,
            'is_active' => true,
        ])->id, 'name' => 'Ganjil', 'code' => 'ganjil', 'is_active' => true]);
        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Al-Qur\'an',
            'code' => 'alquran',
            'is_active' => true,
        ]);
        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab 1',
            'code' => 'bab-1',
            'is_active' => true,
        ]);

        return LearningMaterial::create([
            'material_chapter_id' => $chapter->id,
            'title' => 'Surat Al-Fatihah',
            'code' => 'al-fatihah',
            'is_active' => true,
        ]);
    }

    private function userWithPermission(string $permissionSlug): User
    {
        $role = Role::create(['name' => 'Peran '.$permissionSlug, 'slug' => $permissionSlug.'-'.uniqid(), 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => $permissionSlug],
            ['name' => $permissionSlug, 'module' => 'learning-materials', 'is_active' => true]
        );
        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Pengguna '.$permissionSlug,
            'email' => $permissionSlug.'-'.uniqid().'@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }
}
