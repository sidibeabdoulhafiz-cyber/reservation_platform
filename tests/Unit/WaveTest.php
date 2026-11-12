<?php

namespace Tests\Unit;

use App\Support\Wave;
use Tests\TestCase;

class WaveTest extends TestCase
{
    public function test_https_est_accepte(): void
    {
        $this->assertTrue(Wave::lienAccepte('https://pay.wave.com/m/abc'));
    }

    public function test_http_est_accepte_hors_production(): void
    {
        $this->assertTrue(Wave::lienAccepte('http://localhost:8000/dev/payer'));
        $this->assertTrue(Wave::lienAccepte('http://127.0.0.1:8000/dev/payer'));
        $this->assertTrue(Wave::lienAccepte('http://192.168.1.10:8000/dev/payer'));
    }

    public function test_lien_vide_est_refuse(): void
    {
        $this->assertFalse(Wave::lienAccepte(''));
        $this->assertFalse(Wave::lienAccepte('javascript:alert(1)'));
    }
}
