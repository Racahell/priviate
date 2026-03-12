<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('class_levels')) {
            Schema::create('class_levels', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('category', 20);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $defaults = [
            ['name' => 'Kelas 1', 'category' => 'awal', 'sort_order' => 1],
            ['name' => 'Kelas 2', 'category' => 'awal', 'sort_order' => 2],
            ['name' => 'Kelas 3', 'category' => 'awal', 'sort_order' => 3],
            ['name' => 'Kelas 4', 'category' => 'awal', 'sort_order' => 4],
            ['name' => 'Kelas 5', 'category' => 'awal', 'sort_order' => 5],
            ['name' => 'Kelas 6', 'category' => 'awal', 'sort_order' => 6],
            ['name' => 'Kelas 7', 'category' => 'menengah', 'sort_order' => 7],
            ['name' => 'Kelas 8', 'category' => 'menengah', 'sort_order' => 8],
            ['name' => 'Kelas 9', 'category' => 'menengah', 'sort_order' => 9],
            ['name' => 'Kelas 10', 'category' => 'tinggi', 'sort_order' => 10],
            ['name' => 'Kelas 11', 'category' => 'tinggi', 'sort_order' => 11],
            ['name' => 'Kelas 12', 'category' => 'tinggi', 'sort_order' => 12],
            ['name' => 'Mahasiswa', 'category' => 'tinggi', 'sort_order' => 13],
        ];

        foreach ($defaults as $row) {
            $exists = DB::table('class_levels')
                ->where('name', $row['name'])
                ->where('category', $row['category'])
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('class_levels')->insert([
                'name' => $row['name'],
                'category' => $row['category'],
                'sort_order' => $row['sort_order'],
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'class_level_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreignId('class_level_id')->nullable()->after('level')->constrained('class_levels');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'class_level_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropConstrainedForeignId('class_level_id');
            });
        }

        Schema::dropIfExists('class_levels');
    }
};
