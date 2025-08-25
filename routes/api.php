<?php

use App\Http\Controllers\Api\AttendanceSettingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientInfoController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeDocumentController;
use App\Http\Controllers\Api\EmployeeDocumentPivotController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\IncidentTypeController;
use App\Http\Controllers\Api\OccurrenceCategoryController;
use App\Http\Controllers\Api\OccurrenceController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\SiteUserController;
use App\Http\Controllers\Api\SOPDocumentController;
use App\Http\Controllers\Api\AuditTrailsController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\UserLanguageController;
use App\Models\AttendanceSetting;
use App\Models\SOPDocument;
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

    Route::resources([
        'employees' => EmployeeController::class,
        'sites' => SiteController::class,
        'routes' => RouteController::class,
        'occcurrences' => OccurrenceController::class,
        'audit-trails' => AuditTrailsController::class,
        'languages' => LanguageController::class,
        'employee-document' => EmployeeDocumentPivotController::class,
        'incidents' => IncidentController::class
    ]);

    Route::prefix('master-settings')->name('master-settings.')->group(function () {
        Route::resources([
            'roles' => RoleController::class,
            'permissions' => PermissionController::class,
            'attendance-settings' => AttendanceSettingController::class,
            'occurrence-categories' => OccurrenceCategoryController::class,
            'client-info' => ClientInfoController::class,
            'employee-documents' => EmployeeDocumentController::class,
            'incident-types' => IncidentTypeController::class,
            'sop-documents' => SOPDocumentController::class,
        ]);
    });

    Route::controller(EmployeeController::class)->name('employees.')->prefix('employees')->group(function () {
        Route::put('/{id}/status', 'updateStatus')->name('updateStatus');
        Route::put('/profile/{id}', 'updateProfile')->name('updateProfile');
    });

    Route::controller(AuthController::class)->name('auth.')->prefix('auth')->group(function () {
        Route::post('/login', 'login')->name('login')->withoutMiddleware('auth:sanctum');
        Route::post('/update-profile/{id}', 'updateProfile')->name('updateProfile');
        Route::post('/user/login', 'loginUser')->name('user.login')->withoutMiddleware('auth:sanctum');
    });

    Route::controller(SiteController::class)->name('sites.')->prefix('sites')->group(function () {
        Route::post('/disallocation', 'disallocation')->name('disallocation');
    });

    Route::controller(SiteUserController::class)->name('site-user.')->prefix('site-user')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/disallocation', 'disallocation')->name('disallocation');
        Route::put('/{id}', 'update')->name('update');
        Route::put('/allocation/{id}', 'allocation')->name('allocation');

        Route::get('/user/nearest/{id}', 'nearest')->name('nearest')->withoutMiddleware('auth:sanctum');
        Route::get('/user/data/{id}', 'data')->name('data')->withoutMiddleware('auth:sanctum');
    });

    Route::controller(AttendanceController::class)->name('attendances.')->prefix('attendances')->group(function () {
        Route::get('/site-user/{id}', 'getAttendance')->name('get-by-site-employee');
        Route::post('/', 'store')->name('store');
        Route::put('/{id}', 'update')->name('update');
    });

    Route::controller(LanguageController::class)->name('language.')->prefix('language')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/{id}', 'update')->name('update');
    });
});
