<?php

require_once(ROOT . "/services/IService.php");
require_once(ROOT . "/services/AbstractService.php");
require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/daos/CompteDao.php");
require_once(ROOT . "/services/RoleService.php");

class CompteService extends AbstractService implements IService
{
    protected CompteDao $dao;
    protected RoleService $roleService;

    public function __construct()
    {
        $this->dao = new CompteDao();
        $this->roleService = new RoleService();
    }

    // --- AUTH ---
    function isValidCredential(Compte $compte): ?int
    {
        return $this->dao->isValidCredential($compte);
    }

    // --- DAO ACCESS ---
    function getDao(): CompteDao {
        return $this->dao;
    }

    // --- FIND ---
    function findByPkForSession(int $pk): Compte {
        return $this->getDao()->findByPk($pk);
    }

    function findByPkForLogin(int $pk): IEntity {
        return $this->getDao()->findByPk($pk);
    }

    function findAll(): array {
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut voir tous les comptes.");
        }
        return $this->getDao()->findAll();
    }

    function findByPk(int|array $pk): Compte {
        if (is_array($pk)) {
            throw new InvalidArgumentException("findByPk attend un entier");
        }

        $currentUserPk = getComptePkFromSession();
        $compte = $this->getDao()->findByPk($pk);

        if (isAdmin()) {
            // ✅ Admin peut tout voir, même comptes bannis/supprimés
            return $compte;
        }

        // 🔒 Cas utilisateur simple
        if ($pk !== $currentUserPk) {
            _403_Forbidden("Vous n'avez pas le droit de voir le compte d'un autre utilisateur.");
        }

        if ($compte->getEstSupprime() || $compte->getEstBanni()) {
            throw new HttpStatusException("Ce compte est désactivé", 403);
        }

        return $compte;
    }


    // --- INSERT ---
    function insert(IEntity $entity): int
    {
        /** @var Compte $entity */ 
        // rôle utilisateur par défaut
        $role = $this->roleService->findByPk(1); // 1 = user
        $entity->setRole($role);

        // Forcer les valeurs serveur
        $entity->setDateCreation(new DateTime());
        $entity->setEstSupprime(false);
        $entity->setEstBanni(false);

        return parent::insert($entity);
    }

    // --- UPDATE ---
    function update(IEntity $entity) {
        if (!($entity instanceof Compte)) {
            throw new InvalidArgumentException("Expected instance of Compte");
        }

        $currentUser = $this->getDao()->findByPk(getComptePkFromSession());
        /** @var Compte $currentUser */

        // Un user peut modifier uniquement son mot de passe
        if (isUser() && $entity->getPkCompte() == $currentUser->getPkCompte()) {
            $currentUser->setMotDePasse($entity->getMotDePasse());
            return $this->getDao()->update($currentUser);
        }

        // Un admin peut tout modifier
        if (isAdmin()) {
            $oldEntity = $this->getDao()->findByPk($entity->getPkCompte());
            /** @var Compte $oldEntity */

            $oldEntity->setEstBanni($entity->getEstBanni());

            // Admin peut aussi changer son mot de passe
            if ($entity->getPkCompte() == $currentUser->getPkCompte()) {
                $oldEntity->setMotDePasse($entity->getMotDePasse());
            }

            return $this->getDao()->update($oldEntity);
        }

        _403_Forbidden("Vous n'avez pas le droit de modifier ce compte.");
    }

    // --- DELETE ---
    function delete(int|array $pk): void {
        if (is_array($pk)) {
            throw new InvalidArgumentException("delete() attend un entier, pas un tableau.");
        }

        $currentUser = getCurrentUser();       // utilisateur connecté
        $targetCompte = $this->getDao()->findByPk($pk); // compte ciblé

        // Vérifier droits
        if (
            $currentUser->getRole()->getPkRole() != 2 // pas admin
            && $currentUser->getPkCompte() !== $targetCompte->getPkCompte() // pas propriétaire
        ) {
            _403_Forbidden("Vous n'avez pas le droit de supprimer ce compte.");
        }

        $this->getDao()->delete($pk);
    }

    // --- RESTORE ---
    function restore(int|array $pk): void {
        if (is_array($pk)) {
            throw new InvalidArgumentException("restore() attend un entier, pas un tableau.");
        }

        $currentUser = getCurrentUser();
        if ($currentUser->getRole()->getPkRole() != 2) {
            _403_Forbidden("Seul un administrateur peut restaurer un compte.");
        }

        $this->getDao()->restore($pk);
    }
}
?>