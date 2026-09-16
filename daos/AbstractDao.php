<?php

require_once ROOT . "/entities/IEntity.php";
require_once ROOT . "/daos/IDao.php";

abstract class AbstractDao implements IDao
{   
    abstract function getTableName(): string;
    abstract function getPrimaryKeyName();
    abstract function createEntityFromRow($row): IEntity;

    function findAll(): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT e.* FROM " . $this->getTableName() . " e";
        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $entities = [];
        foreach ($rows as $row) {
            $entities[] = $this->createEntityFromRow($row);
        }
        return $entities;
    }

    function findByPk(int|array $pk): IEntity {
        $pdo = BddSingleton::getInstance()->getPdo();
        $pkName = $this->getPrimaryKeyName();
        
        // Gestion clé composite
        if (is_array($pkName)) {
            if (!is_array($pk)) {
                throw new InvalidArgumentException("Cette table utilise une clé composite.");
            }
            $conditions = [];
            $params = [];
            foreach ($pkName as $index => $key) {
                $conditions[] = "e." . $key . " = :" . $key;
                $params[":" . $key] = $pk[$key];
            }
            $sql = "SELECT e.* FROM " . $this->getTableName() . " e WHERE " . implode(" AND ", $conditions) . " LIMIT 1";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
        } else {
            // Clé simple
            $sql = "SELECT e.* FROM " . $this->getTableName() . " e WHERE e." . $pkName . " = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(1, $pk);
        }
        
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            throw new HttpStatusException("Entité non trouvée", 404);
        }
        return $this->createEntityFromRow($row);
    }

    abstract function insert(IEntity $entity): int;
    abstract function update(IEntity $entity): IEntity;
    abstract function delete(array|int $pk): void;
}
?>
