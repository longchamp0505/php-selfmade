<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->string('id', 50)->primary(); // クライアントID
            $table->string('password', 255);
            $table->string('company_name', 100);
            $table->string('department_name', 100)->nullable();
            $table->string('contact', 50)->nullable();
            $table->string('approver1_name', 100)->nullable();
            $table->string('approver1_email', 100)->nullable();
            $table->string('approver2_name', 100)->nullable();
            $table->string('approver2_email', 100)->nullable();
            $table->string('approver3_name', 100)->nullable();
            $table->string('approver3_email', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
