<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plant_identifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->string('path');
            $table->string('url');
            $table->string('filename');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('organ');
            $table->string('scientific_name');
            $table->string('scientific_name_without_author');
            $table->string('common_name')->nullable();
            $table->string('family');
            $table->string('genus');
            $table->decimal('confidence', 5, 2);
            $table->string('gbif_id')->nullable();
            $table->string('powo_id')->nullable();
            $table->string('iucn_category')->nullable();
            $table->string('region')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_identifications');
    }
};
