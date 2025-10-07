<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/entities/AbstractEntity.php");

class Categorie extends AbstractEntity implements IEntity {
    private int $pk_categorie;
    private string $nom;

    
    public function getPkCategorie(): int {
        return $this->pk_categorie;
    }
    public function setPkCategorie(int $pk_categorie): void {
        $this->pk_categorie = $pk_categorie;
    }

    public function getNom(): string {
        return $this->nom;
    }
    public function setNom(string $nom): void {
        $this->nom = $nom;
    }

    // Categorie::createFromRow($row) (GET / PUT / DELETE)
    public static function createFromRow($row): Categorie {
        $categorie = new Categorie();
        $categorie->setPkCategorie(intval($row->pk_categorie));
        $categorie->setNom($row->nom);
        return $categorie;
    }

    // Categorie::create(...) (POST)
    public static function create(string $nom): Categorie {
        $categorie = new Categorie();
        $categorie->setNom($nom);
        return $categorie;
    }
}