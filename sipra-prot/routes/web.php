<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProjetoController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // "Meus Projetos" é a tela inicial depois do login
    Route::get('dashboard', [ProjetoController::class, 'index'])->name('dashboard');
    Route::resource('projetos', ProjetoController::class)->except('index');
    Route::get('projetos/{projeto}/levantamento', [ProjetoController::class, 'levantamento'])->name('projetos.levantamento');
    Route::get('projetos/{projeto}/orcamento', [ProjetoController::class, 'orcamento'])->name('projetos.orcamento');
    Route::get('projetos/{projeto}/historico', [ProjetoController::class, 'historico'])->name('projetos.historico');

    Route::resource('clientes', ClienteController::class)->except('show');
});

require __DIR__.'/settings.php';