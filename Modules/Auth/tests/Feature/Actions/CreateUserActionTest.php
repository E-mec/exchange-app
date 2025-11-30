<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\actions\CreateUserAction;
use Modules\Auth\app\Notifications\OtpNotification;
use Modules\Auth\dtos\RequestDto\RegisterUserData;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\TestCase;

uses(TestCase::class);


test('can create user', function () {

    Notification::fake();

    $dto = RegisterUserData::fromArray([
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john@example.com',
        'username' => 'johnny',
        'password' => 'password123',
        'phone_number' => '08012345678',
        'dial_code' => '+234',
        'country' => 'Nigeria',
        'pin' => '1234',
    ]);

    $action = new CreateUserAction();
    $user = $action->handle($dto);

    expect($user)->toBeInstanceOf(User::class);
    expect(Hash::check('password123', $user->password))->toBeTrue();

    Notification::assertSentTo($user, OtpNotification::class);


});
