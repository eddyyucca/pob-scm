<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('dashboard.users.index', compact('users'));
    }

    public function create()
    {
        return view('dashboard.users.form', ['user' => null]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|min:8|confirmed',
            'role'                  => 'required|in:admin,viewer',
        ], [
            'email.unique'          => 'Email sudah digunakan.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
            'password.min'          => 'Password minimal 8 karakter.',
        ]);

        User::create([
            'name'     => $v['name'],
            'email'    => $v['email'],
            'password' => Hash::make($v['password']),
            'role'     => $v['role'],
        ]);

        return redirect()->route('users.index')
            ->with('success', "User \"{$v['name']}\" berhasil ditambahkan.");
    }

    public function edit(User $user)
    {
        return view('dashboard.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name'  => 'required|string|max:100',
            'email' => "required|email|unique:users,email,{$user->id}",
            'role'  => 'required|in:admin,viewer',
        ];

        // Password opsional saat edit
        if ($request->filled('password')) {
            $rules['password'] = 'min:8|confirmed';
        }

        $v = $request->validate($rules, [
            'email.unique'       => 'Email sudah digunakan user lain.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min'       => 'Password minimal 8 karakter.',
        ]);

        $data = [
            'name'  => $v['name'],
            'email' => $v['email'],
            'role'  => $v['role'],
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', "Data \"{$v['name']}\" berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        // Tidak boleh hapus diri sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User \"{$name}\" berhasil dihapus.");
    }
}
