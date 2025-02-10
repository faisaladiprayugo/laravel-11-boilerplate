<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Session;

use App\Models\AuthenticationTokens;
use App\Models\Users;

class DocumentationController extends Controller
{
    public function loginProcess(Request $request)
    {
        $email = $request->input('email');
        $data = Users::where('email', $email)
            ->first();

        if ($email == 'superadmin@mail.co' && $request->input('password') == 'superadminmuslimhabitpass') {
            $str_token = Str::random(100);
            $expired = Carbon::now()->endOfDay();

            $auth_token = new AuthenticationTokens();
            $auth_token->authentication_token_id = Str::uuid7();
            $auth_token->user_auth = "api";
            $auth_token->token = $str_token;
            $auth_token->expired = $expired;
            $auth_token->save();

            AuthenticationTokens::where('user_auth', 'api')
                ->where('expired', '<=', $expired->copy()->subDays(3))
                ->delete();

            Session::put('login', TRUE);
            Session::put('token', $str_token);

            return redirect('/api/documentation')->with('success', 'Welcome to Muslim Habit API.');
        }

        if ($data === null) {
            return redirect('/')->with('error', 'Account data not found.');
        }

        if ($request->input('password') != null) {
            $decrypt = Hash::check($request->input('password'), $data->password);

            if ($decrypt) {
                $data = Users::where('email', $email)
                    ->select([
                        'user_id',
                        'email',
                        'created_at',
                        'updated_at'
                    ])->first();

                $str_token = Str::random(100);
                $expired = Carbon::now()->endOfDay();

                $auth_token = new AuthenticationTokens();
                $auth_token->authentication_token_id = Str::uuid7();
                $auth_token->user_auth = "api";
                $auth_token->token = $str_token;
                $auth_token->expired = $expired;
                $auth_token->save();

                AuthenticationTokens::where('user_auth', 'api')
                    ->where('expired', '<=', $expired->copy()->subDays(1))
                    ->delete();

                Session::put('user_id', $data->user_id);
                Session::put('login', TRUE);
                Session::put('token', $str_token);

                return redirect('/api/documentation')->with('success', 'Welcome to Muslim Habit API.');
            } else {
                Session::flush();
                return redirect('/')->with('error', 'Wrong password.');
            }
        } else {
            return redirect('/')->with('error', 'Password is required.');
        }
    }

    public function login()
    {
        if (Session::has('login')) {
            return redirect('/api/documentation');
        } else {
            return view('login');
        }
    }

    public function logout()
    {
        Session::flush();
        return redirect('/')->with('alert', 'Logout successful.');
    }
}
