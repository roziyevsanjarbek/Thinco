<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'parent' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $roleName = $request->parent === 'on' ? 'parent' : 'user';

        $role = Role::where('name', $roleName)->first();

        if ($role) {
            $user->roles()->attach($role->id);
        }

        event(new Registered($user));
        Auth::login($user);

        $token = $user->createToken('web-token')->plainTextToken;
        session(['api_token' => $token]);

        $role = $user->roles()->first();

        if ($role) {
            switch ($role->name) {
                case 'admin':
                    return redirect()->route('admin.dashboard');
                case 'parent':
                    return redirect()->route('parent.dashboard');
                case 'user':
                    return redirect()->route('user.dashboard');
                default:
                    return redirect()->route('dashboard');
            }
        }

        return redirect()->route('dashboard');
    }


}
