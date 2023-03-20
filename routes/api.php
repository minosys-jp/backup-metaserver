<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

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

Route::post('/file_fp', [ApiController::class, 'file_fp']);
Route::post('/move_file', [ApiController::class, 'move_file']);
Route::post('/remove', [ApiController::class, 'remove_file']);
Route::post('/upload_slice', [ApiController::class, 'upload_slice']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
