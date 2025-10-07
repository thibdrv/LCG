<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/entities/AbstractEntity.php");
require_once(ROOT . "/entities/Role.php");

class Compte extends AbstractEntity implements IEntity {
    private int $pk_compte = 0;
    private string $email = "";
    private string $pseudo = "";
    private string $motDePasse = "";
    private DateTime $dateCreation;
    private bool $estBanni = false;
    private bool $estSupprime = false;
    private Role $role;

    // Constructeur "léger"
    public function __construct() {
        $this->dateCreation = new DateTime();
        $this->estBanni = false;
        $this->estSupprime = false;
    }

    // --- Factory pour création ---
    public static function create(string $email, string $pseudo, string $motDePasse): Compte {
        if (trim($email) === "" || trim($pseudo) === "" || trim($motDePasse) === "") {
            throw new InvalidArgumentException("Email, pseudo et mot de passe sont obligatoires.");
        }

        $compte = new Compte();
        $compte->setEmail($email);
        $compte->setPseudo($pseudo);
        $compte->setMotDePasse($motDePasse);
        return $compte;
    }

    // --- Factory pour lecture BDD ---
    public static function createFromRow($row, bool $keepMotDePasse = false): Compte {
        $compte = new Compte();
        $compte->setPkCompte((int)$row->pk_compte);
        $compte->setEmail($row->email);
        $compte->setPseudo($row->pseudo);

        if ($keepMotDePasse && !empty($row->mot_de_passe)) {
            $compte->setMotDePasse($row->mot_de_passe);
        }

        $compte->setDateCreation(new DateTime($row->date_creation));
        $compte->setEstSupprime((bool)$row->est_supprime);
        $compte->setEstBanni((bool)$row->est_banni);
        return $compte;
    }

        public static function createForCredential(string $email, string $motDePasse): Compte {
        $compte = new Compte();
        $compte->setEmail($email);
        $compte->setMotDePasse($motDePasse);
        return $compte;
    }


    // --- GETTERS / SETTERS ---
    public function getPkCompte(): int {
        return $this->pk_compte;
    }
    public function setPkCompte(int $pk_compte): void {
        $this->pk_compte = $pk_compte;
    }

    public function getEmail(): string {
        return $this->email;
    }
    public function setEmail(string $email): void {
        if (trim($email) === "") {
            throw new InvalidArgumentException("Email obligatoire.");
        }
        $this->email = $email;
    }

    public function getPseudo(): string {
        return $this->pseudo;
    }
    public function setPseudo(string $pseudo): void {
        if (trim($pseudo) === "") {
            throw new InvalidArgumentException("Pseudo obligatoire.");
        }
        $this->pseudo = $pseudo;
    }

    public function getMotDePasse(): string {
        return $this->motDePasse;
    }
    public function setMotDePasse(string $motDePasse): void {
        if (trim($motDePasse) === "") {
            throw new InvalidArgumentException("Mot de passe obligatoire.");
        }
        $this->motDePasse = $motDePasse;
    }

    public function getDateCreation(): DateTime {
        return $this->dateCreation;
    }
    public function setDateCreation(DateTime $dateCreation): void {
        $this->dateCreation = $dateCreation;
    }

    public function getEstBanni(): bool {
        return $this->estBanni;
    }
    public function setEstBanni(bool $estBanni): void {
        $this->estBanni = $estBanni;
    }

    public function getEstSupprime(): bool {
        return $this->estSupprime;
    }
    public function setEstSupprime(bool $estSupprime): void {
        $this->estSupprime = $estSupprime;
    }

    public function getRole(): Role {
        return $this->role;
    }
    public function setRole(Role $role): void {
        $this->role = $role;
    }
}
?>