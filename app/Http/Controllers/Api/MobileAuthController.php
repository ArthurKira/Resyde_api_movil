<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Personal;
use App\Models\PersonalResidencia;
use App\Models\AsignacionRecurrente;
use Carbon\Carbon;

class MobileAuthController extends Controller
{
    /**
     * Obtener datos del usuario autenticado (específico para móvil con información de personal y horario)
     * 
     * Nota: Usa el login original /api/auth/login para autenticarse
     * Ahora devuelve TODAS las residencias activas donde trabaja el empleado
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->personal_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no está asociado a un empleado'
                ], 403);
            }

            // Obtener datos del personal
            $personal = Personal::find($user->personal_id);
            if (!$personal) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el empleado asociado'
                ], 404);
            }

            // Obtener TODAS las personal_residencia activas
            $personalResidencias = PersonalResidencia::with('residencia')
                ->where('id_personal', $user->personal_id)
                ->where('activo', true)
                ->get();

            if ($personalResidencias->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El empleado no tiene residencias asignadas activas'
                ], 403);
            }

            // Obtener horario para hoy
            $hoy = Carbon::now()->setTimezone('America/Lima');
            $diaSemana = $hoy->format('l'); // Monday, Tuesday, etc.
            $fechaHoy = $hoy->format('Y-m-d');

            // Procesar cada residencia con su horario
            $residencias = $personalResidencias->map(function($personalResidencia) use ($diaSemana, $fechaHoy) {
                // Buscar horario para hoy en esta residencia específica
                $horarioHoy = AsignacionRecurrente::where('id_personal_residencia', $personalResidencia->id)
                    ->where('activa', true)
                    ->where('dias_semana', 'like', "%{$diaSemana}%")
                    ->where('fecha_inicio', '<=', $fechaHoy)
                    ->where(function($query) use ($fechaHoy) {
                        $query->whereNull('fecha_fin')
                              ->orWhere('fecha_fin', '>=', $fechaHoy);
                    })
                    ->first();

                // Formatear horario si existe
                $horarioData = null;
                if ($horarioHoy) {
                    $horarioData = [
                        'hora_entrada' => Carbon::parse($horarioHoy->hora_entrada)->format('H:i'),
                        'hora_salida' => Carbon::parse($horarioHoy->hora_salida)->format('H:i'),
                        'dias_semana' => explode(',', $horarioHoy->dias_semana),
                        'fecha_inicio' => $horarioHoy->fecha_inicio,
                        'fecha_fin' => $horarioHoy->fecha_fin
                    ];
                }

                return [
                    'id_personal_residencia' => $personalResidencia->id,
                    'cargo' => $personalResidencia->cargo,
                    'residencia' => [
                        'id_residencia' => $personalResidencia->residencia->id_residencia ?? null,
                        'nombre' => $personalResidencia->residencia->nombre ?? 'Sin residencia'
                    ],
                    'tiene_horario_hoy' => $horarioHoy !== null,
                    'horario_hoy' => $horarioData
                ];
            });

            // Verificar si tiene al menos un horario hoy
            $tieneAlgunHorarioHoy = $residencias->where('tiene_horario_hoy', true)->isNotEmpty();

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'personal' => [
                        'id_personal' => $personal->id_personal,
                        'nombres' => $personal->nombres,
                        'apellidos' => $personal->apellidos,
                        'dni_ce' => $personal->dni_ce,
                        'estado' => $personal->estado
                    ],
                    'total_residencias' => $residencias->count(),
                    'tiene_algun_horario_hoy' => $tieneAlgunHorarioHoy,
                    'residencias' => $residencias
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del usuario: ' . $e->getMessage()
            ], 500);
        }
    }

}
