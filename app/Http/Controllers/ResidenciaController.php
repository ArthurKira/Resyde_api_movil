<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResidenciaRequest;
use App\Http\Requests\UpdateResidenciaRequest;
use App\Http\Resources\ResidenciaResource;
use App\Models\Residencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResidenciaController extends Controller
{
    /**
     * Listar todas las residencias
     */
    public function index(Request $request): JsonResponse
    {
        $query = Residencia::withCount('users');

        // Búsqueda por nombre
        if ($request->has('search')) {
            $query->where('nombre', 'like', "%{$request->search}%");
        }

        // Filtrar por schema_relacionado
        if ($request->has('schema')) {
            $query->where('schema_relacionado', $request->schema);
        }

        // Filtrar solo las que tienen schema_relacionado
        if ($request->boolean('con_schema')) {
            $query->whereNotNull('schema_relacionado')
                  ->where('schema_relacionado', '!=', '');
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'id_residencia');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $residencias = $query->paginate($perPage);

        return response()->json([
            'data' => ResidenciaResource::collection($residencias),
            'meta' => [
                'current_page' => $residencias->currentPage(),
                'last_page' => $residencias->lastPage(),
                'per_page' => $residencias->perPage(),
                'total' => $residencias->total(),
            ],
        ]);
    }

    /**
     * Crear una nueva residencia
     */
    public function store(StoreResidenciaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['fecha_creacion'] = now();
        $data['usuario_creacion'] = $request->user()->id ?? 1;
        
        $residencia = Residencia::create($data);

        return response()->json([
            'message' => 'Residencia creada exitosamente',
            'data' => new ResidenciaResource($residencia),
        ], 201);
    }

    /**
     * Mostrar una residencia específica
     */
    public function show(Residencia $residencia): JsonResponse
    {
        $residencia->load('users');

        return response()->json([
            'data' => new ResidenciaResource($residencia),
        ]);
    }

    /**
     * Actualizar una residencia
     */
    public function update(UpdateResidenciaRequest $request, Residencia $residencia): JsonResponse
    {
        $data = $request->validated();
        $data['fecha_modificacion'] = now();
        $data['usuario_modificacion'] = $request->user()->id ?? 1;
        
        $residencia->update($data);

        return response()->json([
            'message' => 'Residencia actualizada exitosamente',
            'data' => new ResidenciaResource($residencia),
        ]);
    }

    /**
     * Eliminar una residencia
     */
    public function destroy(Residencia $residencia): JsonResponse
    {
        $residencia->delete();

        return response()->json([
            'message' => 'Residencia eliminada exitosamente',
        ]);
    }
}

