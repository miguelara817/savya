<?php

use App\Filament\Resources\ServiceDataInfos\ServiceDataInfoResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesPermisosSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('allows a role with the view-any permission to access service data info', function (): void {
    $user = User::factory()->create();
    $user->assignRole('encargado_ventas');
    $user->givePermissionTo('ViewAny:ServiceDataInfo');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user);

    expect(ServiceDataInfoResource::canViewAny())->toBeTrue();
});

it('denies access when the role does not have the view-any permission', function (): void {
    $user = User::factory()->create();
    $user->assignRole('encargado_ventas');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user);

    expect(ServiceDataInfoResource::canViewAny())->toBeFalse();
});

it('registers the resource in panel navigation for an authorized administrator', function (): void {
    $user = User::factory()->create();
    $user->assignRole('administrador');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user);

    expect(ServiceDataInfoResource::canAccess())->toBeTrue()
        ->and(ServiceDataInfoResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ServiceDataInfoResource::getNavigationItems())->not->toBeEmpty();
});

it('renders the service data info navigation item in the admin panel', function (): void {
    $user = User::factory()->create();
    $user->assignRole('administrador');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user)
        ->get(ServiceDataInfoResource::getUrl())
        ->assertSuccessful()
        ->assertSee('servicios externos');
});

it('exposes the resource permissions in the Shield role editor', function (): void {
    $permissions = FilamentShield::getResourcePermissionsWithLabels(ServiceDataInfoResource::class);

    expect($permissions)
        ->toHaveKey('ViewAny:ServiceDataInfo')
        ->toHaveKey('View:ServiceDataInfo')
        ->toHaveKey('Create:ServiceDataInfo')
        ->toHaveKey('Update:ServiceDataInfo')
        ->toHaveKey('Delete:ServiceDataInfo');
});
