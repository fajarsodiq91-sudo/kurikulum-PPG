<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\LearningMaterial;
use App\Models\Level;
use App\Models\MaterialCategory;
use App\Models\MaterialChapter;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CurriculumMenuCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_category_index_renders_edit_and_delete_actions(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/material-categories')
            ->assertOk()
            ->assertSee('data-edit-material-category', false);
    }

    public function test_material_chapter_index_renders_edit_and_delete_actions(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab 1',
            'code' => 'B1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/material-chapters')
            ->assertOk()
            ->assertSee('data-edit-material-chapter', false);
    }

    public function test_learning_material_index_renders_edit_and_delete_actions(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab 1',
            'code' => 'B1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        LearningMaterial::create([
            'material_chapter_id' => $chapter->id,
            'title' => 'Pengenalan Akhlak',
            'code' => 'MAT-1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/learning-materials')
            ->assertOk()
            ->assertSee('data-edit-learning-material', false);
    }

    public function test_admin_can_update_material_category(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put("/material-categories/{$category->id}", [
                'class_grade_id' => $classGrade->id,
                'semester_id' => $semester->id,
                'name' => 'Akhlak Terpuji',
                'code' => 'AKH-1',
                'is_active' => true,
            ])
            ->assertRedirect('/material-categories');

        $this->assertDatabaseHas('material_categories', [
            'id' => $category->id,
            'name' => 'Akhlak Terpuji',
            'code' => 'AKH-1',
        ]);
    }

    public function test_admin_can_delete_material_category_without_chapters(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->delete("/material-categories/{$category->id}")
            ->assertRedirect('/material-categories');

        $this->assertDatabaseMissing('material_categories', ['id' => $category->id]);
    }

    public function test_deleting_material_category_with_chapters_fails_gracefully(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab 1',
            'code' => 'B1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->delete("/material-categories/{$category->id}")
            ->assertRedirect('/material-categories')
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('material_categories', ['id' => $category->id]);
    }

    public function test_admin_can_update_material_chapter(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab 1',
            'code' => 'B1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put("/material-chapters/{$chapter->id}", [
                'material_category_id' => $category->id,
                'name' => 'Bab Satu',
                'code' => 'B1-REV',
                'is_active' => true,
            ])
            ->assertRedirect('/material-chapters');

        $this->assertDatabaseHas('material_chapters', [
            'id' => $chapter->id,
            'name' => 'Bab Satu',
            'code' => 'B1-REV',
        ]);
    }

    public function test_admin_can_delete_material_chapter_without_materials(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab 1',
            'code' => 'B1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->delete("/material-chapters/{$chapter->id}")
            ->assertRedirect('/material-chapters');

        $this->assertDatabaseMissing('material_chapters', ['id' => $chapter->id]);
    }

    public function test_admin_can_update_learning_material(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab 1',
            'code' => 'B1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $material = LearningMaterial::create([
            'material_chapter_id' => $chapter->id,
            'title' => 'Pengenalan Akhlak',
            'code' => 'MAT-1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put("/learning-materials/{$material->id}", [
                'material_chapter_id' => $chapter->id,
                'title' => 'Pengenalan Akhlak Terpuji',
                'code' => 'MAT-1-REV',
                'is_active' => true,
            ])
            ->assertRedirect('/learning-materials');

        $this->assertDatabaseHas('learning_materials', [
            'id' => $material->id,
            'title' => 'Pengenalan Akhlak Terpuji',
            'code' => 'MAT-1-REV',
        ]);
    }

    public function test_admin_can_delete_learning_material(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-materials');
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab 1',
            'code' => 'B1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $material = LearningMaterial::create([
            'material_chapter_id' => $chapter->id,
            'title' => 'Pengenalan Akhlak',
            'code' => 'MAT-1',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->delete("/learning-materials/{$material->id}")
            ->assertRedirect('/learning-materials');

        $this->assertDatabaseMissing('learning_materials', ['id' => $material->id]);
    }

    public function test_user_without_permission_cannot_update_material_category(): void
    {
        [$classGrade, $semester] = $this->createClassGradeAndSemester();

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKH',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Viewer',
            'email' => 'viewer@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->put("/material-categories/{$category->id}", [
                'class_grade_id' => $classGrade->id,
                'semester_id' => $semester->id,
                'name' => 'Akhlak Terpuji',
                'code' => 'AKH-1',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('material_categories', ['id' => $category->id, 'name' => 'Akhlak']);
    }

    private function createAdminWithPermission(string $permissionSlug): User
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $role->permissions()->attach(Permission::create([
            'name' => $permissionSlug,
            'slug' => $permissionSlug,
            'module' => $permissionSlug,
            'is_active' => true,
        ])->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    /**
     * @return array{0: ClassGrade, 1: Semester}
     */
    private function createClassGradeAndSemester(): array
    {
        $level = Level::create([
            'name' => 'Paripurna',
            'code' => 'PAR',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $classGrade = ClassGrade::create([
            'level_id' => $level->id,
            'name' => 'Kelas 1',
            'code' => 'K1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'code' => '2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'code' => 'Ganjil',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return [$classGrade, $semester];
    }
}
