<?php

namespace Tests\Unit;

use App\Enums\ControlePaiement;
use App\Support\AnalyseMontant;
use PHPUnit\Framework\TestCase;

class AnalyseMontantTest extends TestCase
{
    public function test_montant_conforme(): void
    {
        $resultat = AnalyseMontant::analyser(10000, 2500, 4, 8);

        $this->assertTrue($resultat['accepte']);
        $this->assertSame(ControlePaiement::Conforme, $resultat['controle']);
        $this->assertSame(4, $resultat['places']);
    }

    public function test_montant_insuffisant(): void
    {
        $resultat = AnalyseMontant::analyser(7500, 2500, 4, 8);

        $this->assertTrue($resultat['accepte']);
        $this->assertSame(ControlePaiement::Insuffisant, $resultat['controle']);
        $this->assertSame(3, $resultat['places']);
    }

    public function test_montant_excedent(): void
    {
        $resultat = AnalyseMontant::analyser(12500, 2500, 4, 8);

        $this->assertTrue($resultat['accepte']);
        $this->assertSame(ControlePaiement::Excedent, $resultat['controle']);
        $this->assertSame(5, $resultat['places']);
    }

    public function test_montant_non_multiple_refuse(): void
    {
        $resultat = AnalyseMontant::analyser(9000, 2500, 4, 8);

        $this->assertFalse($resultat['accepte']);
        $this->assertSame(0, $resultat['places']);
        $this->assertNotNull($resultat['message']);
    }

    public function test_montant_au_dessus_du_maximum_refuse(): void
    {
        $resultat = AnalyseMontant::analyser(22500, 2500, 4, 8);

        $this->assertFalse($resultat['accepte']);
        $this->assertSame(9, $resultat['places']);
    }
}
