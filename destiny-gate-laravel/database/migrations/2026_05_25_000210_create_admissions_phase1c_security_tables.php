<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_verification_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained('admission_applications')->onDelete('set null');
            $table->unsignedBigInteger('session_id')->nullable()->index();
            $table->enum('verification_type', ['recovery', 'stepup'])->index();
            $table->string('action', 60)->index();
            $table->string('email_to', 320)->nullable()->index();
            $table->string('token_hash', 80)->unique();
            $table->string('token_last4', 10)->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->string('requested_ip_hash', 80)->nullable()->index();
            $table->string('requested_device_hash', 80)->nullable()->index();
            $table->string('correlation_id', 80)->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('admission_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('admission_applications')->onDelete('cascade');
            $table->string('session_hash', 80)->unique();
            $table->string('session_last4', 10)->index();
            $table->string('ip_hash', 80)->nullable()->index();
            $table->string('device_hash', 80)->nullable()->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('stepup_until')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('admission_security_counters', function (Blueprint $table) {
            $table->id();
            $table->string('counter_type', 80)->index();
            $table->string('key_hash', 80)->index();
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('locked_until')->nullable()->index();
            $table->timestamps();
            $table->unique(['counter_type', 'key_hash'], 'asc_type_key_unique');
        });

        Schema::table('admission_audit_events', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_audit_events', 'correlation_id')) {
                $table->string('correlation_id', 80)->nullable()->after('severity')->index();
            }
            if (!Schema::hasColumn('admission_audit_events', 'ip_hash')) {
                $table->string('ip_hash', 80)->nullable()->after('correlation_id')->index();
            }
            if (!Schema::hasColumn('admission_audit_events', 'device_hash')) {
                $table->string('device_hash', 80)->nullable()->after('ip_hash')->index();
            }
        });

        Schema::table('admission_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_applications', 'archived_reason')) {
                $table->string('archived_reason', 40)->nullable()->after('archived_at')->index();
            }
            if (!Schema::hasColumn('admission_applications', 'expiry_warning_14_sent_at')) {
                $table->timestamp('expiry_warning_14_sent_at')->nullable()->after('archived_reason');
            }
            if (!Schema::hasColumn('admission_applications', 'expiry_warning_3_sent_at')) {
                $table->timestamp('expiry_warning_3_sent_at')->nullable()->after('expiry_warning_14_sent_at');
            }
        });

        Schema::table('admission_application_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_application_tokens', 'created_ip_hash')) {
                $table->string('created_ip_hash', 80)->nullable()->after('created_ip')->index();
            }
            if (!Schema::hasColumn('admission_application_tokens', 'created_device_hash')) {
                $table->string('created_device_hash', 80)->nullable()->after('created_ip_hash')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_application_tokens', function (Blueprint $table) {
            foreach (['created_ip_hash', 'created_device_hash'] as $col) {
                if (Schema::hasColumn('admission_application_tokens', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('admission_applications', function (Blueprint $table) {
            foreach (['archived_reason', 'expiry_warning_14_sent_at', 'expiry_warning_3_sent_at'] as $col) {
                if (Schema::hasColumn('admission_applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('admission_audit_events', function (Blueprint $table) {
            foreach (['correlation_id', 'ip_hash', 'device_hash'] as $col) {
                if (Schema::hasColumn('admission_audit_events', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('admission_security_counters');
        Schema::dropIfExists('admission_verification_challenges');
        Schema::dropIfExists('admission_sessions');
    }
};
