<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_syllabi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('content_type', ['file', 'editor', 'both'])->default('file');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->text('content')->nullable();
            $table->json('objectives')->nullable();
            $table->json('topics')->nullable();
            $table->json('evaluation_criteria')->nullable();
            $table->json('resources')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_assignment_id', 'is_published']);
            $table->index('term_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_syllabi');
    }
};
