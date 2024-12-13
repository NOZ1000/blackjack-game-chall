<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;


Route::prefix('game')->middleware('throttle:256,1')->group(function () {
    Route::post('/', [GameController::class, 'createGame']);
    Route::post('{uuid}/bet', [GameController::class, 'placeBet']);
    Route::post('{uuid}/hit', [GameController::class, 'hit']);
    Route::post('{uuid}/stand', [GameController::class, 'stand']);
    Route::post('{uuid}/surrender', [GameController::class, 'surrender']);
    Route::get('{uuid}/status', [GameController::class, 'getGameStatus']);
    Route::get('{uuid}/export', [GameController::class, 'exportGameSession']);
    Route::post('restore', [GameController::class, 'decryptAndRestoreSession']);
    Route::get('{uuid}/flag', [GameController::class, 'getFlag']);
});
