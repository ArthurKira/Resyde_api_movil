<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::with('residencia')
            ->when($request->residencia_id, function ($query, $residenciaId) {
                return $query->where('residencia_id', $residenciaId);
            })
            ->when($request->perfil, function ($query, $perfil) {
                return $query->where('perfil', $perfil);
            })
            ->paginate(15);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Mostrar un usuario específico
     */
    public function show(User $user): JsonResponse
    {
        $user->load('residencia');

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Actualizar un usuario
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        
        // Si se proporciona una nueva contraseña, hashearla
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        $user->update($data);

        $user->load('residencia');

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Eliminar un usuario
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente',
        ]);
    }
}

