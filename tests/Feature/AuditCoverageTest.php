<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Penjaga cakupan Audit Log: setiap route yang MENGUBAH data (POST/PUT/PATCH/DELETE)
 * harus menulis ke Audit Log (AuditLog::record), atau didaftarkan beserta alasannya di
 * config/audit.php. Route baru yang lupa dicatat akan membuat tes ini gagal.
 */
class AuditCoverageTest extends TestCase
{
    public function test_semua_route_yang_mengubah_data_tercatat_atau_dikecualikan(): void
    {
        $missing = [];
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            $methods = array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']);
            $action = $route->getActionName();

            if ($methods === [] || ! str_contains($action, '@') || ! str_starts_with($action, 'App\\')) {
                continue;
            }

            if ($this->ignored($route->getName(), $route->uri())) {
                continue;
            }

            [$class, $method] = explode('@', $action);
            $checked++;

            if (! $this->records($class, $method)) {
                $missing[] = implode('|', $methods) . ' ' . $route->uri() . ' → ' . class_basename($class) . '@' . $method;
            }
        }

        $this->assertGreaterThan(50, $checked, 'pemindaian route tidak menemukan cukup route');
        $this->assertSame(
            [],
            $missing,
            "Route berikut mengubah data tapi tidak menulis AuditLog::record() dan belum terdaftar di config/audit.php:\n" . implode("\n", $missing),
        );
    }

    public function test_daftar_pengecualian_tidak_memuat_route_yang_sudah_tidak_ada(): void
    {
        $names = collect(Route::getRoutes()->getRoutes())->map->getName()->filter()->all();
        $uris = collect(Route::getRoutes()->getRoutes())->map->uri()->all();
        $stale = [];

        foreach (config('audit.ignore') as $pattern) {
            if (! collect($names)->contains(fn($name) => Str::is($pattern, $name))) {
                $stale[] = $pattern;
            }
        }

        foreach (config('audit.ignore_uris') as $uri) {
            if (! in_array($uri, $uris, true)) {
                $stale[] = $uri;
            }
        }

        $this->assertSame([], $stale, 'Pengecualian di config/audit.php menunjuk route yang tidak ada: ' . implode(', ', $stale));
    }

    private function ignored(?string $name, string $uri): bool
    {
        if ($name !== null) {
            foreach (config('audit.ignore', []) as $pattern) {
                if (Str::is($pattern, $name)) {
                    return true;
                }
            }
        }

        return in_array($uri, config('audit.ignore_uris', []), true);
    }

    /** Handler mencatat sendiri, atau lewat satu method pembantu di controller yang sama. */
    private function records(string $class, string $method): bool
    {
        $source = $this->source($class, $method);

        if (str_contains($source, 'AuditLog::record(')) {
            return true;
        }

        preg_match_all('/\$this->(\w+)\(/', $source, $helpers);

        foreach (array_unique($helpers[1]) as $helper) {
            if (method_exists($class, $helper) && str_contains($this->source($class, $helper), 'AuditLog::record(')) {
                return true;
            }
        }

        return false;
    }

    private function source(string $class, string $method): string
    {
        $reflection = new ReflectionMethod($class, $method);
        $lines = file($reflection->getFileName());

        return implode('', array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
    }
}