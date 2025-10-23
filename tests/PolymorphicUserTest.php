<?php

namespace Tests;

use Visualbuilder\Versionable\Version;

class PolymorphicUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Post::enableVersioning();

        config([
            'auth.providers.users.model' => User::class,
        ]);
    }

    public function test_version_stores_polymorphic_user_for_regular_user()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->actingAs($user);

        $post = Post::create(['title' => 'Test Post', 'content' => 'Test Content']);

        $this->assertCount(1, $post->versions);
        $version = $post->lastVersion;

        $this->assertDatabaseHas('versions', [
            'id' => $version->id,
            'user_id' => $user->id,
            'user_type' => User::class,
        ]);
    }

    public function test_version_stores_polymorphic_user_for_admin()
    {
        $admin = Admin::create(['name' => 'Admin User', 'email' => 'admin@example.com']);
        $this->actingAs($admin);

        $post = Post::create(['title' => 'Admin Post', 'content' => 'Admin Content']);

        $this->assertCount(1, $post->versions);
        $version = $post->lastVersion;

        $this->assertDatabaseHas('versions', [
            'id' => $version->id,
            'user_id' => $admin->id,
            'user_type' => Admin::class,
        ]);
    }

    public function test_version_stores_polymorphic_user_for_organisation_user()
    {
        $orgUser = OrganisationUser::create([
            'name' => 'Org User',
            'email' => 'org@example.com',
            'organisation_id' => 123,
        ]);
        $this->actingAs($orgUser);

        $post = Post::create(['title' => 'Org Post', 'content' => 'Org Content']);

        $this->assertCount(1, $post->versions);
        $version = $post->lastVersion;

        $this->assertDatabaseHas('versions', [
            'id' => $version->id,
            'user_id' => $orgUser->id,
            'user_type' => OrganisationUser::class,
        ]);
    }

    public function test_version_user_relationship_returns_correct_user_model()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->actingAs($user);

        $post = Post::create(['title' => 'Test Post', 'content' => 'Test Content']);
        $version = $post->lastVersion;

        $this->assertInstanceOf(User::class, $version->user);
        $this->assertEquals($user->id, $version->user->id);
        $this->assertEquals($user->name, $version->user->name);
    }

    public function test_version_user_relationship_returns_correct_admin_model()
    {
        $admin = Admin::create(['name' => 'Admin User', 'email' => 'admin@example.com']);
        $this->actingAs($admin);

        $post = Post::create(['title' => 'Admin Post', 'content' => 'Admin Content']);
        $version = $post->lastVersion;

        $this->assertInstanceOf(Admin::class, $version->user);
        $this->assertEquals($admin->id, $version->user->id);
        $this->assertEquals($admin->name, $version->user->name);
    }

    public function test_version_user_relationship_returns_correct_organisation_user_model()
    {
        $orgUser = OrganisationUser::create([
            'name' => 'Org User',
            'email' => 'org@example.com',
            'organisation_id' => 123,
        ]);
        $this->actingAs($orgUser);

        $post = Post::create(['title' => 'Org Post', 'content' => 'Org Content']);
        $version = $post->lastVersion;

        $this->assertInstanceOf(OrganisationUser::class, $version->user);
        $this->assertEquals($orgUser->id, $version->user->id);
        $this->assertEquals($orgUser->name, $version->user->name);
        $this->assertEquals(123, $version->user->organisation_id);
    }

    public function test_multiple_versions_with_different_user_types()
    {
        // Version 1 created by User
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->actingAs($user);
        $post = Post::create(['title' => 'v1', 'content' => 'content v1']);

        // Version 2 created by Admin
        $admin = Admin::create(['name' => 'Admin User', 'email' => 'admin@example.com']);
        $this->actingAs($admin);
        $post->update(['title' => 'v2']);

        // Version 3 created by OrganisationUser
        $orgUser = OrganisationUser::create([
            'name' => 'Org User',
            'email' => 'org@example.com',
            'organisation_id' => 456,
        ]);
        $this->actingAs($orgUser);
        $post->update(['title' => 'v3']);

        $post->refresh();
        $this->assertCount(3, $post->versions);

        $versions = $post->versions()->orderBy('id', 'asc')->get();

        // Check version 1
        $this->assertInstanceOf(User::class, $versions[0]->user);
        $this->assertEquals($user->id, $versions[0]->user->id);

        // Check version 2
        $this->assertInstanceOf(Admin::class, $versions[1]->user);
        $this->assertEquals($admin->id, $versions[1]->user->id);

        // Check version 3
        $this->assertInstanceOf(OrganisationUser::class, $versions[2]->user);
        $this->assertEquals($orgUser->id, $versions[2]->user->id);
    }

    public function test_version_user_relationship_works_with_soft_deleted_user()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->actingAs($user);

        $post = Post::create(['title' => 'Test Post', 'content' => 'Test Content']);
        $version = $post->lastVersion;

        // Soft delete the user
        $user->delete();

        // Refresh the version
        $version = Version::find($version->id);

        // Should still be able to access the soft-deleted user
        $this->assertNotNull($version->user);
        $this->assertInstanceOf(User::class, $version->user);
        $this->assertEquals($user->id, $version->user->id);
        $this->assertTrue($version->user->trashed());
    }

    public function test_version_user_relationship_works_with_soft_deleted_admin()
    {
        $admin = Admin::create(['name' => 'Admin User', 'email' => 'admin@example.com']);
        $this->actingAs($admin);

        $post = Post::create(['title' => 'Admin Post', 'content' => 'Admin Content']);
        $version = $post->lastVersion;

        // Soft delete the admin
        $admin->delete();

        // Refresh the version
        $version = Version::find($version->id);

        // Should still be able to access the soft-deleted admin
        $this->assertNotNull($version->user);
        $this->assertInstanceOf(Admin::class, $version->user);
        $this->assertEquals($admin->id, $version->user->id);
        $this->assertTrue($version->user->trashed());
    }

    public function test_version_stores_null_when_no_user_authenticated()
    {
        // No authenticated user
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test Content']);

        $this->assertCount(1, $post->versions);
        $version = $post->lastVersion;

        $this->assertDatabaseHas('versions', [
            'id' => $version->id,
            'user_id' => null,
            'user_type' => null,
        ]);

        $this->assertNull($version->user);
    }

    public function test_version_user_relationship_returns_null_for_null_user()
    {
        // No authenticated user
        $post = Post::create(['title' => 'Test Post', 'content' => 'Test Content']);
        $version = $post->lastVersion;

        $this->assertNull($version->user);
    }
}
