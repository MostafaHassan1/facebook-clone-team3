<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validate_signup;
use App\Http\Requests\Validate_Login;
use App\Http\Requests\Validate_change_password;
use App\Http\Requests\Validate_edit_profile;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function signin(Validate_signup $request)  //Comment Sara:Signup - Mail Clean Code
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
        return response()->json(['success' => "Check your email inbox for verification link"], 200);
    }

    public function login(Validate_Login $request)
    {
        $credentials = $request->only('email', 'password');
        if ($token = $this->guard()->attempt($credentials)) {
            if (is_null(auth()->user()->email_verified_at)) {
                return response()->json(['error' => "Please check your email inbox for verfication email"], 401);
            }
            return $this->respondWithToken($token);
        } else {
            return response()->json(['error' => "Wrong credintials, Please try to login with a valid e-mail or password"], 401);
        }
    }

    public function verifyUser($verification_code)
    {
        $check = User::where('vcode', $verification_code)->first();
        if (!is_null($check)) {
            if ($check->email_verified_at != null)
            {
                return response()->json(['error' => "User Has Verified Before"], 401);
            } else
            {
                User::where('id', $check->id)->update(['email_verified_at' => now()]);
                return view('verifyUser_view');
            }
        } else {
            return response()->json(['error' => "Verification code is invalid."], 401);
        }
    }

    public function me()
    {
        return response()->json($this->guard()->user());
    }

    public function logout()
    {
        $this->guard()->logout();
        return response()->json(['success' => 'Successfully logged out'], 200);
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
    public function edit_profile(Validate_edit_profile $request)
    {
        //$anyuser = DB::table('users')->where('phone',request('phone'))->first();
        $user = User::find(Auth::user()->id);   ///////////
        /*
            1.request data
            2.validate data from request
            3.validate from user own his phone  // ready validation in laravel [][][][][]
            4.accept and edit profile and save
        */
       // if ($anyuser == true && ($user->phone == request('phone') )) /////////////
        {
            $user->phone = request('phone');
            $user->firstname = request('firstname');
            $user->lastname = request('lastname');
            $user->birthdate = request('birthdate');
            $user->save();
            return response()->json(['success' => 'Profile Successfully Updated :) '], 200);
        }

        /*else
         {
            return response()->json(['error' => 'this phone used with other users'], 401);
        }
        */
    }

    /* User can change password only when login and get token
       Other Notes:: user can change to same password
    */
    public function change_password(Validate_change_password $request)
    {
        if (Hash::check(request('password'), Auth::User()->password))
         {
             // new if with new_pass != current pass ????
            $user = User::find(Auth::User()->id); //$$$$ready validation found in laravel documintation
            $user->password = request('new_pass');
            $user->save();
            return response()->json(["success" => "Password Changed Successfully"], 200);
         } else
         {
            return response()->json(["error" => "old password is not correct"], 400);
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

    /*
        I can reset my password if forgetten by providing my email

        When: I provide a not valide email format
        then:  Show error msg that says "This is not a valid email"

        When: navigate to my email and try to set my new password
        then: the password must be more than 8 character ,
        else show an error msg "Password can't be less than 8 characters"
    */

    public function sendresetpasswordemail(Request $request)
    {
        $user = DB::table('users')->where('email', $request->email)->first();
        if ($user) {
            $token = mt_rand(000000, 999999);
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]);
            $email = $request->email;
            $name = $user->firstname;
            $subject = 'Resetting Password';
            Mail::send(
                        'sendrestpassemail',
                        ['name' => $user->firstname, 'token' => $token],
                            function ($mail) use ($email,$name,$subject) {
                                $mail->from('team3@facebookclone.com');
                                $mail->to($email, $name);
                                $mail->subject($subject);
                            }
                        );

            return response()->json(['success' => 'Check your email inbox for pin '], 200);

        }
    }

    public function confirm_pin(Request $request)
    {
        //dd( $request->token);
        $user = DB::table('password_resets')->where('email', $request->email)->where('token', $request->token)->first(); //get()
        if ($user) {
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'invalid pin'], 422);
        }
    }

    //url = POST api/reset_password , new password , email , pin

    public function resetpassword(Request $request)
    {
        $email = DB::table('password_resets')->where('token', $request->token)->where('email', $request->email)->first();
        if ($email) {

            $user = User::where('email', $request->email)->first();

            $user->password =$request->password;

            $user->save();
            //dd($user);
            DB::table('password_resets')->where('email', $request->email)->delete();
            $credentials = $request->only(['email', 'password']);
            if ($token = auth()->attempt($credentials)) {
                return $this->respondWithToken($token);
            } else {
                return response()->json('login failed');
            }

        } else {
            return response()->json(["error" => "Pin is not valid"], 422);
        }
    }
}
