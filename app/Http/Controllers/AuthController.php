<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validate_signup;
use App\Http\Requests\Validate_Login;
use App\Http\Requests\CreateValidate_ChangePassRequest;
use App\Http\Requests\Validate_reset;
use App\Http\Requests\Validate_edit_profile;
use App\User;
use Carbon\Carbon;
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
            ['except' => ['login', 'signin', 'verifyUser', 'reset_password', 'reset_password_2']]
        );
    }

    public function signin(Validate_signup $request)  //Comment Sara:Signup
    {
        $new_code = Str::random(50);
        User::create(array_merge(request()->all(), ['vcode' => $new_code]));
        /******************Mail-Block******************/
        $email = $request->email;
        $name = $request['firstname'];
        $subject  = 'Verify Mail To Login';
        Mail::send(
            'email.verify',
            ['name' => $request->firstname, 'verification_code' => $new_code],
            function ($mail) use ($email, $name, $subject) {
                $mail->from('test@gmail.com');
                $mail->to($email, $name);
                $mail->subject($subject);
            }
        );
        /******************Mail-Block******************/
        return response()->json(['success' => "Check your email inbox for verification link"], 201);
    }

    public function login(Validate_Login $request)  //Comment:SARA $$$$$$$$$$$$$$$$$$$$$$$$$
    {
        $credentials = $request->only('email', 'password');
        if ($token = $this->guard()->attempt($credentials)) {
            if (is_null(auth()->user()->email_verified_at)) {
                return response()->json(['error' => "Please check your email inbox for verfication email"], 405);
            }
            return $this->respondWithToken($token); ///////////xxxxxxxxxxxxxxxxxxxxxxxx/////////////////
            //return response()->json(['success' => "Hi," . auth()->user()->firstname], 202);
        } else {
            return response()->json(['error' => "Wrong credintials, Please try to login with a valid e-mail and password"], 401);
        }
    }

    public function verifyUser($verification_code)
    {
        $check = User::where('vcode', $verification_code)->first();
        if (!is_null($check)) {
            if ($check->email_verified_at != null) //User Has Verified his E-mail
            {
                return response()->json(['error' => "User Has Verified Before"], 405);
            } else    // User Has Not Verified his E-mail
            {
                User::where('id', $check->id)->update(['email_verified_at' => now()]);
                return view('verifyUser_view');
            }
        } else {
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
        I can update my profile info  Name Phone Birthday
        i think mobile send old data in field and user choice to edit or not
    */
    public function edit_profile(Validate_edit_profile $request) //Commnt: can user use same mobile with 2 account
    {   // mobile team send data from database
        $all = DB::table('users')->where('phone', request('phone'))->first();
        $user = User::find(Auth::user()->id);
        if ($all == true && ($user->phone == request('phone'))) // phone exist with other users
        {
            $user->phone = request('phone');
            $user->firstname = request('firstname');
            $user->lastname = request('lastname');
            $user->birthdate = request('birthdate');
            $user->save();
            return response()->json(['success' => 'Profile Successfully Updated :) '], 202);
        } else {
            return response()->json(['error' => 'this phone used with other users '], 405);
        }
    }

    /* User can change password only when login and get token
       Other Notes:: user can change to same password
    */
     /////////////////////// Change Password ///////////////////////

     public function changepassword(CreateValidate_ChangePassRequest $request)
     {
         $user = auth()->User();
         if (!$user) {
             return response()->json(["error" => "old password is not correct"], 406); // 406 is not acceptable
         } else if ($request->old_password == $request->password) {
             return response()->json(["error" => "Old password is same as new password"], 400); // 200 ok
         } else {
             $user->password = $request->new_password;
             $user->save();
             return response()->json(["success" => "Password Changed Successfully"], 200); // 200 ok
         }
     }
     /////////////////////// End Change Password ///////////////////////
    

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

        if ($user)
        {
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => mt_rand(10000000, 99999999), // 6 digit Str::random(10),
                'created_at' => Carbon::now()
            ]);

            $tokenData = DB::table('password_resets')->where('email', $request->email)->first();
            /******************Mail-Block******************/
            $email = $request->email;
            $name = $user->firstname;
            $subject  = 'Resetting Password';
            Mail::send(
                'email.reset_password',
                ['name' => $user->firstname, 'token' => $tokenData->token],
                function ($mail) use ($email, $name, $subject) {
                    $mail->from('backend@team3.com');
                    $mail->to($email, $name);
                    $mail->subject($subject);
                }
            );
            /******************Mail-Block******************/
            return response()->json(['success' => "Check your email inbox for Restting Email"], 202);
        }
    }

    public function reset_password_2($token)
    {
        $tokenData = DB::table('password_resets')->where('token', $token)->first();

        if (!$tokenData) {
            return response()->json(["error" => "token not valied \n "]);
        }

        $user = User::where('email', $tokenData->email)->first();

        if (!$user) {
            return response()->json(["error" => "sry user does not use this token ...  "], 405);
        }

        // code review  update user password with [token or send view get password]  %%%%^%^%$^#@^#$@^%$#@!&^@#$^#@
        $user->password = $token;

        $user->save(); // $user->update();

        Auth::login($user);

        DB::table('password_resets')->where('email', $user->email)->delete();

        return response()->json(["success" => "Welcome " . $user->firstname . " ... "], 200);
    }

    
}
