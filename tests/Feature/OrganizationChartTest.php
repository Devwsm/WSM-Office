<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/** Struktur Organisasi dari akun aktif + Atasan Langsung, dengan pop-up akses. */
class OrganizationChartTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    public function test_chart_nests_active_accounts_under_their_manager(): void
    {
        $this->officeSetting();
        $p = $this->company();
        $p['gepeng']->delete(); // nonaktif -> tidak tampil

        $html = $this->actingAs($p['owner'])->get(route('owner.organization'))->assertOk()->getContent();

        foreach (['Kanaya', 'Aldora', 'Rania'] as $name) {
            $this->assertStringContainsString($name, $html);
        }
        $this->assertStringNotContainsString('Gepeng', $html);
    }

    public function test_popup_payload_has_access_of_every_account_including_nested(): void
    {
        $this->officeSetting();
        $p = $this->company();

        $html = $this->actingAs($p['owner'])->get(route('owner.organization'))->getContent();

        // Aldora bersarang di bawah Kanaya; levelnya (work = manage) ikut terkirim.
        $this->assertStringContainsString('u' . $p['aldora']->id, $html);
        $this->assertStringContainsString('Work Control', $html);
        $this->assertStringContainsString('Manajer', $html);
    }

    public function test_employee_cannot_open_the_chart(): void
    {
        $this->officeSetting();
        $p = $this->company();

        $this->actingAs($p['aldora'])->get(route('owner.organization'))->assertForbidden();
    }
}