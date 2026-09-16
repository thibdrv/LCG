<?php

require_once(ROOT . "/entities/IEntity.php");

interface IDao 
{
    function findByPk(int|array $pk): IEntity;
    
    function findAll(): array;

    function insert(IEntity $entity): int;
    
    function update(IEntity $entity): IEntity;

    function delete(array|int $pk): void;
}
?>