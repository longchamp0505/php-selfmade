<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id(); // 自動採番 BIGINT
            $table->string('user_id', 50);
            $table->date('date');
            $table->string('day_of_week', 10);
            $table->string('category', 20);
            $table->boolean('is_other_company_work')->default(0);
            $table->time('clock_in')->nullable();
            $table->time('start_time')->nullable();
            $table->time('clock_out')->nullable();
            $table->time('end_time')->nullable();
            $table->time('break_time')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->boolean('is_submitted')->default(0);
            $table->boolean('is_approved_by_clients')->default(0);
            $table->dateTime('clients_approved_at')->nullable();
            $table->boolean('is_approved_by_admins')->default(0);
            $table->dateTime('admins_approved_at')->nullable();
            $table->boolean('rejection')->default(0);
            $table->string('rejection_comment', 255)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
