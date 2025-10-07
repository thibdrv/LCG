<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/utils/BddSingleton.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/daos/AbstractDao.php");
require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/entities/Commentaire.php");
require_once(ROOT . "/daos/CompteDao.php");
require_once(ROOT . "/daos/RecetteDao.php");

class CommentaireDao extends AbstractDao implements IDao
    {
        private CompteDao $compteDao;
        private RecetteDao $recetteDao;

        public function __construct() {
        $this->compteDao = new CompteDao();
        $this->recetteDao = new RecetteDao();
    }

        function getTableName(): string {
            return "ecrire_commentaire";
        }

        function getPrimaryKeyName(): array {
            return ["fk_compte", "fk_recette"];
        }

        function createEntityFromRow($row): IEntity {
            $commentaire = Commentaire::createFromRow($row);

            if (isset($row->fk_compte)) {
                $compteDao = new CompteDao();
                $commentaire->setCompte($compteDao->findByPk($row->fk_compte));
            }

            if (isset($row->fk_recette)) {
                $recetteDao = new RecetteDao();
                $commentaire->setRecette($recetteDao->findByPk($row->fk_recette));
            }

            return $commentaire;
        }

        function findAll(): array {
            $pdo = BddSingleton::getInstance()->getPdo();
            $sql = "SELECT ec.*
            FROM ecrire_commentaire ec
            WHERE est_supprime = 0";

            $stmt = $pdo->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute();

            $rows = $stmt->fetchAll();
            $commentaires = [];

            foreach ($rows as $row) {
                $commentaires[] = $this->createEntityFromRow($row);
            }

            return $commentaires;
        }

        function findByPk(int|array $pk): IEntity {
            if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
                throw new InvalidArgumentException("CommentaireDao attend une clé primaire composite : ['fk_compte'=>X, 'fk_recette'=>Y]");
            }

            $pdo = BddSingleton::getInstance()->getPdo();
            $sql = "SELECT ec.*
                    FROM ecrire_commentaire ec
                    WHERE ec.fk_compte = :fkCompte AND ec.fk_recette = :fkRecette AND est_supprime = 0";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":fkCompte", $pk['fk_compte'], PDO::PARAM_INT);
            $stmt->bindValue(":fkRecette", $pk['fk_recette'], PDO::PARAM_INT);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute();
            $row = $stmt->fetch();

            if (!$row) {
                throw new HttpStatusException("Commentaire introuvable pour ce couple compte/recette", 404);
            }

            return $this->createEntityFromRow($row);
        }

        function findByRecette(int $pk): array {
            $pdo = BddSingleton::getInstance()->getPdo();

            $sql = "SELECT ec.* 
                    FROM ecrire_commentaire ec
                    WHERE ec.fk_recette = :fkRecette 
                    AND ec.est_supprime = 0 
                    ORDER BY ec.date_creation DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":fkRecette", $pk, PDO::PARAM_INT);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute();

            $rows = $stmt->fetchAll();
            $commentaires = [];

            foreach ($rows as $row) {
                $commentaires[] = $this->createEntityFromRow($row);
            }

            return $commentaires;
        }

        function insert(IEntity $entity): int {
            /** @var Commentaire $entity */
            $pdo = BddSingleton::getInstance()->getPdo();
            $sql = "INSERT INTO ecrire_commentaire 
                    (fk_compte, fk_recette, contenu, date_creation, est_approuve, est_supprime)
                    VALUES (:fkCompte, :fkRecette, :contenu, :dCreation, :estApprouve, :estSupprime)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":fkCompte", $entity->getCompte()->getPkCompte(), PDO::PARAM_INT);
            $stmt->bindValue(":fkRecette", $entity->getRecette()->getPkRecette(), PDO::PARAM_INT);
            $stmt->bindValue(":contenu", $entity->getContenu());
            $stmt->bindValue(":dCreation", $entity->getDateCreation()->format(MYSQL_DATE_FORMAT));
            $stmt->bindValue(":estApprouve", $entity->getEstApprouve(), PDO::PARAM_BOOL);
            $stmt->bindValue(":estSupprime", $entity->getEstSupprime(), PDO::PARAM_BOOL);

            try {
                $stmt->execute();
                return 1; // succès (pas de lastInsertId sur clé composite)
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                throw new HttpStatusException($ex->getMessage(), 500, $ex);
            }
        }

    function update(IEntity $entity): IEntity {
        if (!($entity instanceof Commentaire)) {
            throw new InvalidArgumentException("Expected instance of Commentaire");
        }

        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "UPDATE ecrire_commentaire
                SET est_approuve = :estApprouve
                WHERE fk_compte = :fkCompte AND fk_recette = :fkRecette";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCompte", $entity->getCompte()->getPkCompte(), PDO::PARAM_INT);
        $stmt->bindValue(":fkRecette", $entity->getRecette()->getPkRecette(), PDO::PARAM_INT);
        $stmt->bindValue(":estApprouve", $entity->getEstApprouve(), PDO::PARAM_BOOL);

        try {
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la mise à jour du commentaire");
            }
            return $this->findByPk([
                "fk_compte"  => $entity->getCompte()->getPkCompte(),
                "fk_recette" => $entity->getRecette()->getPkRecette()
            ]);
        } catch (PDOException $ex) {
            error_log("Erreur SQL Update Commentaire : " . $ex->getMessage());
            throw new HttpStatusException("Erreur lors de la mise à jour", 500, $ex);
        }
    }



        function delete(int|array $pk) {
            if (!is_array($pk) || !isset($pk['fk_compte'], $pk['fk_recette'])) {
                throw new InvalidArgumentException("CommentaireDao attend une clé composite : ['fk_compte'=>X, 'fk_recette'=>Y]");
            }

            $pdo = BddSingleton::getInstance()->getPdo();
            // Ici je fais un soft delete (marquer est_supprime à 1)
            $sql = "UPDATE ecrire_commentaire 
                    SET est_supprime = 1
                    WHERE fk_compte = :fkCompte AND fk_recette = :fkRecette";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":fkCompte", $pk['fk_compte'], PDO::PARAM_INT);
            $stmt->bindValue(":fkRecette", $pk['fk_recette'], PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new HttpStatusException("Erreur lors de la suppression du commentaire", 500);
            }
        }

        function recetteExists(int $pk): bool {
            $pdo = BddSingleton::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM recettes WHERE pk_recette = :id");
            $stmt->bindValue(":id", $pk, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        }

    }
?>