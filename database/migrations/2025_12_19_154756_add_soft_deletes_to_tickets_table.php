<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Menambahkan kolom deleted_at
            $table->softDeletes(); 
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Menghapus kolom deleted_at jika migration di-rollback
            $table->dropSoftDeletes();
        });
    }
};