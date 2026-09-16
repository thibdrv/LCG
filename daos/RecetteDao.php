<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/utils/BddSingleton.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/daos/AbstractDao.php");
require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/entities/Recette.php");
require_once(ROOT . "/daos/CompteDao.php");

class RecetteDao extends AbstractDao implements IDao
{
    private CompteDao $compteDao;

    public function __construct() {
        $this->compteDao = new CompteDao();
    }

    function getTableName(): string {
        return "recettes";
    }

    function getPrimaryKeyName(): string {
        return "pk_recette";
    }

    function createEntityFromRow($row): IEntity {
        $recette = Recette::createFromRow($row);
        if (isset($row->fk_compte)) {
            $recette->setCompte($this->compteDao->findByPk($row->fk_compte));
        }
        return $recette;
    }

    function findAll(?int $catId = null): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        
        $sql = "SELECT r.* 
                FROM recettes r
                WHERE r.est_supprime = 0";
        
        if ($catId !== null) {
            $sql = "SELECT r.* 
                    FROM recettes r
                    INNER JOIN categories_recettes cr ON cr.fk_recette = r.pk_recette
                    WHERE r.est_supprime = 0 AND cr.fk_categorie = :cat";
        }

        $stmt = $pdo->prepare($sql);
        if ($catId !== null) {
            $stmt->bindValue(":cat", $catId, PDO::PARAM_INT);
        }
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $recettes = [];
        foreach ($rows as $row) {
            $recettes[] = $this->createEntityFromRow($row);
        }
        return $recettes;
    }

    function findAllApprouvees(?int $catId = null): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        
        $sql = "SELECT r.* 
                FROM recettes r
                WHERE r.est_supprime = 0 AND r.est_approuve = 1";
        
        if ($catId !== null) {
            $sql = "SELECT r.* 
                    FROM recettes r
                    INNER JOIN categories_recettes cr ON cr.fk_recette = r.pk_recette
                    WHERE r.est_supprime = 0 AND r.est_approuve = 1
                    AND cr.fk_categorie = :cat";
        }

        $stmt = $pdo->prepare($sql);
        if ($catId !== null) {
            $stmt->bindValue(":cat", $catId, PDO::PARAM_INT);
        }
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $recettes = [];
        foreach ($rows as $row) {
            $recettes[] = $this->createEntityFromRow($row);
        }
        return $recettes;
    }

    function findByPkAdmin(int|array $pk): Recette {
        $pdo = BddSingleton::getInstance()->getPdo();
        if (is_array($pk)) {
            throw new InvalidArgumentException("findByPk sur Recette attend un entier (pk_recette).");
        }
        $sql = "SELECT * FROM recettes WHERE pk_recette = :id AND est_supprime = 0 LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $pk]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$row) {
            throw new HttpStatusException("Recette avec l'ID $pk introuvable", 404);
        }

        return Recette::createFromRow($row);
    }

    function findByPk(int|array $pk): Recette {
        $pdo = BddSingleton::getInstance()->getPdo();
        if (is_array($pk)) {
            throw new InvalidArgumentException("findByPk sur Recette attend un entier (pk_recette).");
        }
        $sql = "SELECT r.*, c.pk_compte, c.pseudo
                FROM recettes r
                INNER JOIN comptes c ON c.pk_compte = r.fk_compte
                WHERE r.pk_recette = :id AND r.est_supprime = 0
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $pk]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$row) {
            throw new HttpStatusException("Recette avec l'ID $pk introuvable", 404);
        }

        return $this->createEntityFromRow($row);
    }

    function findByCategorie(int $categoriePk): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT r.* 
                FROM recettes r
                INNER JOIN categories_recettes cr ON r.pk_recette = cr.fk_recette
                WHERE cr.fk_categorie = :catFk
                AND r.est_supprime = 0
                AND r.est_approuve = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":catFk", $categoriePk, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $recettes = [];
        foreach ($rows as $row) {
            $recettes[] = $this->createEntityFromRow($row);
        }
        return $recettes;
    }

    function insert(IEntity $entity): int {
        /** @var Recette $entity */
        $pdo = BddSingleton::getInstance()->getPdo();

        $sql = "INSERT INTO recettes (
                    nom, ingredients, details, date_creation, date_modification, 
                    est_approuve, est_supprime, image, lien, fk_compte
                ) VALUES (
                    :nom, :ingredients, :details, :dCreation, :dModif, 
                    :estApprouve, :estSupprime, :image, :lien, :fkCompte
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":nom", $entity->getNom());
        $stmt->bindValue(":ingredients", $entity->getIngredients());
        $stmt->bindValue(":details", $entity->getDetails());
        $stmt->bindValue(":dCreation", $entity->getDateCreation()->format(MYSQL_DATE_FORMAT));
        $stmt->bindValue(":dModif", $entity->getDateModification()->format(MYSQL_DATE_FORMAT));
        $stmt->bindValue(":estApprouve", $entity->getEstApprouve(), PDO::PARAM_BOOL);
        $stmt->bindValue(":estSupprime", $entity->getEstSupprime(), PDO::PARAM_BOOL);
        $stmt->bindValue(":image", $entity->getImage());
        $stmt->bindValue(":lien", $entity->getLien());
        $stmt->bindValue(":fkCompte", $entity->getCompte()->getPkCompte(), PDO::PARAM_INT);

        try {
            $stmt->execute();
            $recetteId = $pdo->lastInsertId();

            // Insertion des catégories associées
            if (!empty($entity->getCategories())) {
                $sqlCat = "INSERT INTO categories_recettes (fk_categorie, fk_recette) VALUES (:catId, :recetteId)";
                $stmtCat = $pdo->prepare($sqlCat);

                foreach ($entity->getCategories() as $catId) {
                    $stmtCat->execute([
                        ":catId" => $catId,
                        ":recetteId" => $recetteId
                    ]);
                }
            }

            return $recetteId;
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            throw new HttpStatusException("Erreur lors de l'insertion de la recette", 500, $ex);
        }
    }

    function update(IEntity $entity): IEntity {
        if (!($entity instanceof Recette)) {
            throw new InvalidArgumentException("Expected instance of Recette");
        }

        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "UPDATE recettes SET 
                    nom = :nom,
                    ingredients = :ingredients,
                    details = :details,
                    date_modification = :dModif,
                    est_approuve = :estApprouve,
                    image = :image,
                    lien = :lien
                WHERE pk_recette = :pkRecette";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":pkRecette", $entity->getPkRecette(), PDO::PARAM_INT);
        $stmt->bindValue(":nom", $entity->getNom());
        $stmt->bindValue(":ingredients", $entity->getIngredients());
        $stmt->bindValue(":details", $entity->getDetails());
        $stmt->bindValue(":dModif", $entity->getDateModification()->format(MYSQL_DATE_FORMAT));
        $stmt->bindValue(":estApprouve", $entity->getEstApprouve(), PDO::PARAM_BOOL);
        $stmt->bindValue(":image", $entity->getImage());
        $stmt->bindValue(":lien", $entity->getLien());

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new HttpStatusException("Recette introuvable", 404);
            }
            return $this->findByPk($entity->getPkRecette());
        } catch (PDOException $ex) {
            error_log("Erreur SQL Update Recette : " . $ex->getMessage());
            throw new HttpStatusException("Erreur lors de la mise à jour de la recette", 500, $ex);
        }
    }

    function delete(int|array $pk): void {
        if (is_array($pk)) {
            throw new InvalidArgumentException("RecetteDao attend une clé primaire simple (int).");
        }

        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "UPDATE recettes 
                SET est_supprime = 1, date_modification = NOW() 
                WHERE pk_recette = :pkRecette";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":pkRecette", $pk, PDO::PARAM_INT);

        try {
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new HttpStatusException("Recette avec l'ID $pk introuvable ou déjà supprimée", 404);
            }
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            throw new HttpStatusException("Erreur lors de la suppression de la recette", 500, $ex);
        }
    }
}
?>