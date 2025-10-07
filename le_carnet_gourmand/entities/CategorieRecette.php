<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/entities/AbstractEntity.php");
require_once(ROOT . "/entities/Recette.php");
require_once(ROOT . "/entities/IEntity.php");

class CategorieRecette extends AbstractEntity implements IEntity {
    private Recette $recette;
    private Categorie $categorie;

    

    public function getRecette(): Recette {
        return $this->recette;
    }

    public function setRecette(Recette $recette): void {
        $this->recette = $recette;
    }

    public function getCategorie(): Categorie {
        return $this->categorie;
    }

    public function setCategorie(Categorie $categorie): void {
        $this->categorie = $categorie;
    }


    // CategorieRecette::createFromRow($row) (GET / PUT / DELETE)
    public static function createFromRow($row): CategorieRecette {
        $categorieRecette = new CategorieRecette();

        if (isset($row->pk_recette)) {
            $recette = new Recette();
            $recette->setPkRecette(intval($row->pk_recette));
            $categorieRecette->setRecette($recette);
        }

        if (isset($row->pk_categorie)) {
            $categorie = new Categorie();
            $categorie->setPkCategorie(intval($row->pk_categorie));
            $categorieRecette->setCategorie($categorie);
        }
        return $categorieRecette;
    }

    // CategorieRecette::create(...) (POST)
    public static function create(Recette $recette, Categorie $categorie): CategorieRecette {
        $categorieRecette = new CategorieRecette();
        $categorieRecette->setRecette($recette);
        $categorieRecette->setCategorie($categorie);
        return $categorieRecette;
    }
}