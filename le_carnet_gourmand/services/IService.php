<?php

require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/IEntity.php");

// Définit les opérations de base pour tout service
interface IService 
{
    function findByPk(int|array $pk) : IEntity;
    
    function findAll() : array;
    
    function insert(IEntity $entity) : int;

    function update(IEntity $entity);

    function delete(int|array $pk);

    // simple getter qui retourne l’objet DAO utilisé par ton service
    function getDao() : IDao;

}

?>