<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/entities/AbstractEntity.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/entities/Recette.php");

class Favori extends AbstractEntity implements IEntity {
    private Compte $compte;
    private Recette $recette;


    // ES-CE vraiment utile ?
    function __construct() { /* RAS */ }


    public function getCompte(): Compte {
        return $this->compte;
    }
    public function setCompte(Compte $compte): void {
        $this->compte = $compte;
    }

    public function getRecette(): Recette {
        return $this->recette;
    }
    public function setRecette(Recette $recette): void {
        $this->recette = $recette;
    }

     // Favori::createFromRow($row) (GET / PUT / DELETE)
    public static function createFromRow($row): Favori {
        $favori = new Favori();

        // Associe le compte (minimal : juste la PK)
        if (isset($row->pk_compte)) {
            $compte = new Compte();
            $compte->setPkCompte(intval($row->pk_compte));
            $favori->setCompte($compte);
        }

        // Associe la recette (minimal : juste la PK)
        if (isset($row->pk_recette)) {
            $recette = new Recette();
            $recette->setPkRecette(intval($row->pk_recette));
            $favori->setRecette($recette);
        }

        return $favori;
    }

    // Favori::create(...) (POST)
    public static function create(Compte $compte, Recette $recette): Favori {
        $favori = new Favori();
        $favori->setCompte($compte);
        $favori->setRecette($recette);
        return $favori;
    }
}