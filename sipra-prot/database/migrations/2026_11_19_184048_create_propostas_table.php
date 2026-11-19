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
        Schema::create('propostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('projeto_id')->constrained('projetos');
            $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete();
            $table->unsignedSmallInteger('ano');
            $table->unsignedInteger('sequencia');
            $table->string('numero', 20);
            $table->string('status')->default('rascunho');
            $table->date('validade_ate');
            $table->decimal('valor_total', 12, 2);
            $table->json('itens');
            $table->text('condicoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'ano', 'sequencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propostas');
    }
};
