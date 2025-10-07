<?php 

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/entities/AbstractEntity.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/entities/Recette.php");

class Commentaire extends AbstractEntity implements IEntity {
    private string $contenu;
    private DateTime $date_creation;
    private bool $est_approuve;
    private bool $est_supprime;
    private Compte $compte;
    private Recette $recette;


    // ES-CE vraiment utile ?
    function __construct() { /* RAS */ }

    
    public function getCompte(): ?Compte {
        return $this->compte;
    }
    public function setCompte(?Compte $compte): void {
        $this->compte = $compte;
    }

    public function getRecette(): ?Recette {
        return $this->recette;
    }
    public function setRecette(?Recette $recette): void {
        $this->recette = $recette;
    }

    public function getContenu(): ?string {
        return $this->contenu ?? null;
    }
    public function setContenu(?string $contenu): void {
        $this->contenu = $contenu;
    }

    public function getDateCreation(): ?DateTime {
        return $this->date_creation ?? null;
    }
    public function setDateCreation(?DateTime $date_creation): void {
        $this->date_creation = $date_creation;
    }

    public function getEstApprouve(): ?bool {
        return $this->est_approuve ?? null;
    }
    public function setEstApprouve(?bool $est_approuve): void {
        $this->est_approuve = $est_approuve;
    }

    public function getEstSupprime(): ?bool {
        return $this->est_supprime ?? null;
    }
    public function setEstSupprime(?bool $est_supprime): void {
        $this->est_supprime = $est_supprime;
    }

    // Commentaire::createFromRow($row) (GET / PUT / DELETE)
    public static function createFromRow($row): Commentaire {
        $commentaire = new Commentaire();
        $commentaire->setContenu($row->contenu);
        $commentaire->setDateCreation(new DateTime($row->date_creation));
        $commentaire->setEstApprouve(boolval($row->est_approuve));
        $commentaire->setEstSupprime(boolval($row->est_supprime));

        if (isset($row->pk_compte)) {
            $compte = new Compte();
            $compte->setPkCompte(intval($row->pk_compte));
            $commentaire->setCompte($compte);
        }

        if (isset($row->pk_recette)) {
            $recette = new Recette();
            $recette->setPkRecette(intval($row->pk_recette));
            $commentaire->setRecette($recette);
        }
        return $commentaire;
    }

    // Commentaire::create(...) (POST)
    public static function create(Compte $compte, Recette $recette, string $contenu): Commentaire {
        $commentaire = new Commentaire();
        $commentaire->setCompte($compte);
        $commentaire->setRecette($recette);
        $commentaire->setContenu($contenu);
        $commentaire->setDateCreation(new DateTime());
        $commentaire->setEstApprouve(false);
        $commentaire->setEstSupprime(false);
        return $commentaire;
    }
}
?>