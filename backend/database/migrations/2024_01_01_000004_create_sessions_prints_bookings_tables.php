<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cyber_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pc_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('total_minutes', 10, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled', 'paused'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['pc_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('print_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->enum('type', ['black_white', 'color', 'photo', 'large_format'])->default('black_white');
            $table->integer('pages')->default(1);
            $table->integer('copies')->default(1);
            $table->boolean('double_sided')->default(false);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'printing', 'ready', 'delivered', 'cancelled', 'refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pc_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'paid', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('print_orders');
        Schema::dropIfExists('cyber_sessions');
    }
};
