<?php // PARENT ses enfents en herite

// Éviter la duplication de code CRUD dans tous les repositories
// Centraliser la sécurité anti-injections SQL au même endroit
// Permettre la pagination facilement sur toutes les tables
// Support à la fois des clés primaires simples et composites
// Faciliter la maintenance : un seul fichier à modifier pour tous"
// "C'est le cœur de mon architecture données !"

// Éviter de répéter le même code dans tous les repositories. C'est le "parent" de tous vos repositories.
// BOITE A OUTILS COMPLETE // LOGIQUE COMMUNE

// ✅ CRUD COMPLET fourni par l'AbstractRepository
// $repo->create($data);    // CREATE - Insertion
// $repo->findByPk($pk);    // READ - Lecture par pk  
// $repo->findAll();        // READ - Lecture multiple
// $repo->update($pk, $data); // UPDATE - Mise à jour
// $repo->delete($pk);      // DELETE - Suppression

// STMT = verif

require_once ROOT . "/entities/IEntity.php";
require_once ROOT . "/daos/IDao.php";

abstract class AbstractDao implements IDao
{   
    
    abstract function getTableName():string;
    abstract function getPrimaryKeyName();
    abstract function createEntityFromRow($row):IEntity;

    function findAll(): array // car retourne plusieurs valeurs
    {
        $pdo = BddSingleton::getInstance()->getPdo();
        // * toutes les tupple -> colonnes
        $sql = "SELECT e.* FROM " . $this->getTableName() . " e";
        $stmt = $pdo->prepare($sql);
        // = chaque ligne comme un objet PHP
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $entities = [];
        foreach ($rows as $row) {
            // = transforme chaque ligne SQL en une entité
            $entities[] = $this->createEntityFromRow($row);
        }

        return $entities;
    }

    function findByPk(int|array $pk): IEntity
    {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT e.*
        FROM ".$this->getTableName()." e
        WHERE e.".$this->getPrimaryKeyName()." =?
        LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $pk);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            throw new HttpStatusException("Entity Role:" . $pk, 400);
        }
        return $this->createEntityFromRow($row);
    }

    abstract function insert(IEntity $entity) : int;
    abstract function update(IEntity $entity);
    abstract function delete(int|array $pk);
 
}
?>