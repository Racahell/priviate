<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocationLookupController extends Controller
{
    public function provinces(): JsonResponse
    {
        $rows = DB::table('ec_provinces')
            ->selectRaw('prov_id as id, prov_name as name')
            ->orderBy('prov_name')
            ->get();

        return response()->json($this->normalizeRows($rows));
    }

    public function regencies(string $provinceId): JsonResponse
    {
        $rows = DB::table('ec_cities')
            ->where('prov_id', (int) $provinceId)
            ->selectRaw('city_id as id, city_name as name')
            ->orderBy('city_name')
            ->get();

        return response()->json($this->normalizeRows($rows));
    }

    public function districts(string $regencyId): JsonResponse
    {
        $rows = DB::table('ec_districts')
            ->where('city_id', (int) $regencyId)
            ->selectRaw('dis_id as id, dis_name as name')
            ->orderBy('dis_name')
            ->get();

        return response()->json($this->normalizeRows($rows));
    }

    public function villages(string $districtId): JsonResponse
    {
        $rows = DB::table('ec_subdistricts')
            ->where('dis_id', (int) $districtId)
            ->selectRaw('subdis_id as id, subdis_name as name')
            ->orderBy('subdis_name')
            ->get();

        return response()->json($this->normalizeRows($rows));
    }

    public function postalCodes(Request $request): JsonResponse
    {
        $provinceId = $this->safeInt($request->query('province_id'));
        $cityId = $this->safeInt($request->query('city_id'));
        $districtId = $this->safeInt($request->query('district_id'));
        $villageId = $this->safeInt($request->query('village_id'));

        $provinceInput = trim((string) $request->query('province', ''));
        $cityInput = trim((string) $request->query('city', ''));
        $districtInput = trim((string) $request->query('district', ''));
        $villageInput = trim((string) $request->query('village', ''));

        if ($provinceId === null) {
            $provinceId = $this->resolveProvinceId($provinceInput);
        }
        if ($cityId === null) {
            $cityId = $this->resolveCityId($cityInput, $provinceId);
        }
        if ($districtId === null) {
            $districtId = $this->resolveDistrictId($districtInput, $cityId);
        }
        if ($villageId === null) {
            $villageId = $this->resolveVillageId($villageInput, $districtId);
        }

        $query = DB::table('ec_postalcode')->whereNotNull('postal_code');

        if ($villageId !== null) {
            $query->where('subdis_id', $villageId);
        } elseif ($districtId !== null) {
            $query->where('dis_id', $districtId);
        } elseif ($cityId !== null) {
            $query->where('city_id', $cityId);
        } elseif ($provinceId !== null) {
            $query->where('prov_id', $provinceId);
        }

        $codes = $query->distinct()->pluck('postal_code')
            ->map(fn ($code) => str_pad((string) ((int) $code), 5, '0', STR_PAD_LEFT))
            ->filter(fn ($code) => preg_match('/^[0-9]{5}$/', $code) === 1)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return response()->json($codes);
    }

    private function safeInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $v = trim((string) $value);
        if ($v === '' || !ctype_digit($v)) {
            return null;
        }

        return (int) $v;
    }

    public function debugGeolocation(Request $request): JsonResponse
    {
        if (!config('app.debug')) {
            abort(404);
        }

        $payload = $request->validate([
            'stage' => 'required|string|max:64',
            'message' => 'nullable|string|max:500',
            'error_code' => 'nullable',
            'coords' => 'nullable|array',
            'coords.lat' => 'nullable',
            'coords.lng' => 'nullable',
            'meta' => 'nullable|array',
        ]);

        Log::info('geolocation_debug', [
            'stage' => $payload['stage'],
            'message' => $payload['message'] ?? null,
            'error_code' => $payload['error_code'] ?? null,
            'coords' => $payload['coords'] ?? null,
            'meta' => $payload['meta'] ?? null,
            'ip' => $request->ip(),
            'ua' => (string) $request->userAgent(),
            'at' => now()->toDateTimeString(),
        ]);

        return response()->json(['ok' => true]);
    }

    private function normalizeRows(Collection $rows): array
    {
        return $rows
            ->map(function ($row) {
                return [
                    'id' => (string) data_get($row, 'id'),
                    'name' => Str::upper(trim((string) data_get($row, 'name', ''))),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function resolveProvinceId(string $input): ?int
    {
        return $this->resolveId(
            table: 'ec_provinces',
            idColumn: 'prov_id',
            nameColumn: 'prov_name',
            input: $input
        );
    }

    private function resolveCityId(string $input, ?int $provinceId): ?int
    {
        return $this->resolveId(
            table: 'ec_cities',
            idColumn: 'city_id',
            nameColumn: 'city_name',
            input: $input,
            parentColumn: 'prov_id',
            parentId: $provinceId
        );
    }

    private function resolveDistrictId(string $input, ?int $cityId): ?int
    {
        return $this->resolveId(
            table: 'ec_districts',
            idColumn: 'dis_id',
            nameColumn: 'dis_name',
            input: $input,
            parentColumn: 'city_id',
            parentId: $cityId
        );
    }

    private function resolveVillageId(string $input, ?int $districtId): ?int
    {
        return $this->resolveId(
            table: 'ec_subdistricts',
            idColumn: 'subdis_id',
            nameColumn: 'subdis_name',
            input: $input,
            parentColumn: 'dis_id',
            parentId: $districtId
        );
    }

    private function resolveId(
        string $table,
        string $idColumn,
        string $nameColumn,
        string $input,
        ?string $parentColumn = null,
        ?int $parentId = null
    ): ?int {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        if (ctype_digit($input)) {
            return (int) $input;
        }

        $query = DB::table($table)->select([$idColumn, $nameColumn]);
        if ($parentColumn !== null && $parentId !== null) {
            $query->where($parentColumn, $parentId);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return null;
        }

        $needle = $this->canonicalArea($input);
        foreach ($rows as $row) {
            $candidate = $this->canonicalArea((string) data_get($row, $nameColumn, ''));
            if ($candidate === $needle) {
                return (int) data_get($row, $idColumn);
            }
        }

        return null;
    }

    private function canonicalArea(string $value): string
    {
        $v = Str::upper(trim($value));
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;
        $compact = preg_replace('/\s+/u', '', $v) ?? $v;

        $prefixes = ['KABUPATEN', 'KECAMATAN', 'KELURAHAN', 'KOTA', 'KAB.', 'DESA'];
        foreach ($prefixes as $prefix) {
            $p = preg_replace('/\s+/u', '', $prefix) ?? $prefix;
            if (Str::startsWith($compact, $p)) {
                $compact = (string) Str::substr($compact, strlen($p));
                break;
            }
        }

        return preg_replace('/[^A-Z0-9]/', '', $compact) ?? '';
    }
}
