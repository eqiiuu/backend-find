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

    public function adminLogin(Request $request)
    {
        try {
        $form = [
            'username' => $request->username,
            'password' => $request->password
        ];

            $credentials = $request->validate([
                'username' => 'required',
                'password' => 'required'
            ]);

            \Log::info('Login attempt', [
                'username' => $credentials['username'],
                'has_password' => !empty($credentials['password'])
            ]);

            // Check if admin exists
            $admin = \App\Models\Admins::where('username', $credentials['username'])->first();
            if (!$admin) {
                \Log::warning('Admin not found', ['username' => $credentials['username']]);
                return redirect()->route('admin.login')->with('error', 'Invalid credentials');
            }

            \Log::info('Admin found', ['admin_id' => $admin->user_id]);

            // Attempt authentication
            if (Auth::guard('admin')->attempt($credentials)) {
                \Log::info('Login successful', ['admin_id' => $admin->user_id]);
                
                $request->session()->regenerate();
                return redirect()->route('admin.dashboard')->with('success', 'Login successful');
            }

            \Log::warning('Login failed - invalid password', ['admin_id' => $admin->user_id]);
            return redirect()->route('admin.login')->with('error', 'Invalid credentials');
        } catch (\Exception $e) {
            \Log::error('Login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.login')->with('error', 'An error occurred during login');
        }
    }
    
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('login');
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
        return response()->json('SELAMAT DATANG DI F!ND, '.Auth()->User()-> name);
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

}

