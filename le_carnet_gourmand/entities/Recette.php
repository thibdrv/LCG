<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/entities/AbstractEntity.php");
require_once(ROOT . "/entities/Compte.php");

class Recette extends AbstractEntity implements IEntity {
    private int $pk_recette;
    private string $nom;
    private string $ingredients;
    private string $details;
    private DateTime $date_creation;
    private DateTime $date_modification;
    private bool $est_approuve;
    private bool $est_supprime;
    private ?string $image = null;
    private ?string $lien = null;
    private Compte $compte;
    private array $categories = [];


    public function getCategories(): array {
        return $this->categories;
    }

    public function setCategories(array $categories): void {
        $this->categories = $categories;
    }

    function getPkRecette(): int {
        return $this->pk_recette;
    }
    function setPkRecette(int $pk_recette): void {
        $this->pk_recette = $pk_recette;
    }

    function getNom(): string {
        return $this->nom;
    }
    function setNom(string $nom): void {
        $this->nom = $nom;
    }

    function getDateCreation(): DateTime {
        return $this->date_creation;
    }
    function setDateCreation(DateTime $date_creation): void {
        $this->date_creation = $date_creation;
    }

    function getDateModification(): DateTime {
        return $this->date_modification;
    }
    function setDateModification(DateTime $date_modification): void {
        $this->date_modification = $date_modification;
    }

    function getEstSupprime(): bool {
        return $this->est_supprime;
    }
    function setEstSupprime(bool $est_supprime): void {
        $this->est_supprime = $est_supprime;
    }

    function getEstApprouve(): bool {
        return $this->est_approuve;
    }
    function setEstApprouve(bool $est_approuve): void {
        $this->est_approuve = $est_approuve;
    }

    function getIngredients(): string {
        return $this->ingredients;
    }
    function setIngredients(string $ingredients): void {
        $this->ingredients = $ingredients;
    }

    function getDetails(): string {
        return $this->details;
    }
    function setDetails(string $details): void {
        $this->details = $details;
    }

    function getImage(): ?string {
        return $this->image;
    }
    function setImage(?string $image): void {
        $this->image = $image ?? "";
    }

    function getLien(): ?string {
        return $this->lien;
    }
    public function setLien(?string $lien): void {
        $this->lien = $lien ?? "";
    }


    function getCompte(): Compte {
        return $this->compte;
    }
    function setCompte(Compte $compte): void {
        $this->compte = $compte;
    }


            // Recette::createFromRow($row) (GET / PUT / DELETE)
    public static function createFromRow($row): Recette {
        $recette = new Recette();

        $recette->setPkRecette( intval($row->pk_recette) );
        $recette->setNom( $row->nom );
        $recette->setIngredients( $row->ingredients );
        $recette->setDetails( $row->details );
        $recette->setDateCreation( new DateTime($row->date_creation) );
        $recette->setDateModification( new DateTime($row->date_modification) );
        $recette->setEstApprouve( boolval($row->est_approuve) );
        $recette->setEstSupprime( boolval($row->est_supprime) );
        $recette->setImage( $row->image );
        $recette->setLien( $row->lien );

        // TO DO si tu veux hydrater le compte associé :
        if (isset($row->pk_compte)) {
            $compte = new Compte();
            $compte->setPkCompte(intval($row->pk_compte));
            $compte->setPseudo($row->pseudo ?? "");
            $recette->setCompte($compte);
        }

        return $recette;
    }

    // TO DO Recette::create(...) (POST)
    public static function create(
        string $nom,
        string $ingredients,
        string $details,
        string $image,
        string $lien,
        Compte $compte
    ): Recette {
        $recette = new Recette();

        $recette->setNom($nom);
        $recette->setIngredients($ingredients);
        $recette->setDetails($details);
        $recette->setImage($image);
        $recette->setLien($lien);
        $recette->setDateCreation(new DateTime());
        $recette->setDateModification(new DateTime());
        $recette->setEstApprouve(false);
        $recette->setEstSupprime(false);
        $recette->setCompte($compte);

        return $recette;
    }
}