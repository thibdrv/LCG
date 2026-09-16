<?php

require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/Role.php");
require_once(ROOT . "/daos/RoleDao.php");

class RoleService extends AbstractService implements IService
{
    public function __construct() {
        $this->dao = new RoleDao();
    }

    function getDao(): IDao {
        return $this->dao;
    }

    function findByPk(int|array $pk): IEntity {
        return $this->getDao()->findByPk($pk);
    }

    function findAll(): array {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut voir la liste des rôles");
        }
        return $this->getDao()->findAll();
    }

    function insert(IEntity $entity): int {
        throw new HttpStatusException("Insertion de rôle interdite", 403);
    }

    function update(IEntity $entity): IEntity {
        throw new HttpStatusException("Modification de rôle interdite", 403);
    }

    function delete(int|array $pk): void {
        throw new HttpStatusException("Suppression de rôle interdite", 403);
    }
}
?>