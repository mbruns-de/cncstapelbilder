<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Palette;
use App\Entity\Platte;

class PlattenService
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    //To-Do hier noch genauer festlegen, wie die Abweichungen sein sollen
    private function istKompatibel(Platte $obere, Platte $untere): bool
    {
        $allowedBreiteMax = $untere->getBreite()/0.7;
        $allowedBreiteMin = $untere->getBreite()*0.7;
        $allowedLaengeMax = $untere->getLaenge()/0.7;
        $allowedLaengeMin = $untere->getLaenge()*0.7;

        return ($obere->getBreite() >= $allowedBreiteMin && $obere->getBreite() <= $allowedBreiteMax && $obere->getLaenge() >= $allowedLaengeMin && $obere->getLaenge() <= $allowedLaengeMax);
    }

    public function initPaletten(): array
    {
        $palettenbreite = $this->params->get('app.palettenlaenge');
        $palettenlaenge = $this->params->get('app.palettenlaenge');

        $stapel = [
            'quer' => [
                'palette' => new Palette($palettenbreite, $palettenlaenge, $palettenbreite, $palettenlaenge/2),
                'stapelplaetze' => [
                    'quer_1' => [],
                    'quer_2' => []
                ]
            ],
            'laengs' => [
                'palette' => new Palette($palettenbreite, $palettenlaenge, $palettenbreite/2, $palettenlaenge),
                'stapelplaetze' => [
                    'laengs_1' => [],
                    'laengs_2' => []
                ]
            ],
            'ganz' => [
                'palette' => new Palette($palettenbreite, $palettenlaenge, $palettenbreite, $palettenlaenge),
                'stapelplaetze' => [
                    'ganz' => []
                ]
            ]
        ];

        return $stapel;
    }

    public function optimierteStapelbildung(array &$stapel, Platte &$neuePlatte): bool
    {
        $ausgangsplatte = $neuePlatte;
        foreach ($stapel as &$paletteData) {
            $neuePlatte = $ausgangsplatte;
            if ($paletteData['palette']->passtPlatte($neuePlatte)) {
                foreach ($paletteData['stapelplaetze'] as $platzName => &$stapelplatz) {
                    if (empty($stapelplatz) || $this->istKompatibel(end($stapelplatz), $neuePlatte)) {
                        $stapelplatz[] = $neuePlatte;
                        return true; // Platte wurde platziert
                    }
                }
            }

            // Falls nicht passend, versuche die Platte zu drehen
            $gedrehtePlatte = new Platte($neuePlatte->getLaenge(), $neuePlatte->getBreite(), $neuePlatte->getDicke(), $neuePlatte->getId(), $neuePlatte->getKanteUnten(), $neuePlatte->getKanteLinks(), $neuePlatte->getKanteOben(), $neuePlatte->getKanteRechts(), true);
            $neuePlatte = $gedrehtePlatte;
            if ($paletteData['palette']->passtPlatte($gedrehtePlatte)) {
                foreach ($paletteData['stapelplaetze'] as $platzName => &$stapelplatz) {
                    if (empty($stapelplatz) || $this->istKompatibel(end($stapelplatz), $gedrehtePlatte)) {
                        $stapelplatz[] = $gedrehtePlatte;
                        return true; // Gedrehte Platte wurde platziert
                    }
                }
            }
        }
        return false;
    }

    public function removeLastPlatte(array &$stapel): void
    {
        $lastPlatte = null;
        $lastPlatteKey = null;
        $lastPlattePaletteKey = null;

        // Finde die Platte mit dem höchsten Timestamp
        foreach ($stapel as $paletteKey => &$paletteData) {
            foreach ($paletteData['stapelplaetze'] as $platzKey => &$stapelplatz) {
                if (!empty($stapelplatz)) {
                    $letztePlatte = end($stapelplatz);
                    if ($lastPlatte === null || $letztePlatte->getTimestamp() > $lastPlatte->getTimestamp()) {
                        $lastPlatte = $letztePlatte;
                        $lastPlatteKey = $platzKey;
                        $lastPlattePaletteKey = $paletteKey;
                    }
                }
            }
        }

        // Falls eine Platte gefunden wurde, diese entfernen
        if ($lastPlatte !== null) {
            array_pop($stapel[$lastPlattePaletteKey]['stapelplaetze'][$lastPlatteKey]);
        }
    }

    public function findLetztePlatte(array $stapel): ?Platte
    {
        $lastPlatte = null;

        // Finde die Platte mit dem höchsten Timestamp
        foreach ($stapel as $paletteKey => &$paletteData) {
            foreach ($paletteData['stapelplaetze'] as $platzKey => &$stapelplatz) {
                if (!empty($stapelplatz)) {
                    $letztePlatte = end($stapelplatz);
                    if ($lastPlatte === null || $letztePlatte->getTimestamp() > $lastPlatte->getTimestamp()) {
                        $lastPlatte = $letztePlatte;
                    }
                }
            }
        }

        return $lastPlatte;
    }

    // Methode, um eine bestimmte Palette zu leeren
    public function clearPalette(array &$stapel, string $paletteTyp): void
    {
        if (isset($stapel[$paletteTyp])) {
            foreach ($stapel[$paletteTyp]['stapelplaetze'] as &$stapelplatz) {
                $stapelplatz = []; // Löscht alle Platten in diesem Stapelplatz
            }
        }
    }
}