<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\RegistroAsistencia;
use App\Models\PersonalResidencia;
use App\Models\Personal;
use App\Models\AsignacionRecurrente;
use App\Models\Vacacion;
use App\Models\Licencia;
use Carbon\Carbon;

class MobileAsistenciaController extends Controller
{
    /**
     * Obtener personal_residencia basado en dni_ce o usuario autenticado
     * 
     * @param Request $request
     * @return array ['personal_residencia' => PersonalResidencia|null, 'personal' => Personal|null, 'error' => string|null]
     */
    private function obtenerPersonalResidencia(Request $request)
    {
        $user = $request->user();
        $dniCeObjetivo = $request->input('dni_ce');

        // Si se proporciona dni_ce, buscar personal por DNI
        if ($dniCeObjetivo) {
            // Buscar personal por DNI
            $personalObjetivo = Personal::where('dni_ce', $dniCeObjetivo)->first();

            if (!$personalObjetivo) {
                return [
                    'personal_residencia' => null,
                    'personal' => null,
                    'error' => 'No se encontró personal con el DNI proporcionado'
                ];
            }

            // Validar que el personal esté activo
            if ($personalObjetivo->estado !== 'Activo') {
                return [
                    'personal_residencia' => null,
                    'personal' => $personalObjetivo,
                    'error' => 'El personal no está activo'
                ];
            }

            // Obtener personal_residencia activa del personal objetivo
            $personalResidencia = PersonalResidencia::where('id_personal', $personalObjetivo->id_personal)
                ->where('activo', true)
                ->first();

            if (!$personalResidencia) {
                return [
                    'personal_residencia' => null,
                    'personal' => $personalObjetivo,
                    'error' => 'El empleado no tiene residencias asignadas activas'
                ];
            }

            return [
                'personal_residencia' => $personalResidencia,
                'personal' => $personalObjetivo,
                'error' => null
            ];
        }

        // Modo: Marcar/Ver propia asistencia (comportamiento original)
        if (!$user->personal_id) {
            return [
                'personal_residencia' => null,
                'personal' => null,
                'error' => 'El usuario no está asociado a un empleado'
            ];
        }

        // Obtener personal_residencia activa del usuario autenticado
        $personalResidencia = PersonalResidencia::where('id_personal', $user->personal_id)
            ->where('activo', true)
            ->first();

        if (!$personalResidencia) {
            return [
                'personal_residencia' => null,
                'personal' => null,
                'error' => 'El empleado no tiene residencias asignadas activas'
            ];
        }

        $personal = Personal::find($user->personal_id);

        return [
            'personal_residencia' => $personalResidencia,
            'personal' => $personal,
            'error' => null
        ];
    }

    /**
     * Guardar foto de asistencia con nombre cifrado
     * 
     * @param \Illuminate\Http\UploadedFile $foto
     * @param string $tipo 'entrada' o 'salida'
     * @param int $idRegistro
     * @param int $personalId
     * @param string $fecha Fecha en formato Y-m-d
     * @return array ['ruta_relativa' => string, 'url_publica' => string]
     */
    private function guardarFotoAsistencia($foto, $tipo, $idRegistro, $personalId, $fecha)
    {
        // Obtener ruta base desde .env (default: asistencia/fotos)
        $rutaBase = env('ASISTENCIA_FOTOS_PATH', 'asistencia/fotos');
        
        // Extraer año y mes de la fecha
        $year = date('Y', strtotime($fecha));
        $month = date('m', strtotime($fecha));
        
        // Obtener extensión del archivo
        $extension = $foto->getClientOriginalExtension();
        
        // Generar hash único para el nombre del archivo
        // Combinar: id_registro + timestamp + random_bytes + personal_id
        $hash = substr(
            hash('sha256', $idRegistro . time() . random_bytes(16) . $personalId),
            0,
            16
        );
        
        // Nombre del archivo: {tipo}_{hash}.{extension}
        $nombreArchivo = $tipo . '_' . $hash . '.' . $extension;
        
        // Ruta completa: {ruta_base}/{año}/{mes}/{nombre_archivo}
        $rutaCompleta = $rutaBase . '/' . $year . '/' . $month . '/' . $nombreArchivo;
        
        // Guardar en el storage del ERP
        $rutaGuardada = $foto->storeAs(
            $rutaBase . '/' . $year . '/' . $month,
            $nombreArchivo,
            'erp_storage'
        );
        
        // Obtener URL pública
        $urlPublica = Storage::disk('erp_storage')->url($rutaGuardada);
        
        return [
            'ruta_relativa' => $rutaGuardada,
            'url_publica' => $urlPublica
        ];
    }

