<?php

namespace App\Entity;

class Platte
{
    private $breite;
    private $laenge;
    private $dicke;
    private $id;
    private $kanteLinks;
    private $kanteOben;
    private $kanteRechts;
    private $kanteUnten;
    private $gedreht;
    private $timestamp;

    public function __construct($breite, $laenge, $dicke, $id, $kanteLinks, $kanteOben, $kanteRechts, $kanteUnten, $gedreht)
    {
        $this->breite = $breite;
        $this->laenge = $laenge;
        $this->dicke = $dicke;
        $this->id = $id;
        $this->kanteLinks = $kanteLinks;
        $this->kanteOben = $kanteOben;
        $this->kanteRechts = $kanteRechts;
        $this->kanteUnten = $kanteUnten;
        $this->gedreht = $gedreht;
        $this->timestamp = time(); // Setzt die aktuelle Zeit als ID
    }

    public function getBreite()
    {
        return $this->breite;
    }

    public function getLaenge()
    {
        return $this->laenge;
    }

    public function getDicke()
    {
        return $this->dicke;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getKanteLinks() {
        return $this->kanteLinks;
    }

    public function getKanteOben() {
        return $this->kanteOben;
    }

    public function getKanteRechts() {
        return $this->kanteRechts;
    }

    public function getKanteUnten() {
        return $this->kanteUnten;
    }

    public function getGedreht() {
        return $this->gedreht;
    }

    public function getTimestamp(): int {
        return $this->timestamp;
    }
}
