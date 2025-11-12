<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowTableStructure extends Command
{
    protected $signature = 'table:structure {table} {--connection=mysql}';
    protected $description = 'Show the structure of a database table';

    public function handle()
    {
        $table = $this->argument('table');
        $connection = $this->option('connection') ?? config('database.default');

        $columns = DB::connection($connection)
            ->select(
                "SELECT column_name, data_type, character_maximum_length, is_nullable, column_default 
                FROM information_schema.columns 
                WHERE table_name = ?
                ORDER BY ordinal_position",
                [$table]
            );

        $this->info("Structure of table: {$table}");
        $this->table(
            ['Column', 'Type', 'Length', 'Nullable', 'Default'],
            array_map(function($col) {
                return [
                    'Column' => $col->column_name,
                    'Type' => $col->data_type,
                    'Length' => $col->character_maximum_length ?? 'N/A',
                    'Nullable' => $col->is_nullable,
                    'Default' => $col->column_default ?? 'NULL'
                ];
            }, $columns)
        );

        return Command::SUCCESS;
    }
}
