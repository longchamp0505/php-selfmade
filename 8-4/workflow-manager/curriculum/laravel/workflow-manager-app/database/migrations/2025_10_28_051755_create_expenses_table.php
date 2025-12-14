<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 50);
            $table->date('date');
            $table->string('day_of_week', 10);
            $table->string('category', 50);
            $table->integer('amount')->default(0);
            $table->string('payee', 100);
            $table->string('purpose', 255)->nullable();
            $table->string('receipt_image', 255)->nullable();
            $table->string('invoice_number', 50)->nullable();
            $table->boolean('is_submitted')->default(0);
            $table->boolean('is_approved_by_admins')->default(0);
            $table->dateTime('admins_approved_at')->nullable();
            $table->boolean('is_rejection')->default(0);
            $table->string('rejection_comment', 255)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
