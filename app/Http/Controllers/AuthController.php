<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register()
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages());
        }

        $user = User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make(request('password')),
            'refresh_token' => null,
        ]);

        if ($user) {
            return response()->json(['message' => 'Pendaftaran berhasil!'], 201);
        } else {
            return response()->json(['message' => 'Pendaftaran gagal!'], 400);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Simpan refresh token ke dalam database
        $user = auth()->user();
        $refreshToken = $this->generateRefreshToken();
        $user->refresh_token = $refreshToken;
        $user->save();

        // return $this->respondWithToken($token, $refreshToken);

        return redirect(request('redirect').'/callback/'.$token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $user = auth()->user();
        $user->refresh_token = null; 
        $user->save();

        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $refreshToken = request('refresh_token');
        
        // Validate refresh token
        if (empty($refreshToken) || !is_string($refreshToken)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Find user by refresh token
        $user = User::where('refresh_token', $refreshToken)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }

        // Refresh the access token
        $accessToken = auth()->refresh();

        // Generate and save new refresh token
        $newRefreshToken = $this->generateRefreshToken();
        $user->refresh_token = $newRefreshToken;
        $user->save();

        return $this->respondWithToken($accessToken, $newRefreshToken);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     * @param  string $refreshToken
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $refreshToken)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'refresh_token' => $refreshToken, 
        ]);
    }

    /**
     * Generate a new refresh token.
     *
     * @return string
     */
    protected function generateRefreshToken()
    {
        return Str::random(60);
    }
}
