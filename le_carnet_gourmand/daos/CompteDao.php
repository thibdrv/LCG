<?php // repositories/CompteRepository.php
// chaque fonction créée dans ce repository est directement CRUD complet par sa classe abstraite

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/utils/BddSingleton.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/daos/AbstractDao.php");
require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/daos/RoleDao.php");

class CompteDao extends AbstractDao implements IDao
{
    private RoleDao $roleDao;

    public function __construct() {
        $this->roleDao = new RoleDao();
    }

    function getTableName(): string {
        return "comptes";
    }

    function getPrimaryKeyName(): string {
        return "pk_compte";
    }

    // pour récupérer les infos de son entité
    function createEntityFromRow($row): IEntity {
        $compte = Compte::createFromRow($row);
        $roleDao = new RoleDao();
        $compte->setRole($roleDao->findByPk($row->fk_role));
        return $compte;
    }

    function isValidCredential($compte): ?int {
        $pdo = BddSingleton::getInstance()->getPdo();

        $stmt = $pdo->prepare("SELECT pk_compte, mot_de_passe FROM comptes WHERE email = :email LIMIT 1");
        $stmt->bindValue(':email', $compte->getEmail(), PDO::PARAM_STR);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $hash = $row->mot_de_passe ?? '';
        $received_trim = trim($compte->getMotDePasse());

        if ($hash && password_verify($received_trim, $hash)) {
            return (int) $row->pk_compte;
        }

        return null;
    }

    function findAll(): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT c.* FROM comptes c WHERE est_supprime = 0";

        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $comptes = [];

        foreach ($rows as $row) {
            $comptes[] = $this->createEntityFromRow($row);
        }

        return $comptes;
    }

    function findByPk(int|array $pk): Compte {
        if (is_array($pk)) {
            throw new InvalidArgumentException("CompteDao attend une clé primaire simple (int).");
        }

        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT c.* 
                FROM comptes c
                WHERE c.pk_compte = ?
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $pk, PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            throw new HttpStatusException("Compte avec l'ID " . $pk . " introuvable", 404);
        }
            // Vérifier les flags
        if ($row->est_supprime) {
            throw new HttpStatusException("Compte avec l'ID " . $pk . " est supprimé", 403);
        }
        if ($row->est_banni) {
            throw new HttpStatusException("Compte avec l'ID " . $pk . " est banni", 403);
    }
        return $this->createEntityFromRow($row);
    }

    function insert(IEntity $entity): int { 
        /** @var Compte $entity */
        $pdo = BddSingleton::getInstance()->getPdo(); 
        $sql = "INSERT INTO comptes (
                    email, pseudo, mot_de_passe, date_creation, est_supprime, est_banni, fk_role
                ) VALUES (
                    :email, :pseudo, :mdp, :dCreation, :estSupp, :estBan, :fkRole
                )"; 
        
        $stmt = $pdo->prepare($sql); 
        $stmt->bindValue(":email", $entity->getEmail()); 
        $stmt->bindValue(":pseudo", $entity->getPseudo()); 
        $stmt->bindValue(":mdp", password_hash($entity->getMotDePasse(), PASSWORD_BCRYPT)); 
        $stmt->bindValue(":dCreation", $entity->getDateCreation()->format(MYSQL_DATE_FORMAT)); 
        $stmt->bindValue(":estSupp", $entity->getEstSupprime(), PDO::PARAM_BOOL); 
        $stmt->bindValue(":estBan", $entity->getEstBanni(), PDO::PARAM_BOOL); 

        // rôle par défaut si null
        $role = $entity->getRole() ? $entity->getRole()->getPkRole() : 3; 
        $stmt->bindValue(":fkRole", $role, PDO::PARAM_INT);

        try { 
            $stmt->execute(); 
            return $pdo->lastInsertId();
        } catch (PDOException $ex) { 
            if (str_starts_with($ex->getMessage(), "SQLSTATE[23000]:")) {
                $msg = explode(": ", $ex->getMessage())[2];
                if (str_starts_with($msg, "1062 ")) {
                    $msg = explode(" ", $msg)[6];
                    throw new HttpStatusException($msg . " - already exists ", 499); 
                }
            }
            throw new HttpStatusException($ex->getMessage(), 500, $ex); 
        }
    }

    function update(IEntity $entity): IEntity {
        if (!($entity instanceof Compte)) {
            throw new InvalidArgumentException("Expected instance of Compte");
        }

        $pdo = BddSingleton::getInstance()->getPdo(); 
        $sql = "UPDATE comptes SET 
                    mot_de_passe = :mdp,
                    est_banni = :estBan
                WHERE pk_compte = :pkCompte";
    
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":mdp", password_hash($entity->getMotDePasse(), PASSWORD_DEFAULT));
        $stmt->bindValue(":estBan", $entity->getEstBanni(), PDO::PARAM_BOOL);
        $stmt->bindValue(":pkCompte", $entity->getPkCompte(), PDO::PARAM_INT);
    
        try {
            $stmt->execute();
            return $this->findByPk($entity->getPkCompte());
        } catch (PDOException $ex) {
            throw new HttpStatusException("Erreur lors de la mise à jour : " . __CLASS__  . " " . __FUNCTION__ . " " . __LINE__, 500, $ex);
        }
    }

    // suppression logique
    function delete(int|array $pk): void {
        if (is_array($pk)) {
            throw new InvalidArgumentException("delete() attend un entier, pas un tableau.");
        }

        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "UPDATE comptes SET est_supprime = 1 WHERE pk_compte = :pkCompte";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":pkCompte", $pk, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new HttpStatusException("Erreur lors de la suppression du compte", 500);
        }
    }

    // restauration logique
    function restore(int|array $pk): void {
        if (is_array($pk)) {
            throw new InvalidArgumentException("restore() attend un entier, pas un tableau.");
        }

        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "UPDATE comptes SET est_supprime = 0 WHERE pk_compte = :pkCompte";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":pkCompte", $pk, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new HttpStatusException("Erreur lors de la restauration du compte", 500);
        }
    }
}
?>
