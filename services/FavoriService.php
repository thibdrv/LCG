<?php

require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/Favori.php");
require_once(ROOT . "/daos/FavoriDao.php");
require_once(ROOT . "/services/RecetteService.php");
require_once(ROOT . "/services/CompteService.php");

class FavoriService extends AbstractService implements IService
{
    private RecetteService $recetteService;
    private CompteService $compteService;

    public function __construct() {
        $this->dao = new FavoriDao();
        $this->recetteService = new RecetteService();
        $this->compteService = new CompteService();
    }

    function getDao(): FavoriDao {
        return $this->dao;
    }

    function findAll(): array {
        throw new HttpStatusException("Méthode non disponible pour les favoris", 403);
    }

    function findByPk(int|array $pk): IEntity {
        if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
            throw new InvalidArgumentException("findByPk attend une clé composite ['fk_compte'=>X, 'fk_recette'=>Y]");
        }

        $currentUserPk = getComptePkFromSession();
        if ($pk['fk_compte'] !== $currentUserPk && !isAdmin()) {
            _403_Forbidden("Vous ne pouvez accéder qu'à vos propres favoris.");
        }

        return $this->getDao()->findByPk($pk);
    }

    function findByCompte(int $fkCompte): array {
        $currentUserPk = getComptePkFromSession();
        if ($fkCompte !== $currentUserPk && !isAdmin()) {
            _403_Forbidden("Vous ne pouvez voir que vos propres favoris.");
        }

        return $this->getDao()->findByCompte($fkCompte);
    }

    function insert(IEntity $entity): int {
        /** @var Favori $entity */
        $currentUser = getCurrentUser();
        $entity->setCompte($currentUser);

        $fkCompte = $entity->getCompte()->getPkCompte();
        $fkRecette = $entity->getRecette()->getPkRecette();

        // Vérifier que la recette existe
        $this->recetteService->findByPk($fkRecette);

        if ($this->getDao()->exists($fkCompte, $fkRecette)) {
            throw new HttpStatusException("Vous avez déjà ajouté cette recette en favori.", 409);
        }

        return parent::insert($entity);
    }

    function update(IEntity $entity): IEntity {
        throw new HttpStatusException("Modification de favori interdite", 403);
    }

    function delete(int|array $pk): void {
        if (!is_array($pk) || !isset($pk['fk_recette'])) {
            throw new InvalidArgumentException("delete() attend ['fk_recette'=>Y]");
        }

        $currentUserPk = getComptePkFromSession();

        $compositePk = [
            'fk_compte'  => $currentUserPk,
            'fk_recette' => $pk['fk_recette']
        ];

        $this->getDao()->delete($compositePk);
    }
}
?>