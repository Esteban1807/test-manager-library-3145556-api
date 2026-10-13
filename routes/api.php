<?php

use App\Adapters\Http\HealthController;
use Illuminate\Support\Facades\Route;

// Public route: no token required (api-contract.md §3.1, D-API-03)
Route::get('/health', HealthController::class);
