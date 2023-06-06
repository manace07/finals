<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use App\Models\products_table;
use Hash;
use Session;

class User_Controller extends Controller
{
    public function index()
    {
        return redirect('/');
    }

    public function registration()
    {
        return view('registration');
    }

    public function validate_registration(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'firstname' => 'required',
            'lastname' => 'required',
            'username' => 'required|unique:users',
            'email' => 'required|email|confirmed|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = new User;
        $user->firstname = $request->input('firstname');
        $user->lastname = $request->input('lastname');
        $user->username = $request->input('username');
        $user->email = $request->input('email');
        $user->password = bcrypt($request->input('password'));
        $user->save();

        // $data = $request->all();

        // User::create([
        //     'firstname' => $data['firstname'],
        //     'lastname' => $data['lastname'],
        //     'username' => $data['username'],
        //     'email' => $data['email'], 
        //     'password' => Hash::make($data['password'])
        // ]);

        return redirect('/')->with('success', 'Registration Successful!');
    }

    public function verifyEmail(Request $request)
    {
        $user = User::find($request->id);

        if ($user && hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
            $user->markEmailAsVerified();
            return redirect()->route('login')->with('success', 'Email verification successful. You can now login.');
    }

        return redirect()->route('login')->with('error', 'Invalid email verification link.');

    }


    public function validate_login(Request $request)
    {
        $credentials = $request->validate(['username' => 'required', 'password' => 'required']);

        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {

            return redirect('products');
        }

        // return redirect()->back()->withErrors([
        //     'login_failed' => 'Invalid credentials',
        // ])->withInput();

        // $request->validate(['email' => 'required', 'password' => 'required']);

        // $credentials = $request->only('email', 'password');

        // if(Auth::attempt($credentials))
        // {
        //     return redirect('products');
        // }

        return redirect('/')->with('success', 'Incorrect Email or Password!');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? redirect()->route('login')->with('success', 'Password reset link sent to your email.')
            : redirect()->back()->withErrors(['email' => __($status)]);
    }

    public function showResetPasswordForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset successful. You can now login with your new password.')
            : redirect()->back()->withErrors(['email' => __($status)]);
    }

    

    public function products()
    {
        if(Auth::check())
        {
        
        return view('dashboard');
        }

        return redirect('/')->with('success', 'Access Denied, You must log in first!');
    }

    public function logout()
    {
        Session::flush();

        Auth::logout();

        return redirect('/');
    }


}
