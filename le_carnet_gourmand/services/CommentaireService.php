<?php

require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/Commentaire.php");
require_once(ROOT . "/daos/CommentaireDao.php");
require_once(ROOT . "/services/CompteService.php");
require_once(ROOT . "/services/RecetteService.php");


class CommentaireService extends AbstractService implements IService
{
    protected CommentaireDao $dao;
    protected CompteDao $compteDao;

    public function __construct()
    {
        $this->dao = new CommentaireDao();
        $this->compteDao = new CompteDao();
    }

    function getDao(): CommentaireDao {
        return $this->dao;
    }

    function findAll(): array {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut voir tous les commentaires.");
        }

        return $this->getDao()->findAll();
    }

    // Un commentaire par sa pk
    function findByPk(int|array $pk): IEntity {
        /** @var Commentaire|null $commentaire */
        $commentaire = $this->getDao()->findByPk($pk);

        if (!$commentaire) {
            throw new HttpStatusException("Commentaire introuvable", 404);
        }

        // 🔎 Logique métier : si ce n’est pas un admin
        // on empêche l’accès aux commentaires non approuvés
        if (!isAdmin() && !$commentaire->getEstApprouve()) {
            throw new HttpStatusException("Commentaire non approuvé", 403);
        }

        // 🔎 Si le commentaire est marqué supprimé → anonymiser
        if ($commentaire->getEstSupprime()) {
            $commentaire->setCompte(null);
        } else {
            $compteEntity = $commentaire->getCompte();
            if ($compteEntity !== null) {
                $compte = $this->compteDao->findByPk($compteEntity->getPkCompte());
                $commentaire->setCompte($compte);
            }
        }

        return $commentaire;
    }

    // Tous les commentaires d'une recette (accessible même sans être connecté)
    function findByRecette(int $pkRecette): array {

        // Vérifier que la recette existe via le DAO
        if (!$this->getDao()->recetteExists($pkRecette)) {
        throw new HttpStatusException("La recette n'existe pas", 404);
        }
        $commentaires = $this->getDao()->findByRecette($pkRecette);

            // Pour chaque commentaire, on récupère le pseudo du compte
            foreach ($commentaires as $commentaire) {
        if ($commentaire->getEstSupprime()) {
            $commentaire->setCompte(null); // ou un compte "anonyme"
        } else {
            $compte = $this->compteDao->findByPk($commentaire->getCompte()->getPkCompte());
            $commentaire->setCompte($compte);
        }
    }

    return $commentaires;
    }

    // Insertion d’un commentaire
    function insert(IEntity $entity): int {
        if (!isUser() && !isAdmin()) {
            _403_Forbidden("Seuls les utilisateurs connectés peuvent poster un commentaire.");
        }

        /** @var Commentaire $entity */
        $entity->setDateCreation(new DateTime());
        $entity->setEstApprouve(false); // Par défaut, en attente d’approbation admin
        $entity->setEstSupprime(false);
        $compteDao = new CompteDao();
        $compte = $compteDao->findByPk(getComptePkFromSession());
        $entity->setCompte($compte);

        return parent::insert($entity);
    }

    function update(IEntity $entity) {

        /** @var Commentaire $entity */
        $oldEntity = $this->getDao()->findByPk([
            'fk_compte'  => $entity->getCompte()->getPkCompte(),
            'fk_recette' => $entity->getRecette()->getPkRecette()
        ]);

        if (!($oldEntity instanceof Commentaire)) {
            throw new InvalidArgumentException("Expected Commentaire, got " . get_class($oldEntity));
        }

        // ✅ Seul un admin peut approuver/désapprouver
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut approuver ou refuser un commentaire.");
        }

        $oldEntity->setEstApprouve($entity->getEstApprouve());
        return $this->getDao()->update($oldEntity);
    }



    // Suppression : auteur OU admin
    function delete(int|array $pk): void {
        if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
            throw new InvalidArgumentException("delete() attend une clé composite : ['fk_compte'=>X, 'fk_recette'=>Y]");
        }

        /** @var Commentaire $commentaire */
        $commentaire = $this->getDao()->findByPk($pk);
        $currentUser = getCurrentUser();

        // Vérification des droits
        if ($commentaire->getCompte()->getPkCompte() !== $currentUser->getPkCompte() && !isAdmin()) {
            _403_Forbidden("Vous n'avez pas le droit de supprimer ce commentaire.");
        }

        // Soft delete via DAO
        $this->getDao()->delete($pk);
    }
}
?>