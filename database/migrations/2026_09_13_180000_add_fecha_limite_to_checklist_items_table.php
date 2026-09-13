<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = "usuarios";

    public function up(): void
    {
        Schema::table("checklist_items", function (Blueprint $table) {
            $table->date("fecha_limite")->nullable()->after("dueno_id");
        });
    }

    public function down(): void
    {
        Schema::table("checklist_items", function (Blueprint $table) {
            $table->dropColumn("fecha_limite");
        });
    }
};
