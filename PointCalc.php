<?php

require 'PointCalcConst.php';

class PointCalc
{
    // Pontok
    protected $alapPontok;
    protected $bonuszPontok;
    protected $osszPontok;

    // Választott szak
    protected $egyetem;
    protected $kar;
    protected $szak;

    // Tárgyak
    protected $magyar;
    protected $matek;
    protected $tori;
    protected $angol;
    protected $informatika;
    protected $fizika;
    protected $biologia;
    protected $kemia;
    protected $francia;
    protected $nemet;
    protected $olasz;
    protected $orosz;
    protected $spanyol;

    // Nyelvvizsga
    protected $angolVizsga;
    protected $nemetVizsga;
    protected $vizsgaTipusKozep;
    protected $vizsgaTipusEmelt;


    public function __construct($d) {
        $this->setEgyetem($d['valasztott-szak']['egyetem']);
        $this->setKar($d['valasztott-szak']['kar']);
        $this->setSzak($d['valasztott-szak']['szak']);
        $this->setVizsgaTipusKozep($d['tobbletpontok'][0]['tipus'][0]);
        $this->setVizsgaTipusEmelt($d['tobbletpontok'][1]['tipus'][0]);

        foreach ($d['erettsegi-eredmenyek'] as $e) {
            $this->calculateSubjectPoints($e);
        }
        $this->kotelezoPontOsszeadas();
        $this->addOsszPontok();
        $this->nyelvVizsgaBonusz();
    }

    public function addOsszPontok() {
        $osszpontok = $this->getAlapPontok() + $this->getBonuszPontok();
        if (!$this->checkValidity()) {
            $this->setOsszPontok($osszpontok);
            return $this->getOsszPontok() . " (" . $this->getAlapPontok() . " alappont + " . $this->getBonuszPontok() . " többletpont)";
        }
        return $this->checkValidity();
    }

    protected function calculateSubjectPoints($d) {
        $nev = $d['nev'];
        $tipus = $d['tipus'];
        $eredmeny = $d['eredmeny'];
        $pluszpont = 0;
        $alapPont = 0;

        $eredmeny = (int) str_replace('%', '', $eredmeny);

        $alapPont += $eredmeny;

        if ($tipus == EMELT) {
            $pluszpont += 50;
        }

        switch ($nev) {
            case MAGYAR:
                $this->setMagyar($eredmeny, $tipus);
                break;
            case MATEK:
                $this->setMatek($eredmeny, $tipus);
                break;
            case TORI:
                $this->setTori($eredmeny, $tipus);
                break;
            case ANGOL:
                $this->setAngol($eredmeny, $tipus);
                break;
            case INFO:
                $this->setInfo($eredmeny, $tipus);
                break;
            case FIZIKA:
                $this->setFizika($eredmeny, $tipus);
                break;
            case BIOLOGIA:
                $this->setBiologia($eredmeny, $tipus);
                break;
            case KEMIA:
                $this->setKemia($eredmeny, $tipus);
                break;
            case FRANCIA:
                $this->setFrancia($eredmeny, $tipus);
                break;
            case NEMET:
                $this->setNemet($eredmeny, $tipus);
                break;
            case OLASZ:
                $this->setOlasz($eredmeny, $tipus);
                break;
            case OROSZ:
                $this->setOrosz($eredmeny, $tipus);
                break;
            case SPANYOL:
                $this->setSpanyol($eredmeny, $tipus);
                break;
        }

        $this->kotelezoPontOsszeadas();
        $this->addBonuszPontok($pluszpont);
    }

    protected function checkValidity() {
        if (isset($this->getMagyar()['eredmeny'])) {
            if ($this->getMagyar()['eredmeny'] < 20) {
                return "hiba, nem lehetséges a pontszámítás a magyar nyelv és irodalom tárgyból elért 20% alatti eredmény miatt";
            }
            if ($this->getTori()['eredmeny'] < 20) {
                return "hiba, nem lehetséges a pontszámítás a történelem tárgyból elért 20% alatti eredmény miatt";
            }
            if ($this->getMatek()['eredmeny'] < 20) {
                return "hiba, nem lehetséges a pontszámítás a matematika tárgyból elért 20% alatti eredmény miatt";
            }
        }
        else {
            return "hiba, nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt";
        }


        switch ($this->getEgyetem()) {
            case "ELTE":
                if ($this->getInfo()['eredmeny'] < 20) {
                    return "hiba, nem lehetséges a pontszámítás az informatika tárgyból elért 20% alatti eredmény miatt";
                }
                break;
            case "PPKE":
                if ($this->getAngol()['eredmeny'] < 20) {
                    return "hiba, nem lehetséges a pontszámítás az angol tárgyból elért 20% alatti eredmény miatt";
                }
                if ($this->getAngol()['tipus'] == KOZEP) {
                    return "hiba, nem lehetséges a pontszámítás a kötelező érettségi tárgy(ak) hiánya miatt";
                }
                break;
        }
        return false;
    }

    protected function nyelvVizsgaBonusz() {
        $vizsgaPont = 0;
        if ($this->getVizsgaTipusKozep() == "B") {
            $vizsgaPont += 28;
        }
        if ($this->getVizsgaTipusEmelt() == "C") {
            $vizsgaPont += 40;
        }
        return $vizsgaPont;
    }

    protected function kotelezoPontOsszeadas() {
        $pontok = 0;
        switch ($this->getEgyetem()) {
            case "ELTE":
                $pontok += $this->getMatek()['eredmeny'];
                $maxPont = max($this->getBiologia()['eredmeny'], $this->getFizika()['eredmeny'], $this->getInfo()['eredmeny'], $this->getKemia()['eredmeny']);
                $this->setAlapPontok(($maxPont + $pontok) * 2);
                break;
            case "PPKE":
                $pontok += $this->getAngol()['eredmeny'];
                $maxPont = max($this->getFrancia()['eredmeny'], $this->getNemet()['eredmeny'], $this->getOlasz()['eredmeny'], $this->getOrosz()['eredmeny'], $this->getSpanyol()['eredmeny'], $this->getTori()['eredmeny']);
                $this->setAlapPontok(($maxPont + $pontok) * 2);
                break;
        }
    }

    protected function addBonuszPontok($v) {
        $this->bonuszPontok += $v;
        if ($this->bonuszPontok > 100) {
            $this->bonuszPontok = 100;
        }
    }

    /*
     * Setters
     */
    public function setVizsgaTipusKozep($v) {
        $this->vizsgaTipusKozep = $v;
    }

    public function setVizsgaTipusEmelt($v) {
        $this->vizsgaTipusEmelt = $v;
    }

    public function setBiologia($v, $t) {
        $this->biologia = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setKemia($v, $t) {
        $this->kemia = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setFrancia($v, $t) {
        $this->francia = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setNemet($v, $t) {
        $this->nemet = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setOlasz($v, $t) {
        $this->olasz = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setOrosz($v, $t) {
        $this->orosz = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setSpanyol($v, $t) {
        $this->spanyol = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setAlapPontok($v) {
        $this->alapPontok = $v;
    }

    public function setBonuszPontok($v) {
        $this->bonuszPontok = $v;
    }

    public function setOsszPontok($v) {
        $this->osszPontok = $v;
    }

    public function setEgyetem($v) {
        $this->egyetem = $v;
    }

    public function setKar($v) {
        $this->kar = $v;
    }

    public function setSzak($v) {
        $this->szak = $v;
    }

    public function setMagyar($v, $t) {
        $this->magyar = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setMatek($v, $t) {
        $this->matek = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setTori($v, $t) {
        $this->tori = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setAngol($v, $t) {
        $this->angol = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setInfo($v, $t) {
        $this->informatika = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setFizika($v, $t) {
        $this->fizika = [
            "eredmeny" => $v,
            "tipus" => $t,
        ];
    }

    public function setAngolVizsga($v) {
        $this->angolVizsga = $v;
    }

    public function setNemetVizsga($v) {
        $this->nemetVizsga = $v;
    }


    /*
     * Getters
     */
    public function getVizsgaTipusKozep() {
        return $this->vizsgaTipusKozep;
    }

    public function getVizsgaTipusEmelt() {
        return $this->vizsgaTipusEmelt;
    }

    public function getBiologia() {
        return $this->biologia;
    }

    public function getKemia() {
        return $this->kemia;
    }

    public function getFrancia() {
        return $this->francia;
    }

    public function getNemet() {
        return $this->nemet;
    }

    public function getOlasz() {
        return $this->olasz;
    }

    public function getOrosz() {
        return $this->orosz;
    }

    public function getSpanyol() {
        return $this->spanyol;
    }

    public function getBonuszPontok() {
        return $this->bonuszPontok;
    }

    public function getAlapPontok() {
        return $this->alapPontok;
    }

    public function getBasePoints() {
        return $this->basePoints;
    }

    public function getAdditionalPoints() {
        return $this->additionalPoints;
    }

    public function getPointSum() {
        return $this->pointSum;
    }

    public function getEgyetem() {
        return $this->egyetem;
    }

    public function getKar() {
        return $this->kar;
    }

    public function getSzak() {
        return $this->szak;
    }

    public function getMagyar() {
        return $this->magyar;
    }

    public function getMatek() {
        return $this->matek;
    }

    public function getTori() {
        return $this->tori;
    }

    public function getAngol() {
        return $this->angol;
    }

    public function getInfo() {
        return $this->informatika;
    }

    public function getFizika() {
        return $this->fizika;
    }

    public function getAngolVizsga() {
        return $this->angolVizsga;
    }

    public function getNemetVizsga() {
        return $this->nemetVizsga;
    }

    public function getOsszPontok() {
        return $this->osszPontok;
    }
}
?>