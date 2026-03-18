<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();

            // Relasi ke User (Siapa yang punya catatan ini)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Data Hutang/Piutang
            $table->string('name'); // Nama orang/instansi (Misal: "Budi", "Pinjol X")
            $table->decimal('amount', 15, 2); // Nominal hutang
            $table->enum('type', ['payable', 'receivable']); // Hutang atau Piutang
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid'); // Status pelunasan
            $table->date('due_date')->nullable(); // Tanggal jatuh tempo (boleh kosong)
            $table->text('description')->nullable(); // Keterangan tambahan

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
