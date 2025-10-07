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
    protected RecetteDao $dao;

    public function __construct()
    {
        $this->dao = new RecetteDao();
    }

    function getDao(): RecetteDao {
        return $this->dao;
    }

    function findAll(): array {
        if (!isAdmin()) {
            return $this->getDao()->findAllApp();
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
            // Si non autorisé, la fonction renvoie une erreur 403 (Accès interdit).
            _403_Forbidden("Seul un utilisateur peut poster une recette.");
        }

        // Définition automatique des attributs par défaut
        $date = new DateTime();
        $entity->setDateCreation($date);       // Date et heure de création de la recette
        $entity->setDateModification($date);
        $entity->setEstApprouve(false);        // La recette n’est pas encore approuvée par un modérateur
        $entity->setEstSupprime(false);        // La recette n’est pas supprimée (état actif)

        // Association de la recette à l’auteur actuellement connecté
        $currentUser = getCurrentUser();       // Récupère l’utilisateur en session
        $entity->setCompte($currentUser);      // Attribue cet utilisateur comme auteur de la recette

        // Insertion de la recette dans la base via la méthode de la classe parente
        $recettePk = parent::insert($entity);  // Retourne la clé primaire (ID) générée par la base

        return $recettePk;                     // Renvoie l’identifiant de la recette nouvellement créée
    }



    function update(IEntity $entity): IEntity {
        if (!($entity instanceof Recette)) {
            throw new InvalidArgumentException("Expected instance of Recette");
        }

        $currentUser = getCurrentUser();
        /** @var Recette $oldEntity */
        $oldEntity = $this->getDao()->findByPk($entity->getPkRecette());
        // $objet est bien un objet de type MaClasse
        if (!($oldEntity instanceof Recette)) {
        throw new RuntimeException("Expected Recette from DAO");
        }

        // Vérifier droits : auteur OU admin
        if (!isAdmin() && $oldEntity->getCompte()->getPkCompte() !== $currentUser->getPkCompte()) {
            _403_Forbidden("Vous n'avez pas le droit de modifier cette recette.");
        }

        // Mise à jour
        $oldEntity->setNom($entity->getNom());
        $oldEntity->setIngredients($entity->getIngredients());
        $oldEntity->setDetails($entity->getDetails());
        $oldEntity->setImage($entity->getImage());
        $oldEntity->setLien($entity->getLien());
        $oldEntity->setDateModification(new DateTime());

        return $this->getDao()->update($oldEntity);
    }

    // Approuve by ADMIN (UPDATE)
    function approve(int $pk, bool $approve = true): void {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut approuver une recette.");
        }
        /** @var Recette $recette */
        $recette = $this->getDao()->findByPkAdmin($pk);
        // $objet est bien un objet de type MaClasse
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
        $recette = $this->getDao()->findByPk($pk); // ✅ Charger la recette depuis la BDD

        if (!($recette instanceof Recette)) {
            throw new RuntimeException("Expected Recette from DAO");
        }

        // Vérifie droits : admin ou auteur
        if (!isAdmin() && $recette->getCompte()->getPkCompte() !== $currentUser->getPkCompte()) {
            _403_Forbidden("Vous n'avez pas le droit de supprimer cette recette.");
        }

        // Soft delete (est_supprime = 1)
        $this->getDao()->delete($pk);
    }

}