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
    protected CategorieRecetteDao $dao;
    protected RecetteDao $recetteDao;

    public function __construct()
    {
        $this->dao = new CategorieRecetteDao();
        $this->recetteDao = new RecetteDao();
    }

    function getDao(): CategorieRecetteDao {
        return $this->dao;
    }

    // Lecture toutes les recettes et leurs categories
    function findAll(): array {
        return $this->getDao()->findAll();
    }

    function findByPk(int|array $pk): IEntity {
        return $this->getDao()->findByPk($pk);
    }
    
    // Liste d'une recette associées à des catégories
    function findByRecette(int $fkRecette): array {
        return $this->getDao()->findByRecette($fkRecette);
    }

    // Liste d'une catégorie associées à des recettes
    function findByCategorie(int $fkCategorie): array {
        return $this->getDao()->findByCategorie($fkCategorie);
    }


    // Associer une catégorie à une recette
function insert(IEntity $entity): int {
    $currentUser = getCurrentUser();
    /** @var CategorieRecette $entity */
    // Ici, plus besoin de recharger la recette :
    $recette = $entity->getRecette();

    // Vérifie que le user est bien l'auteur
    if (!isAdmin() && $recette->getCompte()->getPkCompte() !== $currentUser->getPkCompte()) {
        _403_Forbidden("Vous ne pouvez pas modifier les catégories d'une autre recette.");
    }

    return $this->getDao()->insert($entity);
}


    // Supprimer une catégorie d'une recette
    function delete(int|array $pk): void {
        if (!is_array($pk) || !isset($pk['fk_recette'], $pk['fk_categorie'])) {
            throw new InvalidArgumentException("delete() attend ['fk_recette'=>X, 'fk_categorie'=>Y]");
        }

        $currentUser = getCurrentUser();
        /** @var Recette $recette */
        $recette = $this->recetteDao->findByPk($pk['fk_recette']);

        // Vérifie que le user est l'auteur ou un admin
        if (!isAdmin() && $recette->getCompte()->getPkCompte() !== $currentUser->getPkCompte()) {
            _403_Forbidden("Vous ne pouvez pas modifier les catégories d'une autre recette.");
        }
        $this->getDao()->delete($pk);
    }

    // Update interdit
    function update(IEntity $entity) {
        throw new HttpStatusException("Modification interdite sur la liaison catégorie-recette", 403);
    }
}
?>