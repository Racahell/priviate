<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportIndonesiaLocations extends Command
{
    protected $signature = 'locations:import-indonesia-local {--force : Re-import and replace existing tables}';

    protected $description = 'Import complete Indonesia province/city/district/village/postal data into local DB tables';

    private const SOURCE_URL = 'https://raw.githubusercontent.com/rfahmi/kode-pos-indonesia/master/indonesia.sql';

    public function handle(): int
    {
        $hasTables = $this->tableExists('ec_provinces')
            && $this->tableExists('ec_cities')
            && $this->tableExists('ec_districts')
            && $this->tableExists('ec_subdistricts')
            && $this->tableExists('ec_postalcode');

        if ($hasTables && !$this->option('force')) {
            $this->info('Tabel lokasi lokal sudah ada. Gunakan --force untuk impor ulang.');
            $this->printCounts();
            return self::SUCCESS;
        }

        $this->info('Mengunduh dataset lokasi Indonesia...');
        $response = Http::timeout(120)
            ->withOptions(['proxy' => false])
            ->get(self::SOURCE_URL);

        if (!$response->ok()) {
            $this->error('Gagal download dataset. HTTP ' . $response->status());
            return self::FAILURE;
        }

        $sql = (string) $response->body();
        if ($sql === '') {
            $this->error('Dataset kosong.');
            return self::FAILURE;
        }

        $this->info('Menjalankan import ke database lokal...');
        $fkDisabled = false;
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $fkDisabled = true;
            DB::statement('DROP TABLE IF EXISTS `ec_postalcode`');
            DB::statement('DROP TABLE IF EXISTS `ec_subdistricts`');
            DB::statement('DROP TABLE IF EXISTS `ec_districts`');
            DB::statement('DROP TABLE IF EXISTS `ec_cities`');
            DB::statement('DROP TABLE IF EXISTS `ec_provinces`');

            $statements = $this->extractRelevantStatements($sql);
            foreach ($statements as $statement) {
                DB::unprepared($statement);
            }
        } catch (\Throwable $e) {
            $this->error('Import gagal: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            if ($fkDisabled) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        $this->info('Import lokasi lokal selesai.');
        $this->printCounts();

        return self::SUCCESS;
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    private function extractRelevantStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $previous = '';

        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;

            if ($char === "'" && !$inDouble && $previous !== '\\\\') {
                $inSingle = !$inSingle;
            } elseif ($char === '"' && !$inSingle && $previous !== '\\\\') {
                $inDouble = !$inDouble;
            }

            if ($char === ';' && !$inSingle && !$inDouble) {
                $clean = trim($buffer);
                $buffer = '';

                if ($clean === '') {
                    $previous = $char;
                    continue;
                }

                $lower = Str::lower($clean);
                if (
                    Str::startsWith($lower, 'create table `ec_')
                    || Str::startsWith($lower, 'insert into `ec_')
                ) {
                    $statements[] = $clean;
                }
            }

            $previous = $char;
        }

        if (empty($statements)) {
            throw new \RuntimeException('Tidak menemukan statement ec_* pada dataset SQL.');
        }

        return $statements;
    }

    private function printCounts(): void
    {
        if (!$this->tableExists('ec_provinces')) {
            return;
        }

        $this->line('Ringkasan data lokal:');
        $this->line('- provinces: ' . DB::table('ec_provinces')->count());
        $this->line('- cities: ' . DB::table('ec_cities')->count());
        $this->line('- districts: ' . DB::table('ec_districts')->count());
        $this->line('- subdistricts: ' . DB::table('ec_subdistricts')->count());
        $this->line('- postal rows: ' . DB::table('ec_postalcode')->count());
    }
}
