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
        Schema::create('log_transaction_status', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->string('transfers_id')->nullable();
            $table->string('status');
            $table->text('message');
            $table->dateTime('transfer_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_transaction_status');
    }
};
