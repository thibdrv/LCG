<?php

require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/services/IService.php");

abstract class AbstractService implements IService
{
    protected IDao $dao;

    public function __construct() {
        // Laissé vide, le constructeur enfant doit initialiser $dao
    }

    function findByPk(int|array $pk): IEntity {
        return $this->getDao()->findByPk($pk);
    }
    
    function findAll(): array {
        return $this->getDao()->findAll();
    }

    function insert(IEntity $entity): int {
        return $this->getDao()->insert($entity);
    }

    function update(IEntity $entity): IEntity {
        return $this->getDao()->update($entity);
    }

    function delete(int|array $pk): void {
        $this->getDao()->delete($pk);
    }

    function getDao(): IDao {
        return $this->dao;
    }
}
?>
