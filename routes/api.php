<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SanctumController;
use OpenApi\Generator;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::apiResource('/users', UserController::class);
Route::middleware('auth:sanctum')->apiResource('/users', UserController::class);

Route::post('/auth', [SanctumController::class, 'create']);

Route::get('/swagger.json', function () {
    return Generator::scan([ app_path(), ])->toJson();
});
