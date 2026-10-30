<?php

use App\Filament\Resources\UserSessions\Pages\ListUserSessions;
use App\Models\User;
use App\Models\UserSession;
use Database\Seeders\RolesPermisosSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->seed(RolesPermisosSeeder::class);
});

test('administradores pueden consultar sesiones pero otros roles no', function (): void {
    $administrador = User::factory()->create()->assignRole('administrador');
    $sesion = UserSession::query()->create([
        'user_id' => $administrador->getKey(),
        'ip_address' => '192.0.2.10',
        'logged_in_at' => now(),
    ]);

    $this->actingAs($administrador);

    Livewire::test(ListUserSessions::class)
        ->assertCanSeeTableRecords([$sesion])
        ->assertTableColumnStateSet('ip_address', '192.0.2.10', $sesion);

    $this->actingAs(User::factory()->create()->assignRole('cliente'));

    Livewire::test(ListUserSessions::class)->assertForbidden();
});

test('desconocidos no pueden consultar registros de sesiones', function (): void {
    $this->actingAs(User::factory()->create()->assignRole('responsable_inventario'));

    Livewire::test(ListUserSessions::class)->assertForbidden();
});

test('Laravel toma la ip reenviada solamente cuando la solicitud viene del proxy confiable', function (): void {
    $proxy = Request::create('/', 'GET', server: [
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.45',
    ]);

    (new class extends TrustProxies
    {
        protected $proxies = ['10.0.0.1'];

        protected $headers = SymfonyRequest::HEADER_X_FORWARDED_FOR;
    })
        ->handle($proxy, fn (Request $request): Request => $request);

    expect($proxy->ip())->toBe('203.0.113.45');

    $untrustedProxy = Request::create('/', 'GET', server: [
        'REMOTE_ADDR' => '10.0.0.2',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.45',
    ]);

    (new class extends TrustProxies
    {
        protected $proxies = ['10.0.0.1'];

        protected $headers = SymfonyRequest::HEADER_X_FORWARDED_FOR;
    })
        ->handle($untrustedProxy, fn (Request $request): Request => $request);

    expect($untrustedProxy->ip())->toBe('10.0.0.2');
});
