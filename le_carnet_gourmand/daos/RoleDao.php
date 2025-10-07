<?php // repositories/RoleRepository.php

require_once (ROOT . "/entities/IEntity.php");
require_once (ROOT . "/daos/IDao.php");
require_once (ROOT . "/daos/AbstractDao.php");
require_once (ROOT . "/utils/BddSingleton.php");
require_once (ROOT . "/exceptions/HttpStatusException.php");
require_once (ROOT . "/entities/Role.php");

class RoleDao extends AbstractDao implements IDao {
    
    function getTableName():string{
        return "role";
    }
    function getPrimaryKeyName():string{
        return "pk_role";
    }
    function createEntityFromRow($row):IEntity{
        return Role::createFromRow($row);
    }

    public function findAll(): array {
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

    function findByPk(int|array $pk) : IEntity {   
        if (is_array($pk)) {
        throw new InvalidArgumentException("RoleDao attend une clé primaire simple (int).");
        } 
        $pdo = BddSingleton::getInstance()->getPdo();
        // r = allias de la table
        $sql = "SELECT r.* FROM roles r Where r.pk_role =?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $pk);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();
            if(!$row){
                throw new HttpStatusException("Entity Role:" . $pk, 400);
            }
            return Role::createFromRow($row);
        }


    function insert(IEntity $entity) : int {
        throw new Exception("Insertion de rôle interdite");
    }
    function delete(int|array $pk){ 
        throw new Exception("Supression de rôle interdite");
    }
    function update(IEntity $entity){
        throw new Exception("Modification de rôle interdite");
    }

}
?>