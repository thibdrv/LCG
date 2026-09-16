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
    private RoleService $roleService;

    public function __construct() {
        $this->dao = new CompteDao();
        $this->roleService = new RoleService();
    }

    function isValidCredential(Compte $compte): ?int {
        return $this->dao->isValidCredential($compte);
    }

    function getDao(): CompteDao {
        return $this->dao;
    }

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
            return $compte;
        }

        if ($pk !== $currentUserPk) {
            _403_Forbidden("Vous n'avez pas le droit de voir le compte d'un autre utilisateur.");
        }

        if ($compte->getEstSupprime() || $compte->getEstBanni()) {
            throw new HttpStatusException("Ce compte est désactivé", 403);
        }

        return $compte;
    }

    function insert(IEntity $entity): int {
        /** @var Compte $entity */
        $role = $this->roleService->findByPk(1);
        $entity->setRole($role);
        $entity->setDateCreation(new DateTime());
        $entity->setEstSupprime(false);
        $entity->setEstBanni(false);

        return parent::insert($entity);
    }

    function update(IEntity $entity): IEntity {
        if (!($entity instanceof Compte)) {
            throw new InvalidArgumentException("Expected instance of Compte");
        }

        $currentUser = $this->getDao()->findByPk(getComptePkFromSession());
        /** @var Compte $currentUser */

        if (isUser() && $entity->getPkCompte() == $currentUser->getPkCompte()) {
            $currentUser->setMotDePasse($entity->getMotDePasse());
            return $this->getDao()->update($currentUser);
        }

        if (isAdmin()) {
            $oldEntity = $this->getDao()->findByPk($entity->getPkCompte());
            /** @var Compte $oldEntity */

            $oldEntity->setEstBanni($entity->getEstBanni());

            if ($entity->getPkCompte() == $currentUser->getPkCompte()) {
                $oldEntity->setMotDePasse($entity->getMotDePasse());
            }

            return $this->getDao()->update($oldEntity);
        }

        _403_Forbidden("Vous n'avez pas le droit de modifier ce compte.");
        // Ne devrait jamais arriver, mais PHP nécessite un return
        throw new RuntimeException("Unexpected state");
    }

    function delete(int|array $pk): void {
        if (is_array($pk)) {
            throw new InvalidArgumentException("delete() attend un entier, pas un tableau.");
        }

        $currentUser = getCurrentUser();
        $targetCompte = $this->getDao()->findByPk($pk);

        if (!isAdmin() && $currentUser->getPkCompte() !== $targetCompte->getPkCompte()) {
            _403_Forbidden("Vous n'avez pas le droit de supprimer ce compte.");
        }

        $this->getDao()->delete($pk);
    }

    function restore(int|array $pk): void {
        if (is_array($pk)) {
            throw new InvalidArgumentException("restore() attend un entier, pas un tableau.");
        }

        $currentUser = getCurrentUser();
        if (!isAdmin()) {
            _403_Forbidden("Seul un administrateur peut restaurer un compte.");
        }

        $this->getDao()->restore($pk);
    }
}
?>