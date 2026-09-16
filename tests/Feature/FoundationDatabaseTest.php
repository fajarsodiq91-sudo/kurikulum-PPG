<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_foundation_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('regions'));
        $this->assertTrue(Schema::hasTable('villages'));
        $this->assertTrue(Schema::hasTable('groups'));
        $this->assertTrue(Schema::hasTable('levels'));
        $this->assertTrue(Schema::hasTable('academic_years'));
        $this->assertTrue(Schema::hasTable('semesters'));
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasTable('role_permissions'));
        $this->assertTrue(Schema::hasTable('user_roles'));
        $this->assertTrue(Schema::hasTable('settings'));
    }
}
