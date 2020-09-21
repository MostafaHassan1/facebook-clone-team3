<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\User;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','create']]);
    }

    public function create(Request $req)
    {
        $validatedData = $req->validate(
            [
            'firstname' => 'required|max:255',
            'lastname' => 'required',
            'email' => 'required|email:rfc,dns|unique:users',
            'password' => 'required|min:8',
            'gender' => 'required',
            'birthdate' => 'required|date',
             ]);


        /*
            $req->gender == 1 :: this is man
            $req->gender == 2 :: this is woman
            $req->gender == !1 || !2 :: wrong data
        */
        $flag = 1 ;
        if($req->gender == 1 || $req->gender == 2 )    {  $flag = 1 ;  }

        else {$flag = 0 ; echo "please enter Correct Gender" ; }

        if ($flag == 1)
         {
            User::create( $req->all() ) ;
            return "Check your email inbox for verification link" ;
         }
    }


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        // echo (bcrypt('password'));
        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }


    public function me()
    {
        return response()->json($this->guard()->user());
    }


    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }


    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }


    public function guard()
    {
        return Auth::guard();
    }

}
