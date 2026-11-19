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
        Schema::table('projetos', function (Blueprint $table) {
            $table->string('tipologia', 30)->nullable()->after('status');
            $table->index(['empresa_id', 'tipologia']);
        });
    }

    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'tipologia']);
            $table->dropColumn('tipologia');
        });
    }
};
