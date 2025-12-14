<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMedidorRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Residencia;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Recibos",
 *     description="Endpoints para gestionar recibos/invoices"
 * )
 */
class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * @OA\Get(
     *     path="/api/recibos",
     *     summary="Listar recibos del usuario autenticado",
     *     tags={"Recibos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="schema",
     *         in="query",
     *         description="Schema específico (solo para usuarios admin con residencia_id=0)",
     *         required=false,
     *         @OA\Schema(type="string", example="uclubperu_rentalmanagement")
     *     ),
     *     @OA\Parameter(
     *         name="tenant",
     *         in="query",
     *         description="Filtrar por ID de residente",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="house",
     *         in="query",
     *         description="Filtrar por ID de departamento",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filtrar por año",
     *         required=false,
     *         @OA\Schema(type="string", example="2024")
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Filtrar por mes",
     *         required=false,
     *         @OA\Schema(type="string", example="03")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado",
     *         required=false,
     *         @OA\Schema(type="string", example="paid")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de recibos",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Obtener el usuario autenticado
            $user = $request->user();

            // Si residencia_id es 0, el usuario puede acceder a todos los schemas
            if ($user->residencia_id == 0) {
                // Obtener todas las residencias con schema_relacionado configurado
                $residencias = Residencia::whereNotNull('schema_relacionado')
                    ->where('schema_relacionado', '!=', '')
                    ->get();

                if ($residencias->isEmpty()) {
                    return response()->json([
                        'message' => 'No hay residencias con schemas relacionados configurados.',
                    ], 404);
                }

                // Si se especifica un schema específico, filtrar por ese
                $schemaFilter = $request->get('schema');
                if ($schemaFilter) {
                    $residencias = $residencias->where('schema_relacionado', $schemaFilter);
                    if ($residencias->isEmpty()) {
                        return response()->json([
                            'message' => 'El schema especificado no existe o no está configurado.',
                        ], 404);
                    }
                }

                // Preparar filtros base
                $filters = [];

                // Filtros opcionales
                if ($request->has('tenant')) {
                    $filters['tenant'] = $request->get('tenant');
                }

                if ($request->has('house')) {
                    $filters['house'] = $request->get('house');
                }

                if ($request->has('year')) {
                    $filters['year'] = $request->get('year');
                }

                if ($request->has('month')) {
                    $filters['month'] = $request->get('month');
                }

                if ($request->has('status')) {
                    $filters['status'] = $request->get('status');
                }

                // Parámetros de paginación
                $perPage = $request->get('per_page', 15);
                $page = $request->get('page', 1);

                // PASO 1: Obtener el COUNT total de todos los schemas (sin cargar datos)
                $totalCount = 0;
                foreach ($residencias as $residencia) {
                    try {
                        $connectionName = $this->invoiceService->getConnectionForSchema($residencia->schema_relacionado);
                        $totalCount += $this->invoiceService->countInvoices($connectionName, $filters);
                    } catch (\Exception $e) {
                        // Continuar con el siguiente schema si hay error
                        continue;
                    }
                }

                // PASO 2: Obtener TODOS los IDs ordenados de todos los schemas
                $allInvoiceIds = [];
                foreach ($residencias as $residencia) {
                    try {
                        $connectionName = $this->invoiceService->getConnectionForSchema($residencia->schema_relacionado);
                        $ids = $this->invoiceService->getInvoiceIds($connectionName, $filters);
                        
                        foreach ($ids as $idData) {
                            // $idData es un objeto stdClass con propiedad 'id'
                            $allInvoiceIds[] = [
                                'id' => $idData->id,
                                'schema' => $residencia->schema_relacionado,
                                'residencia_nombre' => $residencia->nombre,
                            ];
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                // PASO 3: Ordenar todos los IDs por ID descendente
                usort($allInvoiceIds, function($a, $b) {
                    return $b['id'] <=> $a['id'];
                });

                // PASO 4: Obtener solo los IDs de la página actual
                $offset = ($page - 1) * $perPage;
                $idsForCurrentPage = array_slice($allInvoiceIds, $offset, $perPage);

                // PASO 5: Obtener los datos completos solo de los recibos de esta página
                $invoices = [];
                foreach ($idsForCurrentPage as $idData) {
                    try {
                        $connectionName = $this->invoiceService->getConnectionForSchema($idData['schema']);
                        $invoice = $this->invoiceService->getInvoiceById($connectionName, $idData['id']);
                        
                        if ($invoice) {
                            $invoice->schema = $idData['schema'];
                            $invoice->residencia_nombre = $idData['residencia_nombre'];
                            $invoices[] = $invoice;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                return response()->json([
                    'data' => InvoiceResource::collection(collect($invoices)),
                    'meta' => [
                        'current_page' => (int) $page,
                        'per_page' => (int) $perPage,
                        'total' => $totalCount,
                        'last_page' => (int) ceil($totalCount / $perPage),
                    ],
                ]);
            }

            // Lógica original para usuarios con residencia_id específico
            if (!$user->residencia_id) {
                return response()->json([
                    'message' => 'El usuario no está asociado a ninguna residencia.',
                ], 400);
            }

            // Obtener la residencia del usuario
            $residencia = Residencia::find($user->residencia_id);

            if (!$residencia) {
                return response()->json([
                    'message' => 'La residencia asociada no existe.',
                ], 404);
            }

            // Obtener el schema relacionado
            $schema = $residencia->schema_relacionado;

            if (empty($schema)) {
                return response()->json([
                    'message' => 'La residencia no tiene un schema relacionado configurado.',
                ], 400);
            }

            // Obtener la conexión dinámica para este schema
            $connectionName = $this->invoiceService->getConnectionForSchema($schema);

            // Preparar filtros
            $filters = [
                'page' => $request->get('page', 1),
                'per_page' => $request->get('per_page', 15),
            ];

            // Filtros opcionales
            if ($request->has('tenant')) {
                $filters['tenant'] = $request->get('tenant');
            }

            if ($request->has('house')) {
                $filters['house'] = $request->get('house');
            }

            if ($request->has('year')) {
                $filters['year'] = $request->get('year');
            }

            if ($request->has('month')) {
                $filters['month'] = $request->get('month');
            }

            if ($request->has('status')) {
                $filters['status'] = $request->get('status');
            }

            // Obtener los recibos
            $result = $this->invoiceService->getInvoices($connectionName, $filters);

            return response()->json([
                'data' => InvoiceResource::collection(collect($result['data'])),
                'meta' => $result['meta'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los recibos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/recibos/{id}",
     *     summary="Obtener un recibo específico",
     *     tags={"Recibos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del recibo",
     *         required=true,
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Parameter(
     *         name="schema",
     *         in="query",
     *         description="Schema específico (solo para usuarios admin)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recibo encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Recibo no encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            // Si residencia_id es 0, buscar en todos los schemas
            if ($user->residencia_id == 0) {
                // Obtener todas las residencias con schema_relacionado configurado
                $residencias = Residencia::whereNotNull('schema_relacionado')
                    ->where('schema_relacionado', '!=', '')
                    ->get();

                // Si se especifica un schema, buscar solo en ese
                $schemaFilter = $request->get('schema');
                if ($schemaFilter) {
                    $residencias = $residencias->where('schema_relacionado', $schemaFilter);
                }

                // Buscar el recibo en todos los schemas
                foreach ($residencias as $residencia) {
                    try {
                        $connectionName = $this->invoiceService->getConnectionForSchema($residencia->schema_relacionado);

                        $invoice = \Illuminate\Support\Facades\DB::connection($connectionName)
                            ->table('invoices')
                            ->select([
                                'invoices.*',
                                'tenants.fullname AS residente_nombre',
                                'tenants.phone_number AS residente_telefono',
                                'tenants.email AS residente_email',
                                'houses.house_number AS departamento_numero',
                                'houses.features AS departamento_nombre',
                            ])
                            ->leftJoin('tenants', 'invoices.tenant', '=', 'tenants.id')
                            ->leftJoin('houses', 'tenants.house', '=', 'houses.id')
                            ->where('invoices.id', $id)
                            ->first();

                        if ($invoice) {
                            // Agregar detalles al invoice
                            $invoice->detalles = $this->invoiceService->getInvoiceDetalles($connectionName, $id);
                            
                            // Agregar información del schema
                            $invoice->schema = $residencia->schema_relacionado;
                            $invoice->residencia_nombre = $residencia->nombre;
                            
                            return response()->json([
                                'data' => new InvoiceResource($invoice),
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Continuar con el siguiente schema si hay error
                        continue;
                    }
                }

                return response()->json([
                    'message' => 'Recibo no encontrado en ningún schema.',
                ], 404);
            }

            // Lógica original para usuarios con residencia_id específico
            if (!$user->residencia_id) {
                return response()->json([
                    'message' => 'El usuario no está asociado a ninguna residencia.',
                ], 400);
            }

            $residencia = Residencia::find($user->residencia_id);

            if (!$residencia || empty($residencia->schema_relacionado)) {
                return response()->json([
                    'message' => 'La residencia no tiene un schema relacionado configurado.',
                ], 400);
            }

            $connectionName = $this->invoiceService->getConnectionForSchema($residencia->schema_relacionado);

            $invoice = \Illuminate\Support\Facades\DB::connection($connectionName)
                ->table('invoices')
                ->select([
                    'invoices.*',
                    'tenants.fullname AS residente_nombre',
                    'tenants.phone_number AS residente_telefono',
                    'tenants.email AS residente_email',
                    'houses.house_number AS departamento_numero',
                    'houses.features AS departamento_nombre',
                ])
                ->leftJoin('tenants', 'invoices.tenant', '=', 'tenants.id')
                ->leftJoin('houses', 'tenants.house', '=', 'houses.id')
                ->where('invoices.id', $id)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'message' => 'Recibo no encontrado.',
                ], 404);
            }

            // Agregar detalles al invoice
            $invoice->detalles = $this->invoiceService->getInvoiceDetalles($connectionName, $id);

            return response()->json([
                'data' => new InvoiceResource($invoice),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el recibo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/recibos/{id}/medidor",
     *     summary="Subir imagen del medidor y actualizar lectura actual",
     *     tags={"Recibos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del recibo",
     *         required=true,
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Parameter(
     *         name="schema",
     *         in="query",
     *         description="Schema específico (solo para usuarios admin)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"imagen","lectura_actual"},
     *                 @OA\Property(
     *                     property="imagen",
     *                     type="string",
     *                     format="binary",
     *                     description="Imagen del medidor (jpeg, jpg, png, webp, máximo 5MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="lectura_actual",
     *                     type="string",
     *                     example="1250.50",
     *                     description="Lectura actual del medidor"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Imagen y lectura actualizadas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="imagen_url", type="string", example="https://erp.tudominio.com/storage/medidores/agua/2024/03/invoice_123.jpg")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=404, description="Recibo no encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function updateMedidor(UpdateMedidorRequest $request, int $id): JsonResponse
    {
        try {
            Log::info('=== INICIO updateMedidor ===', [
                'invoice_id' => $id,
                'lectura_actual' => $request->lectura_actual,
                'tiene_imagen' => $request->hasFile('imagen'),
                'imagen_nombre' => $request->hasFile('imagen') ? $request->file('imagen')->getClientOriginalName() : null,
                'imagen_tamaño' => $request->hasFile('imagen') ? $request->file('imagen')->getSize() : null,
            ]);

            $user = $request->user();
            Log::info('Usuario autenticado', [
                'user_id' => $user->id,
                'residencia_id' => $user->residencia_id,
                'email' => $user->email,
            ]);

            $connectionName = null;
            $residencia = null;

            // Determinar el schema a usar
            if ($user->residencia_id == 0) {
                Log::info('Usuario es ADMIN - buscando schema');
                // Usuario admin - debe especificar el schema
                $schemaFilter = $request->get('schema');
                if (!$schemaFilter) {
                    return response()->json([
                        'message' => 'Debe especificar el parámetro "schema" para usuarios con residencia_id = 0.',
                    ], 400);
                }

                $residencia = Residencia::where('schema_relacionado', $schemaFilter)
                    ->whereNotNull('schema_relacionado')
                    ->where('schema_relacionado', '!=', '')
                    ->first();

                if (!$residencia) {
                    return response()->json([
                        'message' => 'El schema especificado no existe o no está configurado.',
                    ], 404);
                }

                $connectionName = $this->invoiceService->getConnectionForSchema($residencia->schema_relacionado);
                Log::info('Schema obtenido (Admin)', [
                    'schema' => $residencia->schema_relacionado,
                    'connection_name' => $connectionName,
                ]);
            } else {
                // Usuario normal - usar su residencia
                Log::info('Usuario NORMAL - buscando residencia', [
                    'residencia_id' => $user->residencia_id,
                ]);

                if (!$user->residencia_id) {
                    Log::error('Usuario sin residencia_id');
                    return response()->json([
                        'message' => 'El usuario no está asociado a ninguna residencia.',
                    ], 400);
                }

                $residencia = Residencia::find($user->residencia_id);
                Log::info('Residencia encontrada', [
                    'residencia' => $residencia ? $residencia->toArray() : null,
                ]);

                if (!$residencia || empty($residencia->schema_relacionado)) {
                    Log::error('Residencia sin schema', [
                        'residencia_id' => $user->residencia_id,
                        'residencia' => $residencia ? $residencia->toArray() : null,
                    ]);
                    return response()->json([
                        'message' => 'La residencia no tiene un schema relacionado configurado.',
                    ], 400);
                }

                $connectionName = $this->invoiceService->getConnectionForSchema($residencia->schema_relacionado);
                Log::info('Schema obtenido (Usuario Normal)', [
                    'schema' => $residencia->schema_relacionado,
                    'connection_name' => $connectionName,
                ]);
            }

            // Verificar que el recibo existe
            Log::info('Buscando recibo en base de datos', [
                'connection' => $connectionName,
                'invoice_id' => $id,
            ]);

            try {
                $invoice = DB::connection($connectionName)
                    ->table('invoices')
                    ->where('id', $id)
                    ->first();

                Log::info('Resultado búsqueda recibo', [
                    'encontrado' => $invoice ? true : false,
                    'invoice_data' => $invoice ? [
                        'id' => $invoice->id,
                        'year' => $invoice->year ?? null,
                        'month' => $invoice->month ?? null,
                    ] : null,
                ]);
            } catch (\Exception $e) {
                Log::error('Error al buscar recibo', [
                    'connection' => $connectionName,
                    'invoice_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            if (!$invoice) {
                Log::warning('Recibo no encontrado', [
                    'connection' => $connectionName,
                    'invoice_id' => $id,
                ]);
                return response()->json([
                    'message' => 'Recibo no encontrado.',
                ], 404);
            }

            // Subir la imagen
            Log::info('Procesando imagen');
            $imagen = $request->file('imagen');
            $year = $invoice->year ?? date('Y');
            $month = $invoice->month ?? date('m');
            
            Log::info('Datos para guardar imagen', [
                'year' => $year,
                'month' => $month,
                'imagen_extension' => $imagen->getClientOriginalExtension(),
                'imagen_size' => $imagen->getSize(),
            ]);
            
            // Crear ruta: medidores/agua/{año}/{mes}/invoice_{id}_{timestamp}.{extension}
            $nombreArchivo = 'invoice_' . $id . '_' . time() . '.' . $imagen->getClientOriginalExtension();
            $ruta = 'medidores/agua/' . $year . '/' . $month . '/' . $nombreArchivo;
            
            Log::info('Guardando imagen en storage', [
                'ruta' => $ruta,
                'nombre_archivo' => $nombreArchivo,
                'disk' => 'erp_storage',
            ]);

            try {
                // Guardar la imagen en el storage del ERP
                $rutaCompleta = $imagen->storeAs('medidores/agua/' . $year . '/' . $month, $nombreArchivo, 'erp_storage');
                Log::info('Imagen guardada exitosamente', [
                    'ruta_completa' => $rutaCompleta,
                ]);

                // Obtener la URL pública de la imagen (desde el ERP)
                $urlImagen = Storage::disk('erp_storage')->url($rutaCompleta);
                Log::info('URL de imagen generada', [
                    'url' => $urlImagen,
                ]);
            } catch (\Exception $e) {
                Log::error('Error al guardar imagen', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            // Calcular diferencia: lectura_actual - lectura_pasada
            $lecturaPasada = $invoice->lectura_pasada ?? null;
            $lecturaActual = $request->lectura_actual;
            $diferencia = null;

            if ($lecturaPasada !== null && $lecturaPasada !== '' && $lecturaActual !== null && $lecturaActual !== '') {
                try {
                    $diferenciaCalculada = (float)$lecturaActual - (float)$lecturaPasada;
                    // Formatear a 2 decimales y convertir a string
                    $diferencia = number_format($diferenciaCalculada, 2, '.', '');
                    Log::info('Diferencia calculada', [
                        'lectura_pasada' => $lecturaPasada,
                        'lectura_actual' => $lecturaActual,
                        'diferencia' => $diferencia,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Error al calcular diferencia', [
                        'lectura_pasada' => $lecturaPasada,
                        'lectura_actual' => $lecturaActual,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::info('No se puede calcular diferencia', [
                    'lectura_pasada' => $lecturaPasada,
                    'lectura_actual' => $lecturaActual,
                    'razon' => 'Valores NULL o vacíos',
                ]);
            }

            // Actualizar el recibo con la imagen, lectura actual y diferencia
            Log::info('Actualizando recibo en base de datos', [
                'connection' => $connectionName,
                'invoice_id' => $id,
                'medidor_image' => $rutaCompleta,
                'lectura_actual' => $lecturaActual,
                'diferencia' => $diferencia,
            ]);

            try {
                // Preparar datos para actualizar
                $updateData = [
                    'medidor_image' => $rutaCompleta, // Guardar la ruta relativa
                    'lectura_actual' => $lecturaActual,
                ];

                // Agregar diferencia solo si se calculó
                if ($diferencia !== null) {
                    $updateData['diferencia'] = $diferencia;
                }

                // Intentar actualizar con updated_at primero
                $updateData['updated_at'] = now();

                try {
                    DB::connection($connectionName)
                        ->table('invoices')
                        ->where('id', $id)
                        ->update($updateData);
                    Log::info('Recibo actualizado exitosamente (con updated_at)');
                } catch (\Exception $e) {
                    // Si falla porque no existe updated_at, intentar sin ese campo
                    if (str_contains($e->getMessage(), 'updated_at') || str_contains($e->getMessage(), "Unknown column 'updated_at'")) {
                        Log::info('Columna updated_at NO existe, actualizando sin ese campo');
                        unset($updateData['updated_at']);
                        
                        DB::connection($connectionName)
                            ->table('invoices')
                            ->where('id', $id)
                            ->update($updateData);
                        Log::info('Recibo actualizado exitosamente (sin updated_at)');
                    } else {
                        // Si es otro error, relanzarlo
                        throw $e;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error al actualizar recibo', [
                    'connection' => $connectionName,
                    'invoice_id' => $id,
                    'error' => $e->getMessage(),
                    'sql_state' => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            // Obtener el recibo actualizado con información relacionada
            Log::info('Obteniendo recibo actualizado con relaciones');
            try {
                $invoiceActualizado = DB::connection($connectionName)
                    ->table('invoices')
                    ->select([
                        'invoices.*',
                        'tenants.fullname AS residente_nombre',
                        'tenants.phone_number AS residente_telefono',
                        'tenants.email AS residente_email',
                        'houses.house_number AS departamento_numero',
                        'houses.features AS departamento_nombre',
                    ])
                    ->leftJoin('tenants', 'invoices.tenant', '=', 'tenants.id')
                    ->leftJoin('houses', 'tenants.house', '=', 'houses.id')
                    ->where('invoices.id', $id)
                    ->first();

                Log::info('Recibo con relaciones obtenido', [
                    'encontrado' => $invoiceActualizado ? true : false,
                ]);

                // Agregar detalles al invoice
                Log::info('Obteniendo detalles del recibo');
                $invoiceActualizado->detalles = $this->invoiceService->getInvoiceDetalles($connectionName, $id);
                Log::info('Detalles obtenidos', [
                    'cantidad_detalles' => is_array($invoiceActualizado->detalles) ? count($invoiceActualizado->detalles) : 0,
                ]);
            } catch (\Exception $e) {
                Log::error('Error al obtener recibo actualizado', [
                    'connection' => $connectionName,
                    'invoice_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            // Agregar información del schema si es admin
            if ($user->residencia_id == 0) {
                $invoiceActualizado->schema = $residencia->schema_relacionado;
                $invoiceActualizado->residencia_nombre = $residencia->nombre;
            }

            Log::info('=== FIN updateMedidor - ÉXITO ===', [
                'invoice_id' => $id,
                'imagen_url' => $urlImagen,
            ]);

            return response()->json([
                'message' => 'Imagen del medidor y lectura actual actualizadas exitosamente.',
                'data' => new InvoiceResource($invoiceActualizado),
                'imagen_url' => $urlImagen, // URL pública para acceso directo
            ]);

        } catch (\Exception $e) {
            Log::error('=== ERROR updateMedidor ===', [
                'invoice_id' => $id,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => [
                    'lectura_actual' => $request->lectura_actual ?? null,
                    'tiene_imagen' => $request->hasFile('imagen'),
                ],
            ]);

            return response()->json([
                'message' => 'Error al actualizar el medidor: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ] : null,
            ], 500);
        }
    }
}

