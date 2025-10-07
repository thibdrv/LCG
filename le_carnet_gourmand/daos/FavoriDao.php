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

    function getTableName() : string {
        return "favori"; // le nom de ta table en BDD
    }

    function getPrimaryKeyName(): array {
        // Clé composite
        return ["fk_compte", "fk_recette"];
    }

    // Hydrate un objet Favori à partir d'une ligne SQL
    function createEntityFromRow($row) : IEntity {
        $favori = Favori::createFromRow($row);

        if (isset($row->fk_compte)) {
            $compteDao = new CompteDao();
            $favori->setCompte($compteDao->findByPk($row->fk_compte));
        }

        if (isset($row->fk_recette)) {
            $recetteDao = new RecetteDao();
            $favori->setRecette($recetteDao->findByPk($row->fk_recette));
        }

        return $favori;
    }

    // Récupérer tous les favoris
    function findAll(): array {
        throw new Exception("Méthode non disponible pour Favori");
    }

    // Récupérer un favori par sa clé composite
    function findByPk(int|array $pk): Favori {
        if (!is_array($pk)) {
        throw new InvalidArgumentException("FavoriDao attend une clé composite (array).");
        }
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT f.*
                FROM favoris f
                WHERE f.fk_compte = ? AND f.fk_recette = ?
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $pk['fk_compte'], PDO::PARAM_INT);
        $stmt->bindValue(2, $pk['fk_recette'], PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();

        return $this->createEntityFromRow($row);
    }

    // Récupérer tous les favoris d’un utilisateur
    public function findByCompte(int $fkCompte): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT * FROM favori WHERE fk_compte = :fkCompte";

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

    function insert(IEntity $entity) : int {
        /** @var Favori $entity */
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "INSERT INTO favori (fk_compte, fk_recette)
                VALUES (:fkCompte, :fkRecette)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $entity->getCompte()->getPkCompte(), PDO::PARAM_INT);
        $stmt->bindValue(":fkRecette", $entity->getRecette()->getPkRecette(), PDO::PARAM_INT);

        try {
            $stmt->execute();
            return 1; // tu peux retourner 1 (ligne insérée)
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            throw new HttpStatusException("Erreur lors de l'insertion du favori", 500, $ex);
        }
    }

    // update n'a pas vraiment de sens pour une table d'association
    function update(IEntity $entity): IEntity {
        throw new Exception("Méthode non disponible pour Favori");
    }

    // Supprimer un favori par clé composite
    function delete(int|array $pk) {
        if (!is_array($pk)) {
        throw new InvalidArgumentException("FavoriDao attend une clé composite (array).");
        }
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "DELETE FROM favori WHERE fk_compte = ? AND fk_recette = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $pk['fk_compte'], PDO::PARAM_INT);
        $stmt->bindValue(2, $pk['fk_recette'], PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new HttpStatusException("Erreur lors de la suppression du favori", 500);
        }

        if ($stmt->rowCount() === 0) {
            throw new HttpStatusException("Aucun favori trouvé pour ce couple compte/recette", 404);
        }
    }

    function exists(int $fkCompte, int $fkRecette): bool {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT COUNT(*) FROM favori WHERE fk_compte = :compte AND fk_recette = :recette";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":compte" => $fkCompte,
            ":recette" => $fkRecette
        ]);
        return $stmt->fetchColumn() > 0;
    }
}
?>