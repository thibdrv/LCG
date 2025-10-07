<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/utils/BddSingleton.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/daos/AbstractDao.php");
require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/entities/Note.php");
require_once(ROOT . "/daos/CompteDao.php");
require_once(ROOT . "/daos/RecetteDao.php");

class NoteDao extends AbstractDao implements IDao
{
    private CompteDao $compteDao;
    private RecetteDao $recetteDao;

    public function __construct() {
        $this->compteDao = new CompteDao();
        $this->recetteDao = new RecetteDao();
    }

    function getTableName(): string {
        return "mettre_note";
    }

    // Ici on a une clé primaire composite
    function getPrimaryKeyName(): array {
        return ["fk_compte", "fk_recette"];
    }

    function createEntityFromRow($row): IEntity {
        $note = Note::createFromRow($row);

        if (isset($row->fk_compte)) {
            $compteDao = new CompteDao();
            $note->setCompte($compteDao->findByPk($row->fk_compte));
        }

        if (isset($row->fk_recette)) {
            $recetteDao = new RecetteDao();
            $note->setRecette($recetteDao->findByPk($row->fk_recette));
        }

        return $note;
    }


    function findAll(): array {
        throw new Exception("find all not implement");
    }
    
    function findByPk(int|array $pk): Note {
        if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
            throw new InvalidArgumentException("NoteDao attend une clé primaire composite : ['fk_compte'=>X, 'fk_recette'=>Y]");
        }

        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT mn.*
                FROM mettre_note mn
                WHERE mn.fk_compte = :fkCompte AND mn.fk_recette = :fkRecette";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $pk['fk_compte'], PDO::PARAM_INT);
        $stmt->bindValue(":fkRecette", $pk['fk_recette'], PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            throw new HttpStatusException("Note introuvable pour ce couple compte/recette", 404);
        }

        return $this->createEntityFromRow($row);
    }

    function MoyenneParRecette(int $pkRecette): float {
        $pdo = BddSingleton::getInstance()->getPdo();
        // transform la valeur du ENUM (string) en un nombre utilisable dans AVG()
        $sql = "SELECT AVG(CAST(note AS DECIMAL(2,1))) AS moyenne 
                FROM mettre_note 
                WHERE fk_recette = :fkRecette";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkRecette", $pkRecette, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        if ($row && $row->moyenne !== null) {
            return (float) $row->moyenne;
        }

        return 0.0; // = si aucune note pour cette recette
    }

    public function totalNoteParRecette(int $pkRecette): int {
        $pdo = BddSingleton::getInstance()->getPdo();

        $sql = "SELECT COUNT(*) AS total
                FROM mettre_note
                WHERE fk_recette = :fkRecette";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkRecette", $pkRecette, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $row['total'];
    }

    // Récupérer toutes les notes d’un utilisateur
    public function findByCompte(int $fkCompte): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT * FROM mettre_note WHERE fk_compte = :fkCompte";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $fkCompte, PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();

        $rows = $stmt->fetchAll();

        if (!$rows || count($rows) === 0) {
            throw new HttpStatusException("Aucune note trouvée pour cet utilisateur.", 404);
        }

        $notes = [];
        foreach ($rows as $row) {
            $notes[] = $this->createEntityFromRow($row);
        }

        return $notes;
    }


    function insert(IEntity $entity): int {
        /** @var Note $entity */
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "INSERT INTO mettre_note (fk_compte, fk_recette, note)
                VALUES (:fkCompte, :fkRecette, :note)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $entity->getCompte()->getPkCompte(), PDO::PARAM_INT);
        $stmt->bindValue(":fkRecette", $entity->getRecette()->getPkRecette(), PDO::PARAM_INT);
        $stmt->bindValue(":note", $entity->getNote());

        try {
            $stmt->execute();
            return 1;
        } catch (PDOException $ex) {
            if ($ex->getCode() == "23000") {
                throw new HttpStatusException("Vous avez déjà noté cette recette.", 400, $ex);
            }
            throw new HttpStatusException("Erreur SQL : " . $ex->getMessage(), 500, $ex);
        }
    }


    function update(IEntity $entity){
        throw new Exception("Modification de note interdite");
    }

    function delete(int|array $pk) {
        if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
            throw new InvalidArgumentException("MettreNoteDao attend une clé composite : ['fk_compte'=>X, 'fk_recette'=>Y]");
        }

        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "DELETE FROM mettre_note WHERE fk_compte = :fkCompte AND fk_recette = :fkRecette";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $pk['fk_compte'], PDO::PARAM_INT);
        $stmt->bindValue(":fkRecette", $pk['fk_recette'], PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new HttpStatusException("Erreur lors de la suppression de la note", 500);
        }

        // ✅ Vérifie si une ligne a été supprimée
        if ($stmt->rowCount() === 0) {
            throw new HttpStatusException("Aucune note trouvée pour ce couple compte/recette", 404);
        }
    }

}
?>