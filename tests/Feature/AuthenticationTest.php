<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_user_can_login_using_api()
    {
        $user = \App\Models\User::factory()->create();
        $this->postJson(
            '/api/auth/login',
            [
                'email'     => $user->email,
                'password'  => 'password'
            ],
            ['Accept' => 'application/json']
        )->assertStatus(200);
    }
}
