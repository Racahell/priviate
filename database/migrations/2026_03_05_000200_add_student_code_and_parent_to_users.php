<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'code')) {
                $table->string('code', 24)->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('users', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->index()->after('code');
            }
        });

        $studentIds = DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'siswa')
            ->whereNull('users.code')
            ->pluck('users.id');

        foreach ($studentIds as $studentId) {
            DB::table('users')
                ->where('id', $studentId)
                ->update(['code' => $this->generateStudentCode()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'parent_id')) {
                $table->dropColumn('parent_id');
            }

            if (Schema::hasColumn('users', 'code')) {
                $table->dropUnique('users_code_unique');
                $table->dropColumn('code');
            }
        });
    }

    private function generateStudentCode(): string
    {
        do {
            $candidate = 'SIS-' . strtoupper(Str::random(8));
            $exists = DB::table('users')->where('code', $candidate)->exists();
        } while ($exists);

        return $candidate;
    }
};
