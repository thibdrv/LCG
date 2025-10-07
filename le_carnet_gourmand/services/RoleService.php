<?php
// Service pour la gestion des rôles (CRUD)
require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/Role.php");
require_once(ROOT . "/daos/RoleDao.php");

class RoleService extends AbstractService implements IService
{
    protected RoleDao $dao;

    public function __construct()
    {
        $this->dao = new RoleDao();
    }

    function getDao() : IDao {
        return $this->dao;
    }

    // Accessible à tout utilisateur connecté
    function findByPk(int|array $pk): IEntity {
        return $this->getDao()->findByPk($pk);
    }

    // Réservé à l’admin
    function findAll(): array {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut voir la liste des rôles");
        }
        return $this->getDao()->findAll();
    }
}


    function insert(IEntity $entity): int {
        throw new HttpStatusException("Insertion de rôle interdite", 403);
    }

    function update(IEntity $entity) {
        throw new HttpStatusException("Modification de rôle interdite", 403);
    }

    function delete(int|array $pk) {
        throw new HttpStatusException("Supression de rôle interdite", 403);
    }

?>