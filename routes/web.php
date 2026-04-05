<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaylistController;

// Route::get('/', function () {
//     return view('welcome');
// });


Route::get('/', [PlaylistController::class, 'index'])->name('playlists.index');
Route::post('/fetch', [PlaylistController::class, 'fetch'])->name('playlists.fetch');
