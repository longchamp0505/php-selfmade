<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 50)->primary(); // ログインID兼ユーザーID
            $table->string('password', 255);
            $table->string('name', 100);
            $table->string('name_kana', 100);
            $table->date('hire_date');
            $table->date('retire_date')->nullable();
            $table->string('employment_type', 50);
            $table->string('contract_type', 50);
            $table->string('workplace', 100);
            $table->integer('scheduled_days');
            $table->string('client_id', 50)->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
