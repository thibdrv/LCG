<?php

require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/services/IService.php");

// On utlise le concept de design pattern Adapter
// Le service va proposer une "adaptation" vers le Dao
abstract class AbstractService implements IService
{
    function findByPk(int|array $pk) : IEntity
    {
        return $this->getDao()->findByPk($pk);
    }
    
    function findAll() : array
    {
        return $this->getDao()->findAll();
    }

    function insert(IEntity $entity) : int
    {
        return $this->getDao()->insert($entity);
    }

    function update(IEntity $entity)
    {
        return $this->getDao()->update($entity);
    }

    function delete(int|array $pk)
    {
        return $this->getDao()->delete($pk);
    }

    function getDao() : IDao {
        return $this->dao;
    }
}

?>