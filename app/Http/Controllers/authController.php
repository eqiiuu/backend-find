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

class authController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'email'=>'required',
            'password'=>'required',
            'nomor_telepon' => 'required'
        ]);
        User::create([
            'name'=>$request->name,
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
        'id'=>'required|integer',
        'name'=>'string',
        'email'=>'email|unique:users,email,' . $request->id,
        'password'=>'string',
        'nomor_telepon'=>'string|unique:users,nomor_telepon,' . $request->id,
        
    ]);

    $users = User::findOrFail($request->id);

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

  public function store(Request $request)
    {
        $request->validate([
            'community_id' => 'required',
            'owner_id' => 'required',
            'gambar' => 'nullable',
            'koordinat' => 'required',
            'description' => 'required',
            'anggota' => 'nullable',
            'capacity' => 'required',
        ]);
        Communitie::create([
            'community_id'=>$request->community_id,
            'owner_id'=>$request->owner_id,
            'gambar'=>$request->gambar,
            'koordinat'=>$request->koordinat,
            'description'=>$request->description,
            'anggota' =>$request->anggota,
            'capacity'=>$request->capacity
        ]);
        return response()->json(['message'=>'BBERHASIL REGISTRASI!'], 201);
    }

    // Menampilkan detail komunitas tertentu
    public function show($id)
    {
        return Community::findOrFail($id);
    }

    public function tampilkan($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'nomor_telepon' => $user->nomor_telepon,
        ]);
    }

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

    // Mengupdate komunitas tertentu
    public function perbarui(Request $request, $id)
    {
        $community = Communitie::findOrFail($id);
        $community->update($request->all());
        return response()->json($community);
    }

    // Menghapus komunitas
    public function destroy($id)
    {
        Community::destroy($id);
        return response()->json(['message' => 'KOMUNITAS BERHASIL DIHAPUS']);
    }

}

