<?php

use App\Http\Controllers\Api\V1\AdminContentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\PublicContentController;
use App\Http\Controllers\Api\V1\ResumeFileController;
use App\Http\Middleware\RequireAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:120,1')->group(function (): void {
    Route::get('health', HealthController::class);
    Route::post('contact', ContactController::class)->middleware('throttle:5,1');
    Route::prefix('admin')->middleware('web')->group(function (): void {
        Route::get('csrf', [AuthController::class, 'csrf']);
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:admin-login');
        Route::middleware(RequireAdmin::class)->group(function (): void {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('dashboard', DashboardController::class);
            Route::get('schema', [AdminContentController::class, 'schema']);
            Route::get('media', [MediaController::class, 'index']);
            Route::post('media', [MediaController::class, 'store']);
            Route::patch('media/{media}', [MediaController::class, 'update'])->whereNumber('media');
            Route::delete('media/{media}', [MediaController::class, 'destroy'])->whereNumber('media');
            Route::get('{resource}', [AdminContentController::class, 'index']);
            Route::post('{resource}', [AdminContentController::class, 'store']);
            Route::get('{resource}/{id}', [AdminContentController::class, 'show'])->whereNumber('id');
            Route::match(['put', 'patch'], '{resource}/{id}', [AdminContentController::class, 'update'])->whereNumber('id');
            Route::delete('{resource}/{id}', [AdminContentController::class, 'destroy'])->whereNumber('id');
        });
    });
    Route::get('projects/featured', [PublicContentController::class, 'featured']);
    Route::get('resumes/{resume}/file', [ResumeFileController::class, 'preview'])->whereNumber('resume');
    Route::get('resumes/{resume}/download', [ResumeFileController::class, 'download'])->whereNumber('resume');
    Route::get('{resource}', [PublicContentController::class, 'index'])->where('resource', 'projects|project-categories|technologies|skills|skill-categories|experience|education|certifications|engineering|blog|blog-categories|blog-tags|site-settings|social-links|profile|resumes');
    Route::get('{resource}/{slug}', [PublicContentController::class, 'show'])->where('resource', 'projects|blog|engineering');
});
