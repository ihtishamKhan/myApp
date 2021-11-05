<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Mail;
use Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email','max:255', 'unique:users'],
            'username' => ['required', 'string', 'min:4','max:20', 'unique:users'],
            'role' => ['required', 'string','max:255'],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }

        $otp = rand(100000, 999999);
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'otp' => $otp,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        $data = array('name'=>"My App", 'email' => $request->email, 'otp' => $otp, 'username' => $request->username);
        Mail::send('email.otp', ['data' => $data], function($message) use ($data) {
            $message->to($data['email'], $data['username'])->subject
                ('Account confirmation');
            $message->from('xyz@gmail.com', $data['name']);
        });

        return $user;
    }

    public function otp_confirm(Request $request)
    {
        $user = User::where([['email', $request->email], ['otp', $request->otp]])->first();
        if($user) {
            User::where('email', $request->email)->update(['otp' => null, 'registered_at' => \Carbon\Carbon::now()]);
            return $user;
        }
        return 'User not found';
    }

    public function user_login(Request $request)
    {
        if(Auth::attempt(['email'=>request('email'), 'password'=>request('password')])) {
            return Auth::user();
        }
        return "Credentials are not correct";
    }

    public function update_profile(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['string','max:255'],
            'avatar' => ['dimensions:max_width=256,max_height=256', 'image:jpeg,png,jpg,gif,svg'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }

        $user = User::findOrFail($id);
        if ($user) {
            $user->name = $request->name;

            //handle file upload
            if($request->hasFile('avatar')){
                //get filename with extension
                $filenameWithExt = $request->file('avatar')->getClientOriginalName();
                //get just filename
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                //get just ext
                $extension = $request->file('avatar')->getClientOriginalExtension();
                //file name to store
                $fileNameToStore = $filename.'_'.time().'.'.$extension;
                // upload image
                $path = $request->file('avatar')->storeAs('public/images', $fileNameToStore);

                // check user
                $user->avatar = $fileNameToStore;
            }

            $user->save();

            return response()->json([
                "success" => true,
                "message" => "User profile updated",
            ]);
        }

        return response()->json([
            "success" => false,
            "message" => "User not found",
        ]);

    }
}
