<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="CampSite API Documentation",
 *     version="1.0.0",
 *     description="Kemping foglalási és beléptetőrendszer API dokumentáció",
 *     @OA\Contact(
 *         email="support@campsite.hu"
 *     )
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local Development Server"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Production Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum authentication token"
 * )
 */
abstract class Controller
{
    //
}