    /**
     * Obtener estado de asistencia del día actual
     * 
     * Query params opcionales:
     * - dni_ce: DNI del empleado
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function estado(Request $request)
    {
        try {
            // Obtener personal_residencia (propia o de otro si se pasa dni_ce)
            $resultado = $this->obtenerPersonalResidencia($request);

            if ($resultado['error']) {
                $codigoError = str_contains($resultado['error'], 'No se encontró') ? 404 : 403;
                return response()->json([
                    'success' => false,
                    'message' => $resultado['error']
                ], $codigoError);
            }

            $personalResidencia = $resultado['personal_residencia'];
            $personalObjetivo = $resultado['personal'];
            $personalIdParaConsulta = $personalObjetivo ? $personalObjetivo->id_personal : $request->user()->personal_id;

            // Fecha de hoy (zona horaria Perú)
            $hoy = Carbon::now()->setTimezone('America/Lima');
            $fechaHoy = $hoy->format('Y-m-d');

            // Buscar registro de asistencia de hoy
            $registro = RegistroAsistencia::where('id_personal_residencia', $personalResidencia->id)
                ->where('fecha_entrada', $fechaHoy)
                ->first();

            // Verificar horario para hoy
            $diaSemana = $hoy->format('l');
            $tieneHorario = AsignacionRecurrente::where('id_personal_residencia', $personalResidencia->id)
                ->where('activa', true)
                ->where('dias_semana', 'like', "%{$diaSemana}%")
                ->where('fecha_inicio', '<=', $fechaHoy)
                ->where(function($query) use ($fechaHoy) {
                    $query->whereNull('fecha_fin')
                          ->orWhere('fecha_fin', '>=', $fechaHoy);
                })
                ->exists();

            // Verificar vacaciones/licencias
            $enVacaciones = false;
            $enLicencia = false;

            try {
                $enVacaciones = Vacacion::where('id_personal', $personalIdParaConsulta)
                    ->where('estado', 'Aprobada')
                    ->where('fecha_inicio', '<=', $fechaHoy)
                    ->where('fecha_fin', '>=', $fechaHoy)
                    ->exists();
            } catch (\Exception $e) {
                // Si no existe el modelo de vacaciones, continuar
            }

            try {
                $enLicencia = Licencia::where('id_personal', $personalIdParaConsulta)
                    ->where('estado', 'Aprobada')
                    ->where('fecha_inicio', '<=', $fechaHoy)
                    ->where('fecha_fin', '>=', $fechaHoy)
                    ->exists();
            } catch (\Exception $e) {
                // Si no existe el modelo de licencias, continuar
            }

            $tieneEntrada = $registro && $registro->hora_entrada !== null;
            $tieneSalida = $registro && $registro->hora_salida !== null;
            $puedeMarcarEntrada = !$tieneEntrada && $tieneHorario && !$enVacaciones && !$enLicencia;
            $puedeMarcarSalida = $tieneEntrada && !$tieneSalida;

            $mensaje = '';
            if ($enVacaciones) {
                $mensaje = 'El empleado está en vacaciones aprobadas';
            } elseif ($enLicencia) {
                $mensaje = 'El empleado está en licencia aprobada';
            } elseif (!$tieneHorario) {
                $mensaje = 'No tiene horario asignado para hoy';
            } elseif ($tieneEntrada && $tieneSalida) {
                $mensaje = 'Asistencia completa del día';
            } elseif ($tieneEntrada) {
                $mensaje = 'Puede marcar su salida';
            } else {
                $mensaje = 'Puede marcar su entrada';
            }

            $registroData = null;
            if ($registro) {
                $registroData = [
                    'id_registro' => $registro->id_registro,
                    'fecha_entrada' => $registro->fecha_entrada ? Carbon::parse($registro->fecha_entrada)->format('Y-m-d') : null,
                    'hora_entrada' => $registro->hora_entrada ? Carbon::parse($registro->hora_entrada)->format('H:i') : null,
                    'latitud_entrada' => $registro->latitud_entrada,
                    'longitud_entrada' => $registro->longitud_entrada,
                    'foto_entrada' => $registro->foto_entrada,
                    'foto_entrada_url' => $registro->foto_entrada ? Storage::disk('erp_storage')->url($registro->foto_entrada) : null,
                    'fecha_salida' => $registro->fecha_salida ? Carbon::parse($registro->fecha_salida)->format('Y-m-d') : null,
                    'hora_salida' => $registro->hora_salida ? Carbon::parse($registro->hora_salida)->format('H:i') : null,
                    'latitud_salida' => $registro->latitud_salida,
                    'longitud_salida' => $registro->longitud_salida,
                    'foto_salida' => $registro->foto_salida,
                    'foto_salida_url' => $registro->foto_salida ? Storage::disk('erp_storage')->url($registro->foto_salida) : null,
                    'estado' => $registro->estado
                ];
            }

            return response()->json([
                'success' => true,
                'fecha' => $fechaHoy,
                'tiene_entrada' => $tieneEntrada,
                'tiene_salida' => $tieneSalida,
                'puede_marcar_entrada' => $puedeMarcarEntrada,
                'puede_marcar_salida' => $puedeMarcarSalida,
                'tiene_horario' => $tieneHorario,
                'en_vacaciones' => $enVacaciones,
                'en_licencia' => $enLicencia,
                'registro' => $registroData,
                'mensaje' => $mensaje
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar entrada automática
     * 
     * Body opcional:
     * - dni_ce: DNI del empleado
     * - latitud: Latitud de la ubicación (requerido)
     * - longitud: Longitud de la ubicación (requerido)
     * - foto: Imagen de la entrada (requerido, multipart/form-data)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function marcarEntrada(Request $request)
    {
        try {
            // Validar coordenadas y foto
            $request->validate([
                'latitud' => ['required', 'numeric', 'between:-90,90'],
                'longitud' => ['required', 'numeric', 'between:-180,180'],
                'foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
                'dni_ce' => ['sometimes', 'string', 'max:20'],
            ]);

            // Obtener personal_residencia (propia o de otro si se pasa dni_ce)
            $resultado = $this->obtenerPersonalResidencia($request);

            if ($resultado['error']) {
                $codigoError = str_contains($resultado['error'], 'No se encontró') ? 404 : 403;
                return response()->json([
                    'success' => false,
                    'message' => $resultado['error']
                ], $codigoError);
            }

            $personalResidencia = $resultado['personal_residencia'];
            $personalObjetivo = $resultado['personal'];
            $personalIdParaConsulta = $personalObjetivo ? $personalObjetivo->id_personal : $request->user()->personal_id;
            $user = $request->user();

            // Fecha y hora actual (zona horaria Perú)
            $ahora = Carbon::now()->setTimezone('America/Lima');
            $fechaHoy = $ahora->format('Y-m-d');
            $horaActual = $ahora->format('H:i:s');

            // Verificar que no tenga entrada marcada hoy
            $existente = RegistroAsistencia::where('id_personal_residencia', $personalResidencia->id)
                ->where('fecha_entrada', $fechaHoy)
                ->first();

            if ($existente && $existente->hora_entrada) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tiene entrada marcada para hoy'
                ], 400);
            }

            // Verificar horario para hoy
            $diaSemana = $ahora->format('l');
            $horario = AsignacionRecurrente::where('id_personal_residencia', $personalResidencia->id)
                ->where('activa', true)
                ->where('dias_semana', 'like', "%{$diaSemana}%")
                ->where('fecha_inicio', '<=', $fechaHoy)
                ->where(function($query) use ($fechaHoy) {
                    $query->whereNull('fecha_fin')
                          ->orWhere('fecha_fin', '>=', $fechaHoy);
                })
                ->first();

            if (!$horario) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene horario asignado para hoy'
                ], 400);
            }

            // Verificar vacaciones/licencias
            try {
                $enVacaciones = Vacacion::where('id_personal', $personalIdParaConsulta)
                    ->where('estado', 'Aprobada')
                    ->where('fecha_inicio', '<=', $fechaHoy)
                    ->where('fecha_fin', '>=', $fechaHoy)
                    ->exists();

                if ($enVacaciones) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No puede marcar asistencia: está en vacaciones aprobadas'
                    ], 400);
                }
            } catch (\Exception $e) {
                // Continuar si no existe el modelo
            }

            try {
                $enLicencia = Licencia::where('id_personal', $personalIdParaConsulta)
                    ->where('estado', 'Aprobada')
                    ->where('fecha_inicio', '<=', $fechaHoy)
                    ->where('fecha_fin', '>=', $fechaHoy)
                    ->exists();

                if ($enLicencia) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No puede marcar asistencia: está en licencia aprobada'
                    ], 400);
                }
            } catch (\Exception $e) {
                // Continuar si no existe el modelo
            }

            // Obtener coordenadas de entrada (ya validadas arriba)
            $latitudEntrada = $request->input('latitud');
            $longitudEntrada = $request->input('longitud');

            // Determinar estado (Presente o Tardanza)
            $horaEntradaHorario = Carbon::parse($horario->hora_entrada)->format('H:i');
            $horaActualFormato = $ahora->format('H:i');
            $estado = ($horaActualFormato > $horaEntradaHorario) ? 'Tardanza' : 'Presente';

            // Combinar fecha y hora
            $horaEntradaReal = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $fechaHoy . ' ' . $horaActual
            );

            // Obtener ID temporal para generar el hash (usaremos 0 si no existe registro aún)
            $idRegistroTemporal = $existente ? $existente->id_registro : 0;
            $personalId = $personalObjetivo ? $personalObjetivo->id_personal : $user->personal_id;

            // Guardar foto de entrada
            $foto = $request->file('foto');
            $fotoData = $this->guardarFotoAsistencia($foto, 'entrada', $idRegistroTemporal, $personalId, $fechaHoy);

            DB::beginTransaction();

            try {
                // Usar el procedimiento almacenado si existe, sino crear directamente
                try {
                    DB::statement(
                        'CALL sp_registrar_asistencia(?, ?, ?, ?, ?)',
                        [
                            $personalResidencia->id,
                            $fechaHoy,
                            $horaEntradaReal,
                            'Marcado desde app móvil',
                            $user->id
                    ]
                    );
                    
                    // Si el procedimiento se ejecutó, actualizar coordenadas y estado
                    $registroActualizado = RegistroAsistencia::where('id_personal_residencia', $personalResidencia->id)
                        ->where('fecha_entrada', $fechaHoy)
                        ->first();
                    
                    if ($registroActualizado) {
                        $registroActualizado->latitud_entrada = $latitudEntrada;
                        $registroActualizado->longitud_entrada = $longitudEntrada;
                        $registroActualizado->foto_entrada = $fotoData['ruta_relativa'];
                        $registroActualizado->estado = $estado;
                        $registroActualizado->save();
                    }
                } catch (\Exception $e) {
                    // Si el procedimiento no existe, crear directamente
                    if ($existente) {
                        $existente->hora_entrada = $horaEntradaReal;
                        $existente->latitud_entrada = $latitudEntrada;
                        $existente->longitud_entrada = $longitudEntrada;
                        $existente->foto_entrada = $fotoData['ruta_relativa'];
                        $existente->estado = $estado;
                        $existente->observaciones = 'Marcado desde app móvil';
                        $existente->save();
                    } else {
                        $nuevoRegistro = RegistroAsistencia::create([
                            'id_personal_residencia' => $personalResidencia->id,
                            'fecha_entrada' => $fechaHoy,
                            'hora_entrada' => $horaEntradaReal,
                            'latitud_entrada' => $latitudEntrada,
                            'longitud_entrada' => $longitudEntrada,
                            'foto_entrada' => $fotoData['ruta_relativa'],
                            'estado' => $estado,
                            'observaciones' => 'Marcado desde app móvil',
                            'fecha_creacion' => now(),
                            'usuario_creacion' => $user->id
                        ]);
                        
                        // Si el hash se generó con id_registro = 0, actualizar con el nombre correcto
                        if ($idRegistroTemporal == 0 && $nuevoRegistro->id_registro) {
                            // Regenerar nombre con el ID real
                            $extension = $foto->getClientOriginalExtension();
                            $hash = substr(
                                hash('sha256', $nuevoRegistro->id_registro . time() . random_bytes(16) . $personalId),
                                0,
                                16
                            );
                            $year = date('Y', strtotime($fechaHoy));
                            $month = date('m', strtotime($fechaHoy));
                            $rutaBase = env('ASISTENCIA_FOTOS_PATH', 'asistencia/fotos');
                            $nuevoNombre = 'entrada_' . $hash . '.' . $extension;
                            $nuevaRuta = $rutaBase . '/' . $year . '/' . $month . '/' . $nuevoNombre;
                            
                            // Renombrar archivo
                            $rutaVieja = $fotoData['ruta_relativa'];
                            if (Storage::disk('erp_storage')->exists($rutaVieja)) {
                                Storage::disk('erp_storage')->move($rutaVieja, $nuevaRuta);
                                $nuevoRegistro->foto_entrada = $nuevaRuta;
                                $nuevoRegistro->save();
                                $fotoData['ruta_relativa'] = $nuevaRuta;
                                $fotoData['url_publica'] = Storage::disk('erp_storage')->url($nuevaRuta);
                            }
                        }
                    }
                }

                DB::commit();

                // Obtener el registro creado/actualizado
                $registro = RegistroAsistencia::where('id_personal_residencia', $personalResidencia->id)
                    ->where('fecha_entrada', $fechaHoy)
                    ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Entrada marcada correctamente',
                    'registro' => [
                        'id_registro' => $registro->id_registro,
                        'fecha_entrada' => $registro->fecha_entrada ? Carbon::parse($registro->fecha_entrada)->format('Y-m-d') : null,
                        'hora_entrada' => $registro->hora_entrada ? Carbon::parse($registro->hora_entrada)->format('H:i') : null,
                        'latitud_entrada' => $registro->latitud_entrada,
                        'longitud_entrada' => $registro->longitud_entrada,
                        'foto_entrada' => $registro->foto_entrada,
                        'foto_entrada_url' => $registro->foto_entrada ? Storage::disk('erp_storage')->url($registro->foto_entrada) : null,
                        'estado' => $registro->estado
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar entrada: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar salida automática
     * 
     * Body opcional:
     * - dni_ce: DNI del empleado
     * - latitud: Latitud de la ubicación (requerido)
     * - longitud: Longitud de la ubicación (requerido)
     * - foto: Imagen de la salida (requerido, multipart/form-data)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function marcarSalida(Request $request)
    {
        try {
            // Validar coordenadas y foto
            $request->validate([
                'latitud' => ['required', 'numeric', 'between:-90,90'],
                'longitud' => ['required', 'numeric', 'between:-180,180'],
                'foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
                'dni_ce' => ['sometimes', 'string', 'max:20'],
            ]);

            // Obtener personal_residencia (propia o de otro si se pasa dni_ce)
            $resultado = $this->obtenerPersonalResidencia($request);

            if ($resultado['error']) {
                $codigoError = str_contains($resultado['error'], 'No se encontró') ? 404 : 403;
                return response()->json([
                    'success' => false,
                    'message' => $resultado['error']
                ], $codigoError);
            }

            $personalResidencia = $resultado['personal_residencia'];
            $user = $request->user();

            // Fecha y hora actual (zona horaria Perú)
            $ahora = Carbon::now()->setTimezone('America/Lima');
            $fechaHoy = $ahora->format('Y-m-d');
            $horaActual = $ahora->format('H:i:s');

            // Buscar registro de asistencia de hoy
            $registro = RegistroAsistencia::where('id_personal_residencia', $personalResidencia->id)
                ->where('fecha_entrada', $fechaHoy)
                ->first();

            if (!$registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene entrada marcada para hoy'
                ], 400);
            }

            if (!$registro->hora_entrada) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene hora de entrada registrada'
                ], 400);
            }

            if ($registro->hora_salida) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tiene salida marcada para hoy'
                ], 400);
            }

            // Obtener coordenadas de salida (ya validadas arriba)
            $latitudSalida = $request->input('latitud');
            $longitudSalida = $request->input('longitud');

            // Verificar que la hora de salida sea posterior a la entrada
            $horaEntrada = Carbon::parse($registro->hora_entrada);
            $horaSalida = Carbon::createFromFormat('Y-m-d H:i:s', $fechaHoy . ' ' . $horaActual);

            if ($horaSalida->lte($horaEntrada)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La hora de salida debe ser posterior a la hora de entrada'
                ], 400);
            }

            // Guardar foto de salida
            $foto = $request->file('foto');
            $personalId = $resultado['personal'] ? $resultado['personal']->id_personal : $user->personal_id;
            $fotoData = $this->guardarFotoAsistencia($foto, 'salida', $registro->id_registro, $personalId, $fechaHoy);

            // Actualizar registro
            $registro->fecha_salida = $fechaHoy;
            $registro->hora_salida = $horaSalida;
            $registro->latitud_salida = $latitudSalida;
            $registro->longitud_salida = $longitudSalida;
            $registro->foto_salida = $fotoData['ruta_relativa'];
            $registro->save();

            return response()->json([
                'success' => true,
                'message' => 'Salida marcada correctamente',
                'registro' => [
                    'id_registro' => $registro->id_registro,
                    'fecha_entrada' => $registro->fecha_entrada ? Carbon::parse($registro->fecha_entrada)->format('Y-m-d') : null,
                    'hora_entrada' => $registro->hora_entrada ? Carbon::parse($registro->hora_entrada)->format('H:i') : null,
                    'latitud_entrada' => $registro->latitud_entrada,
                    'longitud_entrada' => $registro->longitud_entrada,
                    'foto_entrada' => $registro->foto_entrada,
                    'foto_entrada_url' => $registro->foto_entrada ? Storage::disk('erp_storage')->url($registro->foto_entrada) : null,
                    'fecha_salida' => $registro->fecha_salida ? Carbon::parse($registro->fecha_salida)->format('Y-m-d') : null,
                    'hora_salida' => $registro->hora_salida ? Carbon::parse($registro->hora_salida)->format('H:i') : null,
                    'latitud_salida' => $registro->latitud_salida,
                    'longitud_salida' => $registro->longitud_salida,
                    'foto_salida' => $registro->foto_salida,
                    'foto_salida_url' => $registro->foto_salida ? Storage::disk('erp_storage')->url($registro->foto_salida) : null,
                    'estado' => $registro->estado
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar salida: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de asistencia
     * 
     * Query params opcionales:
     * - dni_ce: DNI del empleado
     * - limite: Número de días a mostrar (default: 30)
     * - desde: Fecha inicio (formato: Y-m-d)
     * - hasta: Fecha fin (formato: Y-m-d)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function historial(Request $request)
    {
        try {
            // Obtener personal_residencia (propia o de otro si se pasa dni_ce)
            $resultado = $this->obtenerPersonalResidencia($request);

            if ($resultado['error']) {
                $codigoError = str_contains($resultado['error'], 'No se encontró') ? 404 : 403;
                return response()->json([
                    'success' => false,
                    'message' => $resultado['error']
                ], $codigoError);
            }

            $personalResidencia = $resultado['personal_residencia'];

            // Parámetros opcionales
            $limite = $request->input('limite', 30); // Por defecto últimos 30 días
            $desde = $request->input('desde');
            $hasta = $request->input('hasta');

            $query = RegistroAsistencia::where('id_personal_residencia', $personalResidencia->id)
                ->orderBy('fecha_entrada', 'desc');

            if ($desde) {
                $query->where('fecha_entrada', '>=', $desde);
            }

            if ($hasta) {
                $query->where('fecha_entrada', '<=', $hasta);
            }

            if (!$desde && !$hasta) {
                // Si no se especifican fechas, mostrar últimos N días
                $fechaInicio = Carbon::now()->subDays($limite)->format('Y-m-d');
                $query->where('fecha_entrada', '>=', $fechaInicio);
            }

            $registros = $query->limit(100)->get(); // Máximo 100 registros

            $historial = $registros->map(function($registro) {
                return [
                    'id_registro' => $registro->id_registro,
                    'fecha_entrada' => $registro->fecha_entrada ? Carbon::parse($registro->fecha_entrada)->format('Y-m-d') : null,
                    'hora_entrada' => $registro->hora_entrada ? Carbon::parse($registro->hora_entrada)->format('H:i') : null,
                    'latitud_entrada' => $registro->latitud_entrada,
                    'longitud_entrada' => $registro->longitud_entrada,
                    'foto_entrada' => $registro->foto_entrada,
                    'foto_entrada_url' => $registro->foto_entrada ? Storage::disk('erp_storage')->url($registro->foto_entrada) : null,
                    'fecha_salida' => $registro->fecha_salida ? Carbon::parse($registro->fecha_salida)->format('Y-m-d') : null,
                    'hora_salida' => $registro->hora_salida ? Carbon::parse($registro->hora_salida)->format('H:i') : null,
                    'latitud_salida' => $registro->latitud_salida,
                    'longitud_salida' => $registro->longitud_salida,
                    'foto_salida' => $registro->foto_salida,
                    'foto_salida_url' => $registro->foto_salida ? Storage::disk('erp_storage')->url($registro->foto_salida) : null,
                    'estado' => $registro->estado,
                    'observaciones' => $registro->observaciones
                ];
            });

            return response()->json([
                'success' => true,
                'total' => $historial->count(),
                'historial' => $historial
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }
}
