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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->year('year');
            $table->string('type');

            $table->decimal('allowance', 5, 1)->default(0);
            $table->decimal('used', 5, 1)->default(0);

            $table->decimal('remaining', 5, 1)->virtualAs('allowance - used');

            $table->timestamps();
            $table->unique(['user_id', 'year', 'type']);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
