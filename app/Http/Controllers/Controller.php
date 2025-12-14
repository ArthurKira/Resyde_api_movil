<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="API Resyde",
 *     version="1.0.0",
 *     description="Backend API para aplicación móvil Resyde - Sistema de gestión de residencias y recibos",
 *     @OA\Contact(
 *         email="support@resyde.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor de la API"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Autenticación mediante Laravel Sanctum. Incluye el token en el header: Authorization: Bearer {token}"
 * )
 */
abstract class Controller
{
    //
}

