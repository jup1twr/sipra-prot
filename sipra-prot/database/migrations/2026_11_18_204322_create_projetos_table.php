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
    Schema::create('projetos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('empresa_id')->constrained('empresas');
        $table->foreignId('cliente_id')->constrained('clientes');
        $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
        $table->string('nome');
        $table->string('status')->default('em_levantamento');
        $table->date('prazo')->nullable();
        $table->text('briefing')->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->index(['empresa_id', 'status']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projetos');
    }
};
