<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('cac_number', 50)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account', 30)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended', 'banned'])->default('pending');
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('identifier', 50)->unique();
            $table->text('specs')->nullable();
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->enum('status', ['idle', 'in_use', 'maintenance', 'offline'])->default('idle');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('pcs');
        Schema::dropIfExists('vendors');
    }
};
