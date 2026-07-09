<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChirperController;

Route::get('/', [ChirperController::class, 'index']);
