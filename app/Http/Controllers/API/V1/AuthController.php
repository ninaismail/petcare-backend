<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\PetOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Pet Owner registration
    public function register(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:pet_owners',
            'phone' => 'required|numeric|digits_between:8,15',
            'password' => 'required|string|min:8|confirmed',
        ]);
    
        // If validation fails, return the validation errors
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
    
        // Create the new pet owner
        $petOwner = PetOwner::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'password' => Hash::make($request->get('password')),
        ]);
    
        // Generate the JWT token for the newly registered pet owner
        $token = JWTAuth::fromUser($petOwner);
    
        // Return the response with the pet owner data and JWT token
        return response()->json([
            'pet_owner' => $petOwner,
            'access_token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        // Validate incoming request - Make sure email and password are provided
        $credentials = $request->only('email', 'password');

        // Check if email and password are not empty
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return response()->json(['error' => 'Email and password are required'], 400);
        }

        // Check if the pet owner exists
        $petOwner = PetOwner::where('email', $credentials['email'])->first();

        if (!$petOwner) {
            return response()->json(['error' => 'Invalid email or password'], 401);
        }

        // Check password validity
        if (!Hash::check($credentials['password'], $petOwner->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        try {
            // Generate JWT token for the pet owner
            $token = JWTAuth::fromUser($petOwner);

            // Return the access token and pet owner information
            return response()->json([
                'access_token' => $token,
                'pet_owner' => $petOwner,
            ]);
        } catch (JWTException $e) {
            // Return error response if token generation fails
            return response()->json(['error' => 'Could not create token, please try again later'], 500);
        }
    }

    public function getPetOwner(Request $request)
    {
        // The petOwner should already be authenticated by the JwtMiddleware
        $petOwner = auth()->user();  // Or JWTAuth::user();
        
        if (!$petOwner) {
            return response()->json(['error' => 'Pet owner not found'], 404);
        }
    
        return response()->json([
            'pet_owner' => $petOwner,
        ]);
    }
    

    // logout
    public function logout(Request $request)
    {
        try {
            // Invalidate the token
            JWTAuth::invalidate(JWTAuth::getToken());
    
            // Return a success message
            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            // Handle error if token invalidation fails
            return response()->json(['error' => 'Failed to logout, please try again'], 500);
        }
    }
    
}
