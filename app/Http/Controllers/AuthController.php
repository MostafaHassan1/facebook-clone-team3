<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validate_signup;
use App\Http\Requests\Validate_Login;
use App\Http\Requests\Validate_change_password;
use App\Http\Requests\Validate_reset;
use App\Http\Requests\Validate_update_box ;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'signin', 'verifyUser','resetPassword','update_password'] ]);
    }

    public function signin(Validate_signup $request)
    {
        $validated = $request->validated();
        $new_code = Str::random(50);
        if ($validated == true) {
            User::create(array_merge($validated, ['vcode' => $new_code]));

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
    }

    public function login(Validate_Login $request)
    {

        $validated = $request->validated();
        if ($validated == true) {
            $credentials = $request->only('email', 'password');

            if ($token = $this->guard()->attempt($credentials))
            {
                $user = auth()->user()->email_verified_at;
                if (is_null($user)) {
                    return response()->json(['error' => "Please check your email inbox for verfication email"], 401);
                }
                return $this->respondWithToken($token);
            }
             else {
                return response()->json(['error' => "Wrong credintials, Please try to login with a valid e-mail and password"], 401);
            }
        }
    }

    public function verifyUser($verification_code)
    {
        $check = DB::table('users')->where('vcode', $verification_code)->first();
        if (!is_null($check))
         {
            if($check->email_verified_at != null )
            {
                return response()->json(['error' => "anta 3mlt verify 2bl kdh "], 401);
            }

            else
            {
                DB::table('users')->where('id', $check->id)->update(['email_verified_at' => now()]);
            }

            //return response()->json(['success'=> true,'message'=>'successfully verified email address.'],200);
            return view('Complete');
         }
        return response()->json(['success' => false, 'error' => "Verification code is invalid."], 401);
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
    public function updateBox(Validate_update_box $request)
    {
        /* Validate  Name  Phone   Birthday

         I can: change some of my profile details
         So that: I can update my profile info
        */
        $validated = $request->validated();

        if($validated == true )
        {
            $user = User::find(Auth::user()->id);
            $other_users = DB::table('users')->where('phone',$validated['phone'])->first();

             if(!(is_null($other_users)) &&  ($other_users->id == $user->id) )    // have value
            {
                $user->firstname = $validated['firstname'] ;
                $user->lastname = $validated['lastname'] ;
                $user->phone =   $validated['phone'] ;
                $user->birthdate =   $validated['birthdate'] ;
                $user->save();
                return response()->json(['success'=>'Profile Successfully Updated :) '],200);

            }
            else
            {
                return response()->json(['Err'=>'the phone number currently Used with Other Users'],401);
            }
        }


    }
    public function resetPassword(Validate_reset $request)
    {
        $validated = $request->validated();

        if($validated == true )
        {
            $user = DB::table('users')->where('email',$request->email)->first();

             /******************Mail-Block******************/
             $email = $request->email;
             $name = $user->firstname;
             $subject  = 'Resetting Password';

             Mail::send
             (
                 'email.reset_pass_view',
                 ['name' => $user->firstname , 'vcode' => $user->vcode],
                 function ($mail) use ($email, $name, $subject)
                  {
                     $mail->from('backend@team3.com');
                     $mail->to($email, $name);
                     $mail->subject($subject);
                 }
             );
             /******************Mail-Block******************/
            return response()->json(['success' => "Check your email inbox for Restting Email"], 200);
        }
    }
    public function update_password($vcode,$password)
    {
        if(strlen($password) < 8)
        {
            return response()->json(['err' => "Your Password Length less than 8 charachters"],400);
        }
        else
        {
            $user_1 = DB::table('users')->where('vcode',$vcode)->first();
            $user_2 = User::find($user_1->id);
            $user_2->password = $password ;
            $user_2->save();
            return response()->json(['success' => "Your Password Successfully Reset"],200);
        }
    }

    public function changePasswrod(Validate_change_password $request)
    {
        $user = Auth::user();
        if (Auth::Check())
        {
            $validated = $request->validated();
            if($validated == true )
            {
                $currentPassword = Auth::User()->password;

                if (Hash::check($validated['password'], $currentPassword))
                {
                    $userId = Auth::User()->id;
                    $user = User::find($userId);

                    if($validated['new_pass'] == $validated['conifrm_new_pass'] )
                    {
                        $user->password = $validated['conifrm_new_pass'] ;
                        $user->save();

                    }

                    return response()->json(["Your password has been updated successfully"],200);
                    //return view("complete");
                    //back()->with('message', 'Your password has been updated successfully.');
                }
                else
                {
                    return response()->json(["Your password has NOT been updated successfully"],401);
                    //return back()->withErrors(['Sorry, your current password was not recognised. Please try again.']);
                }
            }
        } else
        {
            return response()->json(['Sorry,User Not Authorized.'],401);
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
