<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circulars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('audience', ['all', 'teachers', 'students', 'guardians', 'staff'])->default('all');
            $table->foreignId('academic_period_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('send_email')->default(false);
            $table->boolean('send_push')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audience', 'sent_at']);
            $table->index('scheduled_at');
        });

        Schema::create('circular_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circular_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('email_sent')->default(false);
            $table->boolean('push_sent')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['circular_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circular_recipients');
        Schema::dropIfExists('circulars');
    }
};
