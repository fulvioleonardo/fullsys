<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Models\Tenant\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];

        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), 'code' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if(!auth()->attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Credenciales invalidas', 'code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }

        $user = auth()->user();
        $userResponse = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'establishment_id' => $user->establishment_id,
            'api_token' => $user->api_token,
        ];

        return response()->json(['data' => $userResponse, 'code' => Response::HTTP_OK], Response::HTTP_OK);
    }

    public function loginToken($token)
    {
        $user = User::where('api_token', $token)->first();
        if($user) {
            Auth::loginUsingId($user->id);
            if (Auth::guard('web')->check()) {
                return redirect('/dashboard');
            } else {
                return response()->json(['error' => 'Credenciales invalidas', 'code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
            }
        } else {
            return response()->json(['error' => 'Credenciales invalidas', 'code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }
    }
}
