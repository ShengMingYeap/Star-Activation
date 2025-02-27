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
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('transfers_id')->after('id');
            $table->unsignedBigInteger('sender_id')->after('transfers_id');
            $table->foreign('sender_id')->references('id')->on('users');
            if (Schema::hasColumn('transfers', 'account_from')) {
                $table->dropColumn('account_from');
            }
            if (Schema::hasColumn('transfers', 'account_to')) {
                $table->dropColumn('account_to');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn('transfers_id');
            $table->dropColumn('sender_id');
            if (Schema::hasColumn('transfers', 'account_from')) {
                $table->dropColumn('account_from');
            }
            if (Schema::hasColumn('transfers', 'account_to')) {
                $table->dropColumn('account_to');
            }
        });
    }
};
