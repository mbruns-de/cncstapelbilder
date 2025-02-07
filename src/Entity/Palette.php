<?php

namespace App\Entity;

class Palette {
    public $breite;
    public $laenge;
    public $stapelBreite;
    public $stapelLaenge;

    public function __construct($breite, $laenge, $stapelBreite, $stapelLaenge) {
        $this->breite = $breite;
        $this->laenge = $laenge;
        $this->stapelBreite = $stapelBreite;
        $this->stapelLaenge = $stapelLaenge;
    }

    public function passtPlatte(Platte $platte): bool {
        return ($platte->getBreite() <= $this->stapelBreite && $platte->getLaenge() <= $this->stapelLaenge);
    }
}