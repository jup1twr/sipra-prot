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
    Schema::create('itens_levantamento', function (Blueprint $table) {
        $table->id();
        $table->foreignId('empresa_id')->constrained('empresas');
        $table->foreignId('ambiente_id')->constrained('ambientes');
        $table->foreignId('insumo_id')->constrained('insumos');
        $table->decimal('comprimento', 10, 3)->nullable();
        $table->decimal('largura', 10, 3)->nullable();
        $table->decimal('altura', 10, 3)->nullable();
        $table->decimal('quantidade', 10, 3)->nullable();
        $table->string('observacao')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

public function down(): void
{
    Schema::dropIfExists('itens_levantamento');
}

};
