<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validate_signup;
use App\Http\Requests\Validate_Login;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str ;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','signin','verifyUser']]);
    }

    public function signin(Validate_signup $request)
    {
        $validated = $request->validated();
        $new_code = Str::random(50);
        if($validated == true )
        {
            User::create(array_merge($validated,['vcode'=>$new_code]) ) ;

            /******************Mail-Block******************/
            $email= $request->email;
            $name = $request['firstname'];
            $subject  = 'Verify Mail To Login';

            Mail::send('email.verify', ['name' => $request->firstname, 'verification_code' => $new_code],
            function($mail) use ($email, $name, $subject){
                $mail->from('test@gmail.com');
                $mail->to($email, $name);
                $mail->subject($subject);
            });
             /******************Mail-Block******************/
            return response()->json(['success'=>"Check your email inbox for verification link"],200);
        }}

    public function login(Validate_Login $request)
    {
        $validated = $request->validated();
        $credentials = $request->only('email','password');
        $user = DB::table('users')->where('email',$request->email)->first();
        $check = $user->email_verified_at ;

        if ($token = $this->guard()->attempt($credentials))
        {
            if(is_null($check))
           {
            return response()->json(['error'=>"Please check your email inbox for verfication email"],401);
           }
           return $this->respondWithToken($token);
        }
        else
        {
            return response()->json(['error'=>"Wrong credintials, Please try to login with a valid e-mail and password"],401);
        }
    }

    public function verifyUser($verification_code)
    {
       $check = DB::table('users')->where('vcode',$verification_code)->first();
        if(!is_null($check))
        {
            DB::table('users')->where('id', $check->id) ->update(['email_verified_at' => now() ]);
            return response()->json([ 'success'=> true, 'message'=> 'successfully verified email address.']);
        }
       return response()->json(['success'=> false, 'error'=> "Verification code is invalid."]);
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
