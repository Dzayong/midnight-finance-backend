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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_account_id')->constrained('financial_accounts')->onDelete('cascade');
            $table->foreignId('to_account_id')->constrained('financial_accounts')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }
};
