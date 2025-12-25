<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_descriptors', function (Blueprint $table) {
            $table->id();
            $table->string('center_code')->index();
            $table->json('descriptor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_descriptors');
    }
};
