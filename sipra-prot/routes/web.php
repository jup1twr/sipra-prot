<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProjetoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnexoController;
use App\Http\Controllers\AmbienteController;
use App\Http\Controllers\LevantamentoController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // "Meus Projetos" é a tela inicial depois do login
    Route::get('dashboard', [ProjetoController::class, 'index'])->name('dashboard');
    Route::resource('projetos', ProjetoController::class)->except('index');
    Route::get('projetos/{projeto}/levantamento', [LevantamentoController::class, 'index'])->name('projetos.levantamento');
    Route::post('projetos/{projeto}/ambientes', [AmbienteController::class, 'store'])->name('projetos.ambientes.store');
    Route::delete('ambientes/{ambiente}', [AmbienteController::class, 'destroy'])->name('ambientes.destroy');
    Route::put('ambientes/{ambiente}/itens', [LevantamentoController::class, 'salvar'])->name('ambientes.itens.salvar');
    Route::get('projetos/{projeto}/orcamento', [ProjetoController::class, 'orcamento'])->name('projetos.orcamento');
    Route::get('projetos/{projeto}/historico', [ProjetoController::class, 'historico'])->name('projetos.historico');
    Route::post('projetos/{projeto}/anexos', [AnexoController::class, 'store'])->name('projetos.anexos.store');
    Route::get('anexos/{anexo}', [AnexoController::class, 'show'])->name('anexos.show');
    Route::delete('anexos/{anexo}', [AnexoController::class, 'destroy'])->name('anexos.destroy');

    Route::resource('clientes', ClienteController::class)->except('show');
});

require __DIR__.'/settings.php';