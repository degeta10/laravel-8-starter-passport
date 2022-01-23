<?php

namespace App\Services;

use App\Http\Resources\Auth\LoginResource;
use App\Http\Resources\Auth\RefreshTokenResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Laravel\Passport\Client as PassportClient;

class UserService
{
    public function createUser($data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);
            event(new Registered($user));
            return $user;
        });
    }

    public function createAuthTokens(User $user, $data)
    {
        if ($user->hasVerifiedEmail()) {
            $oClient = PassportClient::where('password_client', 1)->whereRevoked('0')->firstOrFail();
            return $this->getTokenAndRefreshToken($oClient, $data['email'], $data['password']);
        } else {
            return response()->error(
                'Please verify email to proceed',
                '',
                '',
                Response::HTTP_UNAUTHORIZED
            );
        }
    }

    public function getTokenAndRefreshToken(PassportClient $oClient, $email, $password)
    {
        $response = Http::asForm()->post(config('app.url') . '/oauth/token', [
            'grant_type'    => 'password',
            'client_id'     => $oClient->id,
            'client_secret' => $oClient->secret,
            'username'      => $email,
            'password'      => $password,
            'scope'         => '*',
        ]);
        return $response->successful()
            ? response()->success(new LoginResource($response->json()))
            : response()->error(
                $response->json()['message'],
                '',
                '',
                Response::HTTP_UNAUTHORIZED
            );
    }

    public function refreshToken($data)
    {
        $oClient = PassportClient::where('password_client', 1)->first();
        $response = Http::asForm()->post(config('app.url') . '/oauth/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $data['token'],
            'client_id'     => $oClient->id,
            'client_secret' => $oClient->secret,
            'scope' => '',
        ]);

        return $response->successful()
            ? response()->success(new RefreshTokenResource($response->json()))
            : response()->error(
                $response->json()['message'],
                '',
                '',
                Response::HTTP_UNAUTHORIZED
            );
    }


    public function destroyAccessToken(User $user)
    {
        return DB::transaction(function () use ($user) {
            $tokenId = $user->token()->id;

            // # TO DELETE TOKENS
            // // Token::where('id', $tokenId)->delete();
            // // RefreshToken::where('access_token_id', $tokenId)->delete();

            # TO REVOKE TOKENS
            Token::where('id', $tokenId)->update(['revoked' => 1]);
            return RefreshToken::where('access_token_id', $tokenId)->update(['revoked' => 1]);
        });
    }

    public function updateUser(User $user, $data)
    {
        return DB::transaction(function () use ($data, $user) {
            return $user->update($data);
        });
    }
}
