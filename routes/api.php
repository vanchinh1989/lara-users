<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
*/

use Illuminate\Support\Facades\Route;
use vanchinh1989\larausers\App\Http\Controllers\UsersController;

Route::group([
    'prefix' => 'user'
], function ($router) {
    Route::get('/', [UsersController::class, "index"]);
    Route::post('/create', [UsersController::class, "store"]);
    Route::get('/detail/{id}', [UsersController::class, "show"]);
    Route::post('/update/{id}', [UsersController::class, "update"]);
    Route::delete('/delete/{id}', [UsersController::class, "destroy"]);
});