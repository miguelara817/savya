<?php

use App\Models\User;
use App\Notifications\VerifyLoginEmail;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('requires an emailed code on every login including users who have not enabled MFA', function (): void {
    Notification::fake();
    $user = User::factory()->create(['has_email_authentication' => false]);

    foreach (range(1, 2) as $attempt) {
        $login = Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertNoRedirect();

        $this->assertGuest();
        Notification::assertSentTo($user, VerifyLoginEmail::class);
        $notification = Notification::sent($user, VerifyLoginEmail::class)->last();

        $login->set('data.multiFactor.email_code.code', $notification->code)
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(Filament::getUrl());

        $this->assertAuthenticatedAs($user);
        Filament::auth()->logout();
    }

    Notification::assertSentToTimes($user, VerifyLoginEmail::class, 2);
});

it('rejects incorrect and expired email codes', function (bool $expired): void {
    Notification::fake();
    $this->freezeTime();
    $user = User::factory()->create();
    $login = Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate');
    $notification = Notification::sent($user, VerifyLoginEmail::class)->sole();
    $code = $notification->code === '000000' ? '111111' : '000000';

    if ($expired) {
        $this->travel(5)->minutes();
        $code = $notification->code;
    }

    $login->set('data.multiFactor.email_code.code', $code)
        ->call('authenticate')
        ->assertHasFormErrors(['multiFactor.email_code.code'])
        ->assertNoRedirect();

    $this->assertGuest();
})->with(['incorrect' => false, 'expired' => true]);

it('does not send a code for invalid credentials', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'incorrect-password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
    Notification::assertNothingSent();
});

it('delivers the login email immediately even when the default queue uses the database', function (): void {
    config(['queue.default' => 'database', 'mail.default' => 'array']);
    $user = User::factory()->create();
    $mailer = app('mailer');

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $messages = $mailer->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    expect($messages->sole()->getOriginalMessage()->getTo()[0]->getAddress())->toBe($user->email);
    $this->assertGuest();
});

it('keeps partial codes local and accepts the complete code only after confirmation', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    $login = Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate');
    $notification = Notification::sent($user, VerifyLoginEmail::class)->sole();

    $login->assertSeeHtml('shouldSubmitOnCompletion: false');

    foreach (range(1, 6) as $length) {
        $login->set('data.multiFactor.email_code.code', substr($notification->code, 0, $length))
            ->assertHasNoFormErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    $login->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect(Filament::getUrl());

    $this->assertAuthenticatedAs($user);
    Notification::assertSentToTimes($user, VerifyLoginEmail::class, 1);
});
