<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use App\Models\User;
use Illuminate\Support\Facades\Validator; // <-- IMPORTANTE: Importar Validator

class AuthController extends Controller
{
    /**
     * --------------------------------------------------
     * 1. REGISTRO DE USUARIO
     * --------------------------------------------------
     */
    public function register(Request $request)
    {
        // --- 1. Validación Manual ---
        $validator = Validator::make($request->all(), [
            'name'      => ['required', 'string', 'max:255'],
            'apellido1' => ['required', 'string', 'max:255'],
            'cedula'    => ['required', 'string', 'max:20', 'unique:users,cedula'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
            'telefono'  => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ], [
            // --- Mensajes Personalizados en Español ---
            'cedula.unique' => 'La cédula ingresada ya está registrada en el sistema.',
            'email.unique'  => 'El correo electrónico ya está en uso por otro usuario.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'required'      => 'El campo :attribute es obligatorio.',
        ]);

        // Si la validación falla, devolvemos JSON con error 422 inmediatamente
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error en la validación de datos',
                'errors'  => $validator->errors(),
            ], 422); // 422 Unprocessable Entity
        }

        // --- 2. Creación del Usuario ---
        // Usamos try-catch para capturar cualquier otro error de base de datos inesperado
        try {
            $user = User::create([
                'name'      => $request->name,
                'nombre2'   => $request->nombre2,
                'apellido1' => $request->apellido1,
                'apellido2' => $request->apellido2,
                'cedula'    => $request->cedula,
                'email'     => $request->email,
                'telefono'  => $request->telefono,
                'direccion' => $request->direccion,
                'password'  => Hash::make($request->password),
            ]);

            // --- 3. Asignación de Rol ---
            $user->assignRole('usuario');

            // --- 4. Respuesta ---
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => '¡Usuario registrado exitosamente!',
                'user'    => $user,
                'token'   => $token,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno al crear el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        // Puedes aplicar la misma lógica de Validator aquí si quieres mensajes personalizados
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => '¡Hola ' . $user->name . '!',
            'token'   => $token,
            'user'    => $user,
            'role'    => $user->getRoleNames()->first()
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada exitosamente'], 200);
    }
}