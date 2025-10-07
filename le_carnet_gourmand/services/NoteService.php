<?php

require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/Note.php");
require_once(ROOT . "/daos/NoteDao.php");
require_once(ROOT . "/services/CompteService.php");
require_once(ROOT . "/services/RecetteService.php");


class NoteService extends AbstractService implements IService
{
    protected NoteDao $dao;
    protected CompteService $compteService;
    protected RecetteService $recetteService;

    public function __construct()
    {
        $this->dao = new NoteDao();
        $this->compteService = new CompteService();
        $this->recetteService = new RecetteService();
    }

    function getDao(): NoteDao {
        return $this->dao;
    }

    /**
     * Validation de la note (0 à 5 par pas de 0.5)
     */
    private function validateNote(float $note): void {
        if ($note < 0 || $note > 5) {
            throw new HttpStatusException("La note doit être comprise entre 0 et 5", 400);
        }
        if (fmod($note, 0.5) !== 0.0) {
            throw new HttpStatusException("La note doit être un multiple de 0.5", 400);
        }
    }


    function MoyenneParRecette(int $pkRecette): float {
        // Tout le monde peut consulter la moyenne (même non connecté)
        return $this->getDao()->MoyenneParRecette($pkRecette);
    }
    function totalNoteParRecette(int $pkRecette): int {
        // Tout le monde peut consulter le nombre de notes d’une recette
        return $this->getDao()->totalNoteParRecette($pkRecette);
    }

    function findByCompte(int $fkCompte): array {
        $currentUserPk = getComptePkFromSession();
        if ($fkCompte !== $currentUserPk && !isAdmin()) {
            _403_Forbidden("Vous ne pouvez voir que vos propres notes.");
        }

        return $this->getDao()->findByCompte($fkCompte);
    }

    function findAll(): array {
        throw new HttpStatusException("find all not implement", 403);
    }
    function findByPk(int|array $pk): IEntity {
        // Récupérer une note précise (par compte + recette)
        if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
            throw new InvalidArgumentException("NoteService attend une clé composite ['fk_compte'=>X, 'fk_recette'=>Y]");
        }

        $currentUserPk = getComptePkFromSession();

        // L'utilisateur ne peut voir que sa propre note, sauf si admin
        if ($pk['fk_compte'] !== $currentUserPk && !isAdmin()) {
            _403_Forbidden("Vous n'avez pas le droit de voir la note d'un autre utilisateur.");
        }

        return $this->getDao()->findByPk($pk);
    }


    function insert(IEntity $entity): int {

        /** @var Note $entity */
        $this->validateNote($entity->getNote());
        $compte = getCurrentUser();
        $entity->setCompte($compte);

        // 1️⃣ Vérifier si la recette existe
        $recette = $this->recetteService->findByPk($entity->getRecette()->getPkRecette());
        if (!$recette) {
            throw new HttpStatusException("La recette que vous essayez de noter n'existe pas.", 404);
        }

        // 2️⃣ Vérifier si une note existe déjà
        try {
            $this->getDao()->findByPk([
                'fk_compte'  => $compte->getPkCompte(),
                'fk_recette' => $entity->getRecette()->getPkRecette()
            ]);
            // si trouvé → c'est un doublon
            throw new HttpStatusException("Vous avez déjà noté cette recette.", 400);

        } catch (HttpStatusException $e) {
            if ($e->getCode() !== 404) { 
                // si ce n’est PAS "note introuvable", alors erreur réelle
                throw $e;
            }
            // si c'est bien 404 → pas encore noté, on continue
        }

        // 3️⃣ Insertion en BDD
        return parent::insert($entity);
    }
    

    function update(IEntity $entity) {
        throw new Exception("Modification de note interdite");
    }

    /**
     * Supprimer une note (DELETE)
     */
    function delete(int|array $pk): void {
        if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
            throw new InvalidArgumentException("delete() attend une clé composite ['fk_compte'=>X, 'fk_recette'=>Y]");
        }

        $currentUserPk = getComptePkFromSession();
        if ($pk['fk_compte'] !== $currentUserPk && !isAdmin()) {
            _403_Forbidden("Vous ne pouvez supprimer que vos propres notes.");
        }

        $this->getDao()->delete($pk);
    }

    public function dejaNote(int $fkCompte, int $pkRecette): bool {
        $note = $this->getDao()->findByPk([
            'fk_compte'  => $fkCompte,
            'fk_recette' => $pkRecette
        ]);
        return $note !== null;
    }

}
?>