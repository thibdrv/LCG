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
    private CompteService $compteService;
    private RecetteService $recetteService;

    public function __construct() {
        $this->dao = new CommentaireDao();
        $this->compteService = new CompteService();
        $this->recetteService = new RecetteService();
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

    function findByPk(int|array $pk): IEntity {
        /** @var Commentaire $commentaire */
        $commentaire = $this->getDao()->findByPk($pk);

        if (!$commentaire) {
            throw new HttpStatusException("Commentaire introuvable", 404);
        }

        if (!isAdmin() && !$commentaire->getEstApprouve()) {
            throw new HttpStatusException("Commentaire non approuvé", 403);
        }

        if ($commentaire->getEstSupprime()) {
            $commentaire->setCompte(null);
        } else {
            $compteEntity = $commentaire->getCompte();
            if ($compteEntity !== null) {
                try {
                    $compte = $this->compteService->findByPk($compteEntity->getPkCompte());
                    $commentaire->setCompte($compte);
                } catch (HttpStatusException $e) {
                    // Si le compte est supprimé ou banni, on anonymise
                    $commentaire->setCompte(null);
                }
            }
        }

        return $commentaire;
    }

    function findByRecette(int $pkRecette): array {
        // Vérifier que la recette existe
        try {
            $this->recetteService->findByPk($pkRecette);
        } catch (HttpStatusException $e) {
            throw new HttpStatusException("La recette n'existe pas", 404);
        }

        $commentaires = $this->getDao()->findByRecette($pkRecette);

        foreach ($commentaires as $commentaire) {
            if ($commentaire->getEstSupprime()) {
                $commentaire->setCompte(null);
            } else {
                $compteEntity = $commentaire->getCompte();
                if ($compteEntity !== null) {
                    try {
                        $compte = $this->compteService->findByPk($compteEntity->getPkCompte());
                        $commentaire->setCompte($compte);
                    } catch (HttpStatusException $e) {
                        $commentaire->setCompte(null);
                    }
                }
            }
        }

        return $commentaires;
    }

    function insert(IEntity $entity): int {
        if (!isUser() && !isAdmin()) {
            _403_Forbidden("Seuls les utilisateurs connectés peuvent poster un commentaire.");
        }

        /** @var Commentaire $entity */
        $entity->setDateCreation(new DateTime());
        $entity->setEstApprouve(false);
        $entity->setEstSupprime(false);
        
        $currentUser = getCurrentUser();
        $entity->setCompte($currentUser);

        return parent::insert($entity);
    }

    function update(IEntity $entity): IEntity {
        /** @var Commentaire $entity */
        $oldEntity = $this->getDao()->findByPk([
            'fk_compte'  => $entity->getCompte()->getPkCompte(),
            'fk_recette' => $entity->getRecette()->getPkRecette()
        ]);

        if (!($oldEntity instanceof Commentaire)) {
            throw new InvalidArgumentException("Expected Commentaire, got " . get_class($oldEntity));
        }

        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut approuver ou refuser un commentaire.");
        }

        $oldEntity->setEstApprouve($entity->getEstApprouve());
        return $this->getDao()->update($oldEntity);
    }

    function delete(int|array $pk): void {
        if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
            throw new InvalidArgumentException("delete() attend une clé composite : ['fk_compte'=>X, 'fk_recette'=>Y]");
        }

        /** @var Commentaire $commentaire */
        $commentaire = $this->getDao()->findByPk($pk);
        $currentUser = getCurrentUser();

        if ($commentaire->getCompte()->getPkCompte() !== $currentUser->getPkCompte() && !isAdmin()) {
            _403_Forbidden("Vous n'avez pas le droit de supprimer ce commentaire.");
        }

        $this->getDao()->delete($pk);
    }
}
?>