<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiver_id')->constrained('users');
            $table->decimal('transfer_amount', 18, 2);
            $table->string('account_from');
            $table->string('account_to');
            $table->enum('transfer_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->dateTime('transfer_timestamp')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
