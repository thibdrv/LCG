<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/daos/AbstractDao.php");
require_once(ROOT . "/utils/BddSingleton.php");
require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/entities/Role.php");

class RoleDao extends AbstractDao implements IDao {
    
    function getTableName(): string {
        return "roles";
    }
    
    function getPrimaryKeyName(): string {
        return "pk_role";
    }
    
    function createEntityFromRow($row): IEntity {
        return Role::createFromRow($row);
    }

    function findAll(): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT r.* FROM roles r";
        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $roles = [];

        foreach ($rows as $row) {
            $roles[] = $this->createEntityFromRow($row);
        }

        return $roles;
    }

    function findByPk(int|array $pk): IEntity {   
        if (is_array($pk)) {
            throw new InvalidArgumentException("RoleDao attend une clé primaire simple (int).");
        } 
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT r.* FROM roles r WHERE r.pk_role = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $pk, PDO::PARAM_INT);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            throw new HttpStatusException("Rôle avec l'ID " . $pk . " introuvable", 404);
        }
        return $this->createEntityFromRow($row);
    }

    function insert(IEntity $entity): int {
        throw new Exception("Insertion de rôle interdite");
    }
    
    function delete(int|array $pk): void { 
        throw new Exception("Suppression de rôle interdite");
    }
    
    function update(IEntity $entity): IEntity {
        throw new Exception("Modification de rôle interdite");
    }
}
?>