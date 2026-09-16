<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/daos/AbstractDao.php");
require_once(ROOT . "/utils/BddSingleton.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/entities/Favori.php");
require_once(ROOT . "/daos/CompteDao.php");
require_once(ROOT . "/daos/RecetteDao.php");

class FavoriDao extends AbstractDao implements IDao
{
    private CompteDao $compteDao;
    private RecetteDao $recetteDao;

    public function __construct() {
        $this->compteDao = new CompteDao();
        $this->recetteDao = new RecetteDao();
    }

    function getTableName(): string {
        return "favoris";
    }

    function getPrimaryKeyName(): array {
        return ["fk_compte", "fk_recette"];
    }

    function createEntityFromRow($row): IEntity {
        $favori = Favori::createFromRow($row);

        if (isset($row->fk_compte)) {
            $favori->setCompte($this->compteDao->findByPk($row->fk_compte));
        }

        if (isset($row->fk_recette)) {
            $favori->setRecette($this->recetteDao->findByPk($row->fk_recette));
        }

        return $favori;
    }

    function findAll(): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT f.* FROM favoris f";
        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $favoris = [];
        foreach ($rows as $row) {
            $favoris[] = $this->createEntityFromRow($row);
        }
        return $favoris;
    }

    function findByPk(int|array $pk): IEntity {
        if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
            throw new InvalidArgumentException("FavoriDao attend une clé composite : ['fk_compte'=>X, 'fk_recette'=>Y]");
        }
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT f.*
                FROM favoris f
                WHERE f.fk_compte = :fkCompte AND f.fk_recette = :fkRecette
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $pk['fk_compte'], PDO::PARAM_INT);
        $stmt->bindValue(":fkRecette", $pk['fk_recette'], PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            throw new HttpStatusException("Favori introuvable pour ce couple compte/recette", 404);
        }

        return $this->createEntityFromRow($row);
    }

    public function findByCompte(int $fkCompte): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT * FROM favoris WHERE fk_compte = :fkCompte";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $fkCompte, PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $favoris = [];
        foreach ($rows as $row) {
            $favoris[] = $this->createEntityFromRow($row);
        }
        return $favoris;
    }

    function insert(IEntity $entity): int {
        /** @var Favori $entity */
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "INSERT INTO favoris (fk_compte, fk_recette)
                VALUES (:fkCompte, :fkRecette)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $entity->getCompte()->getPkCompte(), PDO::PARAM_INT);
        $stmt->bindValue(":fkRecette", $entity->getRecette()->getPkRecette(), PDO::PARAM_INT);

        try {
            $stmt->execute();
            return 1;
        } catch (PDOException $ex) {
            if ($ex->errorInfo[1] == 1062) {
                throw new HttpStatusException("Ce favori existe déjà", 409);
            }
            error_log($ex->getMessage());
            throw new HttpStatusException("Erreur lors de l'insertion du favori", 500, $ex);
        }
    }

    function update(IEntity $entity): IEntity {
        throw new Exception("Méthode non disponible pour Favori");
    }

    function delete(int|array $pk): void {
        if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
            throw new InvalidArgumentException("FavoriDao attend une clé composite (array).");
        }
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "DELETE FROM favoris WHERE fk_compte = :fkCompte AND fk_recette = :fkRecette";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $pk['fk_compte'], PDO::PARAM_INT);
        $stmt->bindValue(":fkRecette", $pk['fk_recette'], PDO::PARAM_INT);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new HttpStatusException("Aucun favori trouvé pour ce couple compte/recette", 404);
            }
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            throw new HttpStatusException("Erreur lors de la suppression du favori", 500, $ex);
        }
    }

    function exists(int $fkCompte, int $fkRecette): bool {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT COUNT(*) FROM favoris WHERE fk_compte = :compte AND fk_recette = :recette";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":compte" => $fkCompte,
            ":recette" => $fkRecette
        ]);
        return $stmt->fetchColumn() > 0;
    }
}
?>