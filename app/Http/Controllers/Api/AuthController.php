<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * --------------------------------------------------
     * 1. REGISTRO DE USUARIO
     * --------------------------------------------------
     */
    public function register(Request $request)
    {
        // --- 1. Validación ---
        $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'apellido1' => ['required', 'string', 'max:255'],
            'cedula'    => ['required', 'string', 'max:20', 'unique:users'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
            'telefono'  => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        // --- 2. Creación del Usuario ---
        $user = User::create([
            'name'   => $request->name,
            'nombre2'   => $request->nombre2, // Opcional
            'apellido1' => $request->apellido1,
            'apellido2' => $request->apellido2, // Opcional
            'cedula'    => $request->cedula,
            'email'     => $request->email,
            'telefono'  => $request->telefono,
            'direccion' => $request->direccion,
            'password'  => Hash::make($request->password),
        ]);

        // --- 3. Asignación de Rol por Defecto ---
        // El usuario por defecto saldrá como usuario.

        $user->assignRole('usuario');

        // --- 4. Respuesta (Token y Usuario) ---
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => '¡Usuario registrado exitosamente!',
            'user'    => $user,
            'token'   => $token,
        ], 201); // 201 = Created
    }

    /**
     * --------------------------------------------------
     * 2. LOGIN DE USUARIO
     * --------------------------------------------------
     */
    public function login(Request $request)
    {
        // --- 1. Validación ---
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // --- 2. Intento de Autenticación ---
        // Intentamos autenticar con las credenciales (email y password)
        if (!Auth::attempt($request->only('email', 'password'))) {
            // Si falla, devolvemos error
            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401); // 401 = Unauthorized
        }

        // --- 3. Éxito: Generar Token ---
        // Si las credenciales son correctas, buscamos al usuario
        $user = User::where('email', $request['email'])->firstOrFail();

        // Creamos un nuevo token para el usuario
        $token = $user->createToken('auth_token')->plainTextToken;

        // --- 4. Respuesta (Token y Rol) ---
        return response()->json([
            'message' => '¡Hola ' . $user->name . '!',
            'token'   => $token,
            'user'    => $user,
            'role'    => $user->getRoleNames()->first()
        ], 200); // 200 = OK
    }

    /**
     * --------------------------------------------------
     * 3. LOGOUT DE USUARIO
     * --------------------------------------------------
     */
    public function logout(Request $request)
    {
        // El middleware 'auth:sanctum' ya validó al usuario
        // Revocamos el token actual (el que usó para esta solicitud)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ], 200); // 200 = OK
    }
}
