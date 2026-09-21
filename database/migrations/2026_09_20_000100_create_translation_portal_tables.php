<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso2', 2)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('locale_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('full_name')->nullable()->after('locale_id');
        });

        Schema::create('registration_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('translation_sources', function (Blueprint $table) {
            $table->id();
            $table->text('msgid');
            $table->text('context')->nullable();
            $table->string('source_hash', 64)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('text')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->unique(['translation_source_id', 'locale_id']);
        });

        Schema::create('translation_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('old_text')->nullable();
            $table->longText('new_text')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_revisions');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('translation_sources');
        Schema::dropIfExists('registration_tokens');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locale_id');
            $table->dropColumn('full_name');
        });
        Schema::dropIfExists('locales');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('countries');
    }
};
