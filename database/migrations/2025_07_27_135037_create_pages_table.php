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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('url')->index();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->nullable(); // page, product, collection, blog, etc.
            $table->json('lighthouse')->nullable(); // lighthouse scores and metrics
            $table->json('seo')->nullable(); // SEO analysis data
            $table->json('meta_data')->nullable(); // additional metadata
            $table->boolean('is_analyzed')->default(false);
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['website_id', 'url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
