<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSign_UpRequest;
use App\Http\Requests\Validate_Login;
use App\Http\Requests\CreateValidate_ChangePassRequest;
use App\Http\Requests\Validate_reset;
use App\Http\Requests\CreateEdit_profileRequest;
use App\Mail\verifyEmail;
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
            ['except' => ['login', 'signin', 'verifyUser', 'sendresetpasswordemail', 'confirm_pin','resetpassword']]
        );
    }

    public function signin(CreateSign_UpRequest $request)
    {
        $vcode = Str::random(70);
        //dd($vcode);
        $validate = $request->validate(
            [
                'firstname' => 'string',
                'lastname' => 'string',
                'email' => 'string',
                'password' => 'string',
                'gender' => 'boolean', //1=>female & 0=>male
                'birthdate' => 'date',
                'phone' => 'string', //new row

            ]
        );
        $user = User::create(
            [
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'password' => $request->password,
                'email' => $request->email,
                'gender' => $request->gender,
                'birthdate' => $request->birthdate,
                'vcode' => $vcode,
                'phone' => $request->phone,
            ]
        );

        Mail::to($user)->send(new verifyEmail($user->firstname, $vcode));

        return response()->json(['message' => 'Successfully sign up ,Look at your email inbox'], 201);
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
        else
        {
            return response()->json(['success' => 'Check your email inbox for pin '], 200);
        }
    }

    public function confirm_pin(Request $request)
    {
        $user = DB::table('password_resets')->where('email', $request->email)->where('token', $request->token)->first(); //get()
        if ($user) {
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'invalid pin'], 422);
        }
    }

    public function resetpassword(Request $request)
    {
        $email = DB::table('password_resets')->where('token', $request->token)->where('email', $request->email)->first();
        if ($email)
        {
            $user = User::where('email', $request->email)->first();
            $user->password =$request->password;
            $user->save();
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

    /////////////////////// Edit Profile///////////////////////
    public function edit_profile(CreateEdit_profileRequest $request) //Commnt: can user use same mobile with 2 account
    {
        //DB::table('users')->where('phone', request('phone'))->first();
        $user = auth()->User();
         //mosh mi7taga if because user already loged in
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->phone = $request->phone;
            $user->birthdate = $request->birthdate;
            $user->save();

            return response()->json(['success' => 'Your profile updated successfully'], 200);
       
    }
    ///////////////////////End Edit Profile///////////////////////
}
