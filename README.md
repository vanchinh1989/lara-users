## Laravel User Profile
This packages used for model User

# How to install package?
```
composer require vanchinh1989/lara-users
```

# Copy above code to routes/api.php
```
use vanchinh1989\larausers\App\Http\Controllers\UsersController;

Route::group([
    'prefix' => 'user'
], function ($router) {
    Route::get('', [UsersController::class, "index"]);
    Route::post('', [UsersController::class, "store"]);
    Route::get('{id}', [UsersController::class, "show"]);
    Route::post('{id}', [UsersController::class, "update"]);
    Route::delete('{id}', [UsersController::class, "destroy"]);
});
```