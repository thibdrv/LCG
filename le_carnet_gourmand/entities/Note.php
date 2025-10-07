<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/entities/AbstractEntity.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/entities/Recette.php");

class Note extends AbstractEntity implements IEntity {
    private Compte $compte;
    private Recette $recette;
    private float $note;

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

    public function getNote(): float {
        return $this->note;
    }
    public function setNote(float $note): void {
        $this->note = $note;
    }

    // Note::createFromRow($row) (GET / PUT / DELETE)
    public static function createFromRow($row): Note {
        $note = new Note();

        // Hydrate la note
        $note->setNote(floatval($row->note));

        // Associe le compte (minimal : juste la PK)
        if (isset($row->pk_compte)) {
            $compte = new Compte();
            $compte->setPkCompte(intval($row->pk_compte));
            $note->setCompte($compte);
        }
        // Associe la recette (minimal : juste la PK)
        if (isset($row->pk_recette)) {
            $recette = new Recette();
            $recette->setPkRecette(intval($row->pk_recette));
            $note->setRecette($recette);
        }
        return $note;
    }

    // Note::create(...) (POST)
    public static function create(Compte $compte, Recette $recette, float $notes): Note {
        $note = new Note();
        $note->setCompte($compte);
        $note->setRecette($recette);
        $note->setNote($notes);
        return $note;
    }
}