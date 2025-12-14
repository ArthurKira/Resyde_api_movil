<?php

namespace App\Http\Controllers;

use App\Http\Resources\TenantResource;
use App\Models\Residencia;
use App\Services\SchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    protected SchemaService $schemaService;

    public function __construct(SchemaService $schemaService)
    {
        $this->schemaService = $schemaService;
    }

    /**
     * Listar residentes (tenants)
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
            $query = DB::connection($connectionName)->table('tenants');

            // Filtros
            if ($request->has('fullname')) {
                $query->where('fullname', 'like', "%{$request->fullname}%");
            }

            if ($request->has('email')) {
                $query->where('email', 'like', "%{$request->email}%");
            }

            if ($request->has('phone_number')) {
                $query->where('phone_number', 'like', "%{$request->phone_number}%");
            }

            if ($request->has('house')) {
                $query->where('house', $request->house);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);

            $total = $query->count();
            $tenants = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            return response()->json([
                'data' => TenantResource::collection($tenants),
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
                'message' => 'Error al obtener los residentes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un residente específico
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

            $tenant = DB::connection($connectionName)
                ->table('tenants')
                ->where('id', $id)
                ->first();

            if (!$tenant) {
                return response()->json([
                    'message' => 'Residente no encontrado.',
                ], 404);
            }

            return response()->json([
                'data' => new TenantResource($tenant),
                'schema' => $schema,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el residente: ' . $e->getMessage(),
            ], 500);
        }
    }
}

