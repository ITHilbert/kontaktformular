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
        Schema::create('kontaktformular', function (Blueprint $table) {
            $table->id();
            $table->integer('jahr');
            $table->integer('nummer');
            $table->string('url');
            $table->string('name');
            $table->string('email');
            $table->string('telefon');
            $table->longText('nachricht');
            $table->boolean('datenverarbeitung')->default(false);
            $table->string('filename')->nullable();
            $table->string('filetype')->nullable();
            $table->string('filehash')->nullable();
            $table->string('filesize')->nullable();
            $table->date('filedownload_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontaktformular');
    }
};
