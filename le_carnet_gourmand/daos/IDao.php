<?php

// obligation d'avoir ces methodes dans le code enfant
require_once(ROOT . "/entities/IEntity.php");

interface IDao 
{
    function findByPk(int|array $pk) : IEntity;
    
    function findAll();

    function insert(IEntity $entity) : int;
    
    function update(IEntity $entity);

    function delete(int|array $pk);
}

?>