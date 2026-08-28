<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::all();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'role_id' => ['nullable', 'exists:roles,id', Rule::in($this->assignableRoleIds())],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        if ($request->hasFile('avatar')) {
            $result = upload_file($request->file('avatar'), [
                'base_folder' => 'Users',
                'sub_folders' => [$user->name ?? 'user'],
                'name_prefix' => 'Avatar',
                'name_category' => $user->name ?? 'user',
            ]);
            $user->avatar = $result['path'];
            $user->save();
        }

        if (! empty($validated['role_id'])) {
            DB::table('model_has_roles')->insert([
                'role_id' => $validated['role_id'],
                'model_type' => User::class,
                'model_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        log_activity('user_created', "User {$user->name} created");

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $roles = Role::all();
        $userRoleId = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->value('role_id');

        return view('admin.users.edit', compact('user', 'roles', 'userRoleId'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // FIND-009: a user must never escalate their own role in a single request
        if ($user->id === auth()->id() && $request->filled('role_id')) {
            $current = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->value('role_id');
            $requested = (int) $request->input('role_id');

            if ($current && $requested !== $current) {
                return back()->withInput()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'role_id' => ['nullable', 'exists:roles,id', Rule::in($this->assignableRoleIds())],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        if ($request->hasFile('avatar')) {
            $result = upload_file($request->file('avatar'), [
                'base_folder' => 'Users',
                'sub_folders' => [$user->name ?? 'user'],
                'name_prefix' => 'Avatar',
                'name_category' => $user->name ?? 'user',
            ]);
            $user->avatar = $result['path'];
            $user->save();
        }

        DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->delete();

        if (! empty($validated['role_id'])) {
            DB::table('model_has_roles')->insert([
                'role_id' => $validated['role_id'],
                'model_type' => User::class,
                'model_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        log_activity('user_updated', "User {$user->name} updated");

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Role IDs the current actor is allowed to assign.
     *
     * FIND-009: only super-admins may grant the super-admin role; plain
     * admins can only assign regular roles.
     */
    protected function assignableRoleIds(): array
    {
        if (auth()->user()?->hasRole('super-admin')) {
            return Role::pluck('id')->all();
        }

        return Role::where('slug', '!=', 'super-admin')->pluck('id')->all();
    }

    public function destroy(User $user): RedirectResponse
    {
        // BUG-015 FIX: Cegah admin menghapus dirinya sendiri. Jika satu-satunya
        // super-admin dihapus, tidak ada yang bisa login ke admin panel lagi.
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        log_activity('user_deleted', "User {$user->name} deleted");

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
