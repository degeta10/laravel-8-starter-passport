<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_user_can_login_using_api()
    {
        $this->withoutExceptionHandling();
        $this->artisan('migrate:fresh');
        $this->artisan('passport:install');
        $user = \App\Models\User::factory()->create();
        $this->postJson(
            '/api/auth/login',
            [
                'email'     => $user->email,
                'password'  => 'password'
            ],
            ['Accept' => 'application/json']
        )->assertStatus(200)->assertJsonStructure([
            "data" => [
                "message",
                "user" => [
                    'name',
                    'email',
                ],
                "token_type",
                "access_token",
                "refresh_token",
                "expires_at",
            ]
        ]);
        $this->artisan('migrate:reset');
    }

    public function test_user_can_signup_using_api()
    {
        $this->withoutExceptionHandling();
        $this->artisan('migrate:fresh');
        $this->artisan('passport:install');
        $this->postJson(
            '/api/auth/signup',
            [
                'name'                  => 'Test customer',
                'email'                 => 'test@test.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
            ],
            ['Accept' => 'application/json']
        )->assertStatus(200);
        $this->artisan('migrate:reset');
    }

    public function test_user_cannot_login_with_wrong_password_using_api()
    {
        $this->withoutExceptionHandling();
        $this->artisan('migrate:fresh');
        $this->artisan('passport:install');
        $user = \App\Models\User::factory()->create();
        $this->postJson(
            '/api/auth/login',
            [
                'email'     => $user->email,
                'password'  => 'password1'
            ],
            ['Accept' => 'application/json']
        )->assertStatus(401);
        $this->artisan('migrate:reset');
    }

    public function test_user_cannot_login_with_wrong_email_using_api()
    {
        $this->withoutExceptionHandling();
        $this->artisan('migrate:fresh');
        $this->artisan('passport:install');
        $user = \App\Models\User::factory()->create();
        $this->postJson(
            '/api/auth/login',
            [
                'email'     => $user->email . "test",
                'password'  => 'password1'
            ],
            ['Accept' => 'application/json']
        )->assertStatus(422);
        $this->artisan('migrate:reset');
    }
}
