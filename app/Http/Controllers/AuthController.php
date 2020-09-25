<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\ValidationController;
use App\Mail\MailtrapExample;
use App\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str ;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'signin']]);
    }

    public function signin(ValidationController $request)
    {
        $validated = $request->validated();
        $new_code = Str::random(50);
        if($validated == true )
        {
            // User::create( $request->all() , ['vcode' => $new_code]);
            User::create([
                'firstname' => $request['firstname'],
                'lastname' => $request['lastname'],
                'birthdate' => $request['birthdate'],
                'gender' => $request['gender'],
                'email' => $request['email'],
                'password' => $request['password'],
                'vcode'=>  $new_code
            ]);
            Mail::to($request->email)->send(new MailtrapExample() );
            return "Check your email inbox for verification link";
        }}

    public function login(Request $request)
    {
        $validatedData = $request->validate(
            [
                'email' => 'required|email:rfc,dns|unique:users',
                'password' => 'required|min:8|max:50',  // password accept Spaces
            ]
        );

        $credentials = $request->only('email','password');
        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        echo ($request->email . " not Exist plz enter Correct E-mail");
        echo "\n";

        Mail::to($request->email)->send(new MailtrapExample());
        echo "Hello " . $request->email . " please see your inbox \n ";
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
