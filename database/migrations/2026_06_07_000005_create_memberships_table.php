<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('neighbourhood_id')->constrained()->restrictOnDelete();
            // Nullable: a pending "request a new household" has no household yet.
            $table->foreignId('household_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('role');
            $table->string('requested_household_name')->nullable();
            $table->timestamps();

            // One membership per user per neighbourhood.
            $table->unique(['user_id', 'neighbourhood_id']);
        });

        // Exactly one Founder per neighbourhood (no FK can express this).
        DB::statement(
            "CREATE UNIQUE INDEX memberships_one_founder_per_neighbourhood ON memberships (neighbourhood_id) WHERE role = 'founder'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
