<?php

require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/Categorie.php");
require_once(ROOT . "/daos/CategorieDao.php");

class CategorieService extends AbstractService implements IService
{
    protected CategorieDao $dao;

    public function __construct()
    {
        $this->dao = new CategorieDao();
    }

    function getDao(): IDao {
        return $this->dao;
    }

    // Accessible à tous
    function findByPk(int|array $pk): IEntity {
        return $this->getDao()->findByPk($pk);
    }

    // Accessible à tous
    function findAll(): array {
        return $this->getDao()->findAll();
    }

    // Création réservée à l’admin
    function insert(IEntity $entity): int {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut créer une catégorie.");
        }
        return $this->getDao()->insert($entity);
    }

    // Modification interdite
    function update(IEntity $entity) {
        throw new HttpStatusException("Modification de catégorie interdite", 403);
    }

    // Suppression réservée à l’admin
    function delete(int|array $pk) {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut supprimer une catégorie.");
        }
        return $this->getDao()->delete($pk);
    }
}
?>