<?php

require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/Categorie.php");
require_once(ROOT . "/daos/CategorieDao.php");

class CategorieService extends AbstractService implements IService
{
    public function __construct() {
        $this->dao = new CategorieDao();
    }

    function getDao(): IDao {
        return $this->dao;
    }

    function findByPk(int|array $pk): IEntity {
        return $this->getDao()->findByPk($pk);
    }

    function findAll(): array {
        return $this->getDao()->findAll();
    }

    function insert(IEntity $entity): int {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut créer une catégorie.");
        }
        return $this->getDao()->insert($entity);
    }

    function update(IEntity $entity): IEntity {
        throw new HttpStatusException("Modification de catégorie interdite", 403);
    }

    function delete(int|array $pk): void {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut supprimer une catégorie.");
        }
        $this->getDao()->delete($pk);
    }
}
?>