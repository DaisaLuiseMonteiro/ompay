<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $migrations = [
            '2025_11_13_062133_add_otp_fields_to_clients_table',
            '2025_11_13_062621_add_otp_fields_to_clients_table',
            '2025_11_13_064000_add_otp_type_to_clients_table'
        ];

        foreach ($migrations as $migration) {
            if (!DB::table('migrations')->where('migration', $migration)->exists()) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => DB::table('migrations')->max('batch') + 1
                ]);
            }
        }
    }

    public function down(): void
    {
        // Ne rien faire en cas de rollback
    }
};
