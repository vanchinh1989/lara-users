<?php

namespace vanchinh1989\larausers\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Profile;

class UsersController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {}

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     */
    public function store(Request $request)
    {
        $rules = [
            'name'                  => 'required|string|max:255|unique:users|alpha_dash',
            'email'                 => 'required|email|max:255|unique:users',
            'password'              => 'required|min:6|max:20|confirmed',
            'password_confirmation' => 'required|same:password',
        ];

        $messages = [
            'name.unique'         => trans('laravelusers::laravelusers.messages.userNameTaken'),
            'name.required'       => trans('laravelusers::laravelusers.messages.userNameRequired'),
            'name'                => trans('laravelusers::laravelusers.messages.userNameInvalid'),
            'email.required'      => trans('laravelusers::laravelusers.messages.emailRequired'),
            'email.email'         => trans('laravelusers::laravelusers.messages.emailInvalid'),
            'password.required'   => trans('laravelusers::laravelusers.messages.passwordRequired'),
            'password.min'        => trans('laravelusers::laravelusers.messages.PasswordMin'),
            'password.max'        => trans('laravelusers::laravelusers.messages.PasswordMax'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Request invalid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $profile = new Profile();
        
        $user = config('larausers.defaultUserModel')::create([
            'name'             => strip_tags($request->input('name')),
            'email'            => $request->input('email'),
            'password'         => Hash::make($request->input('password')),
        ]);

        $user->profile()->save($profile);
        $user->save();

        return response()->json([
            'message' => trans('messages.create-success'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     */
    public function update(Request $request, $id)
    {
        $user = config('larausers.defaultUserModel')::find($id);
        $emailCheck = ($request->input('email') != '') && ($request->input('email') != $user->email);
        $passwordCheck = $request->input('password') != null;

        $rules = [
            'name' => 'required|max:255',
        ];

        if ($emailCheck) {
            $rules['email'] = 'required|email|max:255|unique:users';
        }

        if ($passwordCheck) {
            $rules['password'] = 'required|string|min:6|max:20|confirmed';
            $rules['password_confirmation'] = 'required|string|same:password';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ]);
        }

        $user->name = strip_tags($request->input('name'));

        if ($emailCheck) {
            $user->email = $request->input('email');
        }

        if ($passwordCheck) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return response()->json([
            'message' => trans('messages.update-user-success')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     */
    public function show($id)
    {
        $user = config('larausers.defaultUserModel')::find($id);

        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        $user = config('larausers.defaultUserModel')::find($id);

        if ($currentUser->id != $user->id) {
            $user->delete();

            return response()->json([
                'message' => trans('messages.delete-success')
            ]);
        }

        return response()->json([
            'message' => trans('messages.cannot-delete-yourself')
        ]);
    }
}