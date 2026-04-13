<?php declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('lists categories in the backoffice', function () {
    Category::factory()->count(3)->create();

    livewire(CategoryResource\Pages\ListCategories::class)
        ->assertCanSeeTableRecords(Category::all());
});

it('admin can create a category', function () {
    livewire(CategoryResource\Pages\CreateCategory::class)
        ->set('data.name', 'Astronomie')
        ->set('data.slug', 'astronomie')
        ->set('data.color', '#000000')
        ->set('data.is_active', true)
        ->call('create')
        ->assertHasNoErrors();

    expect(Category::where('slug', 'astronomie')->exists())->toBeTrue();
});

it('blocks non-admin from accessing backoffice', function () {
    $player = User::factory()->create(['role' => UserRole::Player]);
    $this->actingAs($player);

    $this->get('/admin')->assertForbidden();
});
