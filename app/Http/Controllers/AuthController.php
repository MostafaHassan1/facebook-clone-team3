<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validate_signup;
use App\Http\Requests\Validate_Login;
use App\Http\Requests\Validate_change_password;
use App\Http\Requests\Validate_reset;
use App\Http\Requests\Validate_edit_profile;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'auth:api',
            ['except' => ['login', 'signin', 'verifyUser', 'resetPassword', 'update_password']]
        );
    }

    public function signin(Validate_signup $request)
    {
        $new_code = Str::random(50);
        User::create(array_merge(request()->all(), ['vcode' => $new_code]));
        /******************Mail-Block******************/
        $email = $request->email;
        $name = $request['firstname'];
        $subject  = 'Verify Mail To Login';
        Mail::send( 'email.verify',['name' => $request->firstname, 'verification_code' => $new_code],
            function ($mail) use ($email, $name, $subject)
                {
                $mail->from('test@gmail.com');
                $mail->to($email, $name);
                $mail->subject($subject);
                });
        /******************Mail-Block******************/
        return response()->json(['success' => "Check your email inbox for verification link"], 201);
    }

    public function login(Validate_Login $request)
    {
        $credentials = $request->only('email', 'password');
        if ($token = $this->guard()->attempt($credentials))
         {
            if (is_null(auth()->user()->email_verified_at))
            {
                return response()->json(['error' => "Please check your email inbox for verfication email"], 405);
            }
            return $this->respondWithToken($token);
            //return response()->json(['success' => "Hi," . auth()->user()->firstname], 202);
        }
        else
        {
            return response()->json(['error' => "Wrong credintials, Please try to login with a valid e-mail and password"], 401);
        }
    }

    public function verifyUser($verification_code)
    {
        $check=User::where('vcode', $verification_code)->first();
        if (!is_null($check))
        {
            if ($check->email_verified_at != null) // User Has Verified his E-mail
             {
                return response()->json(['error' => "User Has Verified Before"], 405);
            }
            else    // User Has Not Verified his E-mail
            {
                User::where('id', $check->id)->update(['email_verified_at' => now()]);
                //redirect()->route('login');
                return view('verifyUser_view');
            }
        }
        else
        {
            return response()->json(['error' => "Verification code is invalid."], 405);
        }
    }

    public function me()
    {
        return response()->json($this->guard()->user());
    }

    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['success' => 'Successfully logged out'], 202);
    }

    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /*
        User can Edit profile details
        So that: I can update my profile info  Name Phone Birthday
    */
    public function edit_profile(Validate_edit_profile $request)
    {
            $user = User::find(Auth::user()->id);
            $user->firstname = request('firstname');
            $user->lastname = request('lastname');
            $user->phone = request('phone');
            $user->birthdate = request('birthdate');
            $user->save();
            return response()->json(['success' => 'Profile Successfully Updated :) '],202);
    }

    /*
        I can reset my password if forgetten by providing my email

        When: I provide a not valide email format
        then:  Show error msg that says "This is not a valid email"

        When: navigate to my email and try to set my new password
        then: the password must be more than 8 character ,
        else show an error msg "Password can't be less than 8 characters"
    */
    public function reset_password(Validate_reset $request)
    {
            $user = DB::table('users')->where('email', $request->email)->first();
            /******************Mail-Block******************/
            $email = $request->email;
            $name = $user->firstname;
            $subject  = 'Resetting Password';
            Mail::send('email.reset_pass_view',['name' => $user->firstname, 'vcode'=>$user->vcode],
                function ($mail) use ($email, $name, $subject)
                {
                    $mail->from('backend@team3.com');
                    $mail->to($email, $name);
                    $mail->subject($subject);
                });
            /******************Mail-Block******************/
            return response()->json(['success' => "Check your email inbox for Restting Email"], 202);
    }
    public function update_password($vcode, $password)
    {
        if (strlen($password) < 8)
        {
            return response()->json(['error' => "Your Password Length less than 8 charachters"], 400);
        } else {
            $user_1 = DB::table('users')->where('vcode', $vcode)->first();
            $user_2 = User::find($user_1->id);
            $user_2->password = $password;
            $user_2->save();
            return response()->json(['success' => "Your Password Successfully Reset"], 200);
        }
    }

    /* User can change password only when login and get token
       Other Notes:: user can change to same password
    */
    public function change_password(Validate_change_password $request)
    {
            if ( Hash::check( request('password'), Auth::User()->password ) )
            {
                $user = User::find(Auth::User()->id);
                $user->password = request('new_pass');
                $user->save();
                return response()->json(["success"=>"Password Changed Successfully"], 200);
             }
             else
            {
                return response()->json(["error"=>"old password is not correct"], 400);
            }
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
