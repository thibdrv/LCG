<?php

require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/Recette.php");
require_once(ROOT . "/daos/RecetteDao.php");
require_once(ROOT . "/services/CompteService.php");

class RecetteService extends AbstractService implements IService
{
    public function __construct() {
        $this->dao = new RecetteDao();
    }

    function getDao(): RecetteDao {
        return $this->dao;
    }

    function findAll(): array {
        if (!isAdmin()) {
            return $this->getDao()->findAllApprouvees();
        }
        return $this->getDao()->findAll();
    }

    function findByPk(int|array $pk): IEntity {
        return $this->getDao()->findByPk($pk);
    }

    function findByCategorie(int $catId): array {
        return $this->getDao()->findByCategorie($catId);
    }

    function insert(IEntity $entity): int {
        /** @var Recette $entity */

        if (!(isUser() || isAdmin())) {
            _403_Forbidden("Seul un utilisateur peut poster une recette.");
        }

        $date = new DateTime();
        $entity->setDateCreation($date);
        $entity->setDateModification($date);
        $entity->setEstApprouve(false);
        $entity->setEstSupprime(false);

        $currentUser = getCurrentUser();
        $entity->setCompte($currentUser);

        return parent::insert($entity);
    }

    function update(IEntity $entity): IEntity {
        if (!($entity instanceof Recette)) {
            throw new InvalidArgumentException("Expected instance of Recette");
        }

        $currentUser = getCurrentUser();
        /** @var Recette $oldEntity */
        $oldEntity = $this->getDao()->findByPk($entity->getPkRecette());

        if (!($oldEntity instanceof Recette)) {
            throw new RuntimeException("Expected Recette from DAO");
        }

        if (!isAdmin() && $oldEntity->getCompte()->getPkCompte() !== $currentUser->getPkCompte()) {
            _403_Forbidden("Vous n'avez pas le droit de modifier cette recette.");
        }

        $oldEntity->setNom($entity->getNom());
        $oldEntity->setIngredients($entity->getIngredients());
        $oldEntity->setDetails($entity->getDetails());
        $oldEntity->setImage($entity->getImage());
        $oldEntity->setLien($entity->getLien());
        $oldEntity->setDateModification(new DateTime());

        return $this->getDao()->update($oldEntity);
    }

    function approve(int $pk, bool $approve = true): void {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut approuver une recette.");
        }
        
        /** @var Recette $recette */
        $recette = $this->getDao()->findByPkAdmin($pk);
        
        if (!($recette instanceof Recette)) {
            throw new RuntimeException("Expected Recette from DAO");
        }
        
        $recette->setEstApprouve($approve);
        $recette->setDateModification(new DateTime());

        $this->getDao()->update($recette);
    }

    function delete(int|array $pk): void {
        if (is_array($pk)) {
            throw new InvalidArgumentException("delete() attend un entier, pas un tableau.");
        }

        $currentUser = getCurrentUser();
        /** @var Recette $recette */
        $recette = $this->getDao()->findByPk($pk);

        if (!($recette instanceof Recette)) {
            throw new RuntimeException("Expected Recette from DAO");
        }

        if (!isAdmin() && $recette->getCompte()->getPkCompte() !== $currentUser->getPkCompte()) {
            _403_Forbidden("Vous n'avez pas le droit de supprimer cette recette.");
        }

        $this->getDao()->delete($pk);
    }
}
?>