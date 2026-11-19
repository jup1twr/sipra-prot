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
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('projeto_id')->constrained('projetos');
            $table->decimal('perdas_percentual', 5, 2)->default(5);
            $table->decimal('bdi_percentual', 5, 2)->default(20);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('projeto_id');
        });

        Schema::create('orcamento_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('orcamento_id')->constrained('orcamentos');
            $table->foreignId('insumo_id')->nullable()->constrained('insumos')->nullOnDelete();
            $table->string('descricao');
            $table->string('unidade', 10);
            $table->decimal('quantidade', 12, 3)->default(0);
            $table->decimal('preco_material', 12, 2)->default(0);
            $table->decimal('preco_mao_obra', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_itens');
        Schema::dropIfExists('orcamentos');
    }
};
