<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\ResendVerificationEmailRequest;
use App\Http\Requests\Auth\SignupRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function signup(SignupRequest $request)
    {
        try {
            $this->userService->createUser($request->validated());
            return response()->success([], 'You have successfully registered!', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->error(
                'Registration failed! Please try again.',
                $th->getMessage(),
                $th->getLine(),
                Response::HTTP_CONFLICT
            );
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $data = $request->validated();
            if (Auth::attempt($data)) {
                return $this->userService->createAuthTokens($request->user(), $data);
            } else {
                return response()->error(
                    'Invalid credentials',
                    '',
                    '',
                    Response::HTTP_UNAUTHORIZED
                );
            }
        } catch (\Throwable $th) {
            return response()->error(
                '',
                $th->getMessage(),
                $th->getLine(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    public function me()
    {
        try {
            return new UserResource(auth()->user());
        } catch (\Throwable $th) {
            return response()->error(
                '',
                $th->getMessage(),
                $th->getLine(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $this->userService->updateUser(auth()->user(), $request->validated());
            return response()->success([], 'Profile updated successfully');
        } catch (\Throwable $th) {
            return response()->error(
                'Profile updation failed',
                $th->getMessage(),
                $th->getLine(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    public function refreshToken(RefreshTokenRequest $request)
    {
        try {
            return $this->userService->refreshToken($request->validated());
        } catch (\Throwable $th) {
            return response()->error(
                '',
                $th->getMessage(),
                $th->getLine(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    public function logout()
    {
        try {
            $this->userService->destroyAccessToken(auth()->user());
            return response()->success([], 'Successfully logged out');
        } catch (\Throwable $th) {
            return response()->error(
                '',
                $th->getMessage(),
                $th->getLine(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    public function resendVerificationEmail(ResendVerificationEmailRequest $request)
    {
        try {
            $user = User::whereEmail($request->email)->firstOrFail();
            if ($user->hasVerifiedEmail()) {
                return response()->success([], 'Email already verified');
            }
            $user->sendEmailVerificationNotification();
            return response()->success([], 'Verification link sent');
        } catch (\Throwable $th) {
            return response()->error(
                'Failed to send verification email',
                $th->getMessage(),
                $th->getLine(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    public function verifyEmail(Request $request)
    {
        $user = User::find($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return redirect('/')->with('already_verified', true);
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            Log::error(new AuthorizationException());
            return redirect('/')->with('verified', false);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect('/')->with('verified', true);
    }
}
