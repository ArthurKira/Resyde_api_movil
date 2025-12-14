<?php

namespace App\Http\Controllers;

use App\Http\Resources\HouseResource;
use App\Models\Residencia;
use App\Services\SchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseController extends Controller
{
    protected SchemaService $schemaService;

    public function __construct(SchemaService $schemaService)
    {
        $this->schemaService = $schemaService;
    }

    /**
     * Listar departamentos (houses)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Determinar el schema a usar
            $schema = null;

            if ($user->residencia_id == 0) {
                // Usuario admin puede especificar el schema
                $schema = $request->get('schema');
                if (!$schema) {
                    return response()->json([
                        'message' => 'Debe especificar el parámetro "schema" para usuarios con residencia_id = 0.',
                    ], 400);
                }
            } else {
                // Obtener el schema de la residencia del usuario
                $residencia = Residencia::find($user->residencia_id);
                if (!$residencia || empty($residencia->schema_relacionado)) {
                    return response()->json([
                        'message' => 'La residencia no tiene un schema relacionado configurado.',
                    ], 400);
                }
                $schema = $residencia->schema_relacionado;
            }

            $connectionName = $this->schemaService->getConnectionForSchema($schema);

            // Construir query
            $query = DB::connection($connectionName)->table('houses');

            // Filtros
            if ($request->has('house_number')) {
                $query->where('house_number', 'like', "%{$request->house_number}%");
            }

            if ($request->has('features')) {
                $query->where('features', 'like', "%{$request->features}%");
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('idresidencia')) {
                $query->where('idresidencia', $request->idresidencia);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);

            $total = $query->count();
            $houses = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            return response()->json([
                'data' => HouseResource::collection($houses),
                'meta' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'last_page' => (int) ceil($total / $perPage),
                    'schema' => $schema,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los departamentos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un departamento específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            // Determinar el schema a usar
            $schema = null;

            if ($user->residencia_id == 0) {
                $schema = $request->get('schema');
                if (!$schema) {
                    return response()->json([
                        'message' => 'Debe especificar el parámetro "schema" para usuarios con residencia_id = 0.',
                    ], 400);
                }
            } else {
                $residencia = Residencia::find($user->residencia_id);
                if (!$residencia || empty($residencia->schema_relacionado)) {
                    return response()->json([
                        'message' => 'La residencia no tiene un schema relacionado configurado.',
                    ], 400);
                }
                $schema = $residencia->schema_relacionado;
            }

            $connectionName = $this->schemaService->getConnectionForSchema($schema);

            $house = DB::connection($connectionName)
                ->table('houses')
                ->where('id', $id)
                ->first();

            if (!$house) {
                return response()->json([
                    'message' => 'Departamento no encontrado.',
                ], 404);
            }

            return response()->json([
                'data' => new HouseResource($house),
                'schema' => $schema,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el departamento: ' . $e->getMessage(),
            ], 500);
        }
    }
}

