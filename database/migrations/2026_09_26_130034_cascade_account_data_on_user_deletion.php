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
        $this->replaceUserForeignKey('tickets', true);
        $this->replaceUserForeignKey('ticket_comments', true);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->replaceUserForeignKey('tickets', false);
        $this->replaceUserForeignKey('ticket_comments', false);
    }

    private function replaceUserForeignKey(string $tableName, bool $cascadeOnDelete): void
    {
        $foreignKey = collect(Schema::getForeignKeys($tableName))
            ->first(fn (array $foreignKey): bool => $foreignKey['columns'] === ['user_id'] && $foreignKey['foreign_table'] === 'users');

        if ($foreignKey !== null) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignKey): void {
                $table->dropForeign($foreignKey['name'] ?? ['user_id']);
            });
        }

        Schema::table($tableName, function (Blueprint $table) use ($cascadeOnDelete): void {
            $foreignKey = $table->foreign('user_id')->references('id')->on('users');

            if ($cascadeOnDelete) {
                $foreignKey->cascadeOnDelete();
            } else {
                $foreignKey->nullOnDelete();
            }
        });
    }
};
