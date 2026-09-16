<?php

require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/IEntity.php");

interface IService 
{
    function findByPk(int|array $pk): IEntity;
    
    function findAll(): array;
    
    function insert(IEntity $entity): int;

    function update(IEntity $entity): IEntity;

    function delete(int|array $pk): void;

    function getDao(): IDao;
}
?>