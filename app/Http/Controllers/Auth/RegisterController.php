<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Workspace;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Validation\Rules\reCAPTCHA;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        return $user;
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'recaptcha_token' => config('recaptcha.enabled') ? ['required', new reCAPTCHA] : [],
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
            'job' => 'max:255'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'job' => $data['job']
        ]);

        $this->createWorkspace($user);

        $user->sendGreetingNotification();

        return $user->load('workspace');
    }

    protected function createWorkspace(User $user) {
        do { // generate new unique alias
            $alias = strtolower(Str::random(32));
        } while (Workspace::where('alias', $alias)->exists());

        $workspace = $user->workspace()->create([
            'alias' => $alias
        ]);

        return $workspace;
    }
}
