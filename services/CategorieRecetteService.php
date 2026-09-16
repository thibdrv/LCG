<?php

require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/CategorieRecette.php");
require_once(ROOT . "/daos/CategorieRecetteDao.php");
require_once(ROOT . "/services/RecetteService.php");
require_once(ROOT . "/services/CategorieService.php");

class CategorieRecetteService extends AbstractService implements IService
{
    private RecetteService $recetteService;
    private CategorieService $categorieService;

    public function __construct() {
        $this->dao = new CategorieRecetteDao();
        $this->recetteService = new RecetteService();
        $this->categorieService = new CategorieService();
    }

    function getDao(): CategorieRecetteDao {
        return $this->dao;
    }

    function findAll(): array {
        return $this->getDao()->findAll();
    }

    function findByPk(int|array $pk): IEntity {
        return $this->getDao()->findByPk($pk);
    }
    
    function findByRecette(int $fkRecette): array {
        return $this->getDao()->findByRecette($fkRecette);
    }

    function findByCategorie(int $fkCategorie): array {
        return $this->getDao()->findByCategorie($fkCategorie);
    }

    function insert(IEntity $entity): int {
        $currentUser = getCurrentUser();
        /** @var CategorieRecette $entity */
        $recette = $entity->getRecette();

        // Vérifie que la recette existe
        $recetteComplete = $this->recetteService->findByPk($recette->getPkRecette());
        
        // Vérifie que le user est bien l'auteur
        if (!isAdmin() && $recetteComplete->getCompte()->getPkCompte() !== $currentUser->getPkCompte()) {
            _403_Forbidden("Vous ne pouvez pas modifier les catégories d'une autre recette.");
        }

        return $this->getDao()->insert($entity);
    }

    function delete(int|array $pk): void {
        if (!is_array($pk) || !isset($pk['fk_recette'], $pk['fk_categorie'])) {
            throw new InvalidArgumentException("delete() attend ['fk_recette'=>X, 'fk_categorie'=>Y]");
        }

        $currentUser = getCurrentUser();
        /** @var Recette $recette */
        $recette = $this->recetteService->findByPk($pk['fk_recette']);

        if (!isAdmin() && $recette->getCompte()->getPkCompte() !== $currentUser->getPkCompte()) {
            _403_Forbidden("Vous ne pouvez pas modifier les catégories d'une autre recette.");
        }
        $this->getDao()->delete($pk);
    }

    function update(IEntity $entity): IEntity {
        throw new HttpStatusException("Modification interdite sur la liaison catégorie-recette", 403);
    }
}
?>