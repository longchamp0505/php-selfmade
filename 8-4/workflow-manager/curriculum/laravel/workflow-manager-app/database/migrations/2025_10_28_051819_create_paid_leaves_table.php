<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paid_leaves', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 50);
            $table->integer('remaining_days')->default(0);
            $table->date('granted_date');
            $table->integer('current_year_taken')->default(0);
            $table->integer('last_year_granted')->default(0);
            $table->integer('last_year_carried')->default(0);
            $table->integer('last_year_taken')->default(0);
            $table->integer('two_years_ago_granted')->default(0);
            $table->integer('two_years_ago_carried')->default(0);
            $table->date('next_expiration_date')->nullable();
            $table->integer('expiration_days')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paid_leaves');
    }
};

