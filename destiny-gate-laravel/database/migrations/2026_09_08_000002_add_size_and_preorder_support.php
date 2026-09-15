<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fee & Uniform Content Specification §3/§4 list "Size/Option" as part of each uniform
 * catalog row. Free-text rather than a fixed enum — sizes vary wildly by item type
 * (numeric blazer sizes, S/M/L for a jersey, "Standard" for a tie/badge) and the school
 * manages its own size chart; a rigid list would fight that instead of supporting it.
 *
 * Preorder support: a purchase can now be recorded against an item with insufficient (or
 * zero) stock when explicitly flagged is_preorder — stock isn't touched at that point.
 * fulfilled_at is set later, once stock actually arrives and someone pulls it for this
 * order (see StudentPurchaseController::fulfillPreorder()) — a non-preorder purchase gets
 * fulfilled_at stamped immediately, since its stock was already pulled at creation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->string('size', 50)->nullable()->after('item_name');
        });

        Schema::table('student_purchases', function (Blueprint $table) {
            $table->boolean('is_preorder')->default(false)->after('collection_notes');
            $table->timestamp('fulfilled_at')->nullable()->after('is_preorder');
        });

        // Every purchase that already exists had its stock pulled at creation time (the
        // only path that existed before preorders) — backfill so the collection gate below
        // doesn't retroactively block anything already in flight.
        DB::table('student_purchases')->whereNull('fulfilled_at')->update(['fulfilled_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('student_purchases', function (Blueprint $table) {
            $table->dropColumn(['is_preorder', 'fulfilled_at']);
        });
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
