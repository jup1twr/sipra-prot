<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('clientes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('empresa_id')->constrained('empresas');
        $table->string('nome');
        $table->string('tipo', 2)->default('PF');
        $table->string('documento')->nullable();
        $table->string('telefone')->nullable();
        $table->string('email')->nullable();
        $table->string('cidade')->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->index(['empresa_id', 'nome']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
