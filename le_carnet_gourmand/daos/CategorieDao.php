<?php

require_once (ROOT . "/entities/IEntity.php");
require_once (ROOT . "/daos/IDao.php");
require_once (ROOT . "/daos/AbstractDao.php");
require_once (ROOT . "/utils/BddSingleton.php");
require_once (ROOT . "/exceptions/HttpStatusException.php");
require_once (ROOT . "/entities/Categorie.php");

class CategorieDao extends AbstractDao implements IDao 
{
    
    function getTableName(): string {
        return "categories";
    }

    function getPrimaryKeyName(): string {
        return "pk_categorie";
    }

    function createEntityFromRow($row): IEntity {
        return Categorie::createFromRow($row);
    }

    // toutes les categories avec leurs noms
    public function findAll(): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT c.* FROM categories c";
        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $categories = [];

        foreach ($rows as $row) {
            $categories[] = $this->createEntityFromRow($row);
        }

        return $categories;
    }

    // une categorie avec son nom
    function findByPk(int|array $pk): IEntity {   
        if (is_array($pk)) {
            throw new InvalidArgumentException("CategoriesDao attend une clé primaire simple (int).");
        } 
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT c.* FROM categories c WHERE c.pk_categorie = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $pk, PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            throw new HttpStatusException("Categorie avec l'ID " . $pk . " introuvable", 404);
        }
        return $this->createEntityFromRow($row);
    }

    function insert(IEntity $entity): int {
        /** @var Categorie $entity */
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "INSERT INTO categories (nom) VALUES (:nom)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":nom", $entity->getNom());

        try {
            $stmt->execute();
            return $pdo->lastInsertId();
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            throw new HttpStatusException("Erreur lors de l'insertion de la catégorie", 500, $ex);
        }
    }

    function update(IEntity $entity){
        throw new Exception("Method not yet implemented");
    }

    function delete(int|array $pk) {
        if (is_array($pk)) {
            throw new InvalidArgumentException("CategoriesDao attend une clé primaire simple (int).");
        } 

        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "DELETE FROM categories WHERE pk_categorie = :pkCategorie";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":pkCategorie", $pk, PDO::PARAM_INT);

        try {
            $stmt->execute();

            // Vérifie si une ligne a été supprimée
            if ($stmt->rowCount() === 0) {
                throw new HttpStatusException("Catégorie avec l'ID $pk introuvable", 404);
            }

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1451) {
                // Contrainte de clé étrangère violée
                throw new HttpStatusException("Suppression non autorisée : la catégorie a encore une ou des recettes associées", 405);
            }
            // Autres erreurs SQL → 500
            throw new HttpStatusException("Erreur SQL : " . $e->getMessage(), 500);
        }
    }
}
?>