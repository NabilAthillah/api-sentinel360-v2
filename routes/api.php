<?php

use App\Http\Controllers\Api\AttendanceSettingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientInfoController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OccurrenceCategoryController;
use App\Http\Controllers\Api\OccurrenceController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\SiteUserController;
use App\Models\AttendanceSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route::middleware([EnsureFrontendRequestsAreStateful::class, 'throtle:api', SubstituteBindings::class])->group(function () {
// });
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/check-token', function (Request $request) {
        $user = $request->user()->load('role', 'role.permissions');

        return response()->json([
            'success' => true,
            'message' => 'Token is valid',
            'user' => $user
        ]);
    });


    Route::controller(EmployeeController::class)->name('employees.')->prefix('employees')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/delete/{id}', 'destroy')->name('destroy');
    });

    Route::controller(RoleController::class)->name('roles.')->prefix('roles')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{id}', 'update')->name('update');
    });

    Route::controller(PermissionController::class)->name('permissions.')->prefix('permissions')->group(function () {
        Route::get('/', 'index')->name('index');
    });

    Route::controller(AuthController::class)->name('auth.')->prefix('auth')->group(function () {
        Route::post('/login', 'login')->name('login')->withoutMiddleware('auth:sanctum');
        Route::post('/update-profile', 'updateProfile')->name('updateProfile');
    });

    Route::controller(AttendanceSettingController::class)->name('attendance-settings.')->prefix('attendance-settings')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'update')->name('update');
    });

    Route::controller(SiteController::class)->name('sites.')->prefix('sites')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::put('/{id}', 'update')->name('update');
        Route::post('/disallocation', 'disallocation')->name('disallocation');
        Route::post('/{id}', 'destroy')->name('destroy');
    });

    Route::controller(RouteController::class)->name('routes.')->prefix('routes')->group(function () {
        Route::post('/', 'store')->name('store');
        Route::put('/{id}', 'update')->name('update');
        Route::post('/{id}', 'destroy')->name('destroy');
    });

    Route::controller(OccurrenceCategoryController::class)->name('occurrence-categories.')->prefix('occurrence-categories')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{id}', 'update')->name('update');
    });

    Route::controller(OccurrenceController::class)->name('occurrences.')->prefix('occurrences')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{id}', 'update')->name('update');
    });

    Route::controller(ClientInfoController::class)->name('client-info.')->prefix('client-info')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/{id}', 'update')->name('update');
    });

    Route::controller(SiteUserController::class)->name('site-user.')->prefix('site-user')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/disallocation', 'disallocation')->name('disallocation');
        Route::put('/{id}', 'update')->name('update');
        Route::put('/allocation/{id}', 'allocation')->name('allocation');
    });
});