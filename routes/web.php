<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/login/keycloak', [LoginController::class, 'redirectToKeycloak'])->name('login.keycloak');
Route::get('/callback', [LoginController::class, 'handleKeycloakCallback'])->name('keycloak.callback');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
