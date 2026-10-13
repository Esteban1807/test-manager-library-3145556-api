<?php

use App\Adapters\Http\Controllers\AuthController;
use App\Adapters\Http\Controllers\BookController;
use App\Adapters\Http\Controllers\CategoryController;
use App\Adapters\Http\Controllers\LoanController;
use App\Adapters\Http\Controllers\MemberController;
use App\Adapters\Http\Controllers\ReportController;
use App\Adapters\Http\Controllers\UserController;
use App\Adapters\Http\HealthController;
use Illuminate\Support\Facades\Route;

// Public routes: no token (api-contract §1, §3.1, D-API-03)
Route::get('/health', HealthController::class);
Route::post('/auth/login', [AuthController::class, 'login']);

// Everything else needs a valid Bearer token (api-contract §4)
Route::middleware('auth.token')->group(function () {
    // Users: admin only (CA-02.1)
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin');

    Route::get('/categories', [CategoryController::class, 'index']);

    // Books: reads for any signed-in role, writes for admin (HU-03, HU-04)
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{id}', [BookController::class, 'show']);
    Route::get('/books/{id}/cover', [BookController::class, 'showCover']);
    Route::post('/books', [BookController::class, 'store'])->middleware('role:admin');
    Route::put('/books/{id}', [BookController::class, 'update'])->middleware('role:admin');
    Route::delete('/books/{id}', [BookController::class, 'destroy'])->middleware('role:admin');
    Route::put('/books/{id}/cover', [BookController::class, 'uploadCover'])->middleware('role:admin');
    Route::delete('/books/{id}/cover', [BookController::class, 'removeCover'])->middleware('role:admin');

    // Members: admin and librarian (HU-05)
    Route::get('/members', [MemberController::class, 'index'])->middleware('role:admin,librarian');
    Route::get('/members/{id}', [MemberController::class, 'show'])->middleware('role:admin,librarian');
    Route::post('/members', [MemberController::class, 'store'])->middleware('role:admin,librarian');

    // Loans: admin and librarian (HU-06 to HU-09). The literal /overdue route goes before /{id}.
    Route::get('/loans/overdue', [LoanController::class, 'overdue'])->middleware('role:admin,librarian');
    Route::post('/loans', [LoanController::class, 'store'])->middleware('role:admin,librarian');
    Route::get('/loans', [LoanController::class, 'index'])->middleware('role:admin,librarian');
    Route::get('/loans/{id}', [LoanController::class, 'show'])->middleware('role:admin,librarian');
    Route::post('/loans/{loanId}/items/{itemId}/return', [LoanController::class, 'returnItem'])->middleware('role:admin,librarian');

    // Report: admin only (CA-10.5)
    Route::get('/reports/most-borrowed', [ReportController::class, 'mostBorrowed'])->middleware('role:admin');
});
