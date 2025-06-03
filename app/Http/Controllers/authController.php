<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Communitie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Product; 
use App\Models\Post;
use App\Models\comments;
use App\Models\Admins;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailTest;

class authController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username'=>'required',
            'name'=>'nullable',
            'email'=>'required',
            'password'=>'required',
            'nomor_telepon' => 'required'
        ]);

        $name = $request->name ?? $request->username;

        User::create([
            'username'=>$request->username,
            'name'=>$name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'nomor_telepon'=>$request->nomor_telepon
        ]);
        return response()->json(['message'=>'berhasil registrasi'],201);
    }

    public function login(Request $request)
    {
        $request->validate([ 
            'email'=>'required',
            'password'=>'required'
        ]);
        $user=User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password,$user->password)){
            return response()->json(['message'=>'UNAUTHORIZED'],401);
        }
        $token=$user->createToken('Auth-token')->plainTextToken;
        return response()->json([
            'user'=>$user,
            'token'=>$token
        ],200);
    }


    public function delete(Request $request){

        $request->validate([
            'id'=>'required|integer'
        ]);

        $del = User::findOrFail($request->id);

        $del->delete();
        return response()->json(['message'=>'BERHASIL MENGHAPUS AKUN'],201);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name'=>'string',
            'email'=>'email|unique:users,email,' . $request->id,
            'password'=>'string',
            'nomor_telepon'=>'string|unique:users,nomor_telepon,' . $request->id,
            
        ]);

        $users = Auth::user();

        $users->name = $request->name;
        $users->email = $request->email;
        if ($request->filled('password')) {
            $users->password = Hash::make($request->password);
        }
        if ($request->filled('nomor_telepon')) {
            $users->nomor_telepon = Hash::make($request->nomor_telepon);
        }

        $users->save();

        return response()->json('DATA BERHASIL DIPERBARUI!');
    }

    public function user()
    {
        $user = Auth::user();
        return response()->json([
            'user_id' => $user->user_id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'nomor_telepon' => $user->nomor_telepon,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json('ANDA TELAH BERHASIL LOGOUT! SAMPAI JUMPA KEMBALI :)');
    }


    // Fungsi untuk percobaan saja
    public function tampilkan($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'nomor_telepon' => $user->nomor_telepon,
        ]);
    }

      // Fungsi untuk percobaan saja
    public function image(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', 
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }

        Product::create([
            'name' => $request->name,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
        ]);

        return response()->json(['message' => 'BERHASIL MENAMBAHKAN GAMBAR!']);
    }

    /**
     * Display the password reset form.
     */
    public function showResetForm(Request $request, $token)
    {
        return response()->json([
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $token = Password::createToken(User::where('email', $request->email)->first());
            
            // Create reset link for mobile app
            $resetLink = config('app.mobile_deep_link') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

            // Send email using MailTest
            Mail::to($request->email)->send(new MailTest($resetLink));

            return response()->json(['message' => 'Password reset link sent to your email'], 200);
        } catch (\Exception $e) {
            \Log::error('Password reset email failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to send password reset email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Generate a new token for the user to automatically log them in
            $user = User::where('email', $request->email)->first();
            $token = $user->createToken('Auth-token')->plainTextToken;
            
            return response()->json([
                'message' => 'Password reset successfully',
                'token' => $token,
                'user' => $user
            ], 200);
        }

        return response()->json(['message' => 'Unable to reset password'], 400);
    }

}

