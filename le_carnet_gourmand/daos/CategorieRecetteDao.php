<?php

require_once(ROOT . "/entities/IEntity.php");
require_once(ROOT . "/daos/IDao.php");
require_once(ROOT . "/daos/AbstractDao.php");
require_once(ROOT . "/utils/BddSingleton.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/entities/CategorieRecette.php");
require_once(ROOT . "/daos/RecetteDao.php");
require_once(ROOT . "/daos/CategorieDao.php");

class CategorieRecetteDao extends AbstractDao implements IDao
    {
        private CategorieDao $categorieDao;
        private RecetteDao $recetteDao;

        public function __construct() {
            $this->categorieDao = new CategorieDao();
            $this->recetteDao = new RecetteDao();
        }

        function getTableName(): string {
            return "categories_recettes";
        }

        function getPrimaryKeyName(): array {
            return ["fk_recette", "fk_categorie"];
        }

        function createEntityFromRow($row): IEntity {
            $catRecette = CategorieRecette::createFromRow($row);

            if (isset($row->fk_recette)) {
                $recetteDao = new RecetteDao();
                $catRecette->setRecette($recetteDao->findByPk($row->fk_recette));
            }

            if (isset($row->fk_categorie)) {
                $categorieDao = new CategorieDao();
                $catRecette->setCategorie($categorieDao->findByPk($row->fk_categorie));
            }

            return $catRecette;
        }

        // Lecture toutes les recettes et leurs categories
        function findAll(): array {
            $pdo = BddSingleton::getInstance()->getPdo();
            $sql = "SELECT cr.* FROM categories_recettes cr";

            $stmt = $pdo->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute();

            $rows = $stmt->fetchAll();
            $catsRecettes = [];

            foreach ($rows as $row) {
                $catsRecettes[] = $this->createEntityFromRow($row);
            }

            return $catsRecettes;
        }


        function findByPk(int|array $pk): CategorieRecette {
            $fkrecette = BddSingleton::getInstance()->getPdo()->lastInsertId();
            if (!is_array($pk) || !isset($fkrecette, $pk['fk_categorie'])) {
                throw new InvalidArgumentException("CategoriesRecettesDao attend une clé primaire composite : ['fk_recette'=>X, 'fk_categorie'=>Y]");
            }

            $pdo = BddSingleton::getInstance()->getPdo();
            $sql = "SELECT cr.*
                    FROM categories_recettes cr
                    WHERE cr.fk_recette = :fkRecette AND cr.fk_categorie = :fkCategorie
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":fkRecette", $pk['fk_recette'], PDO::PARAM_INT);
            $stmt->bindValue(":fkCategorie", $pk['fk_categorie'], PDO::PARAM_INT);
            $stmt->setFetchMode(PDO::FETCH_OBJ);
            $stmt->execute();
            $row = $stmt->fetch();

            if (!$row) {
                throw new HttpStatusException("Lien recette/catégorie introuvable", 404);
            }

            return $this->createEntityFromRow($row);
        }

    // Liste d'une recette associées à des catégories
    public function findByRecette(int $fkRecette): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT * FROM categories_recettes WHERE fk_recette = :fkRecette";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkRecette", $fkRecette, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->createEntityFromRow($row);
        }
        return $result;
    }

    // Liste d'une catégorie associées à des recettes
    public function findByCategorie(int $fkCategorie): array {
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "SELECT * FROM categories_recettes WHERE fk_categorie = :fkCategorie";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":fkCategorie", $fkCategorie, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->createEntityFromRow($row);
        }
        return $result;
    }


        // function insert(IEntity $entity): int {
        //     /** @var CategoriesRecettes $entity */
        //     $pdo = BddSingleton::getInstance()->getPdo();
        //     $sql = "INSERT INTO categories_recettes (fk_recette, fk_categorie)
        //             VALUES (:fkRecette, :fkCategorie)";

        //     $stmt = $pdo->prepare($sql);
        //     $stmt->bindValue(":fkRecette", $entity->getFkRecette()->getPkRecette(), PDO::PARAM_INT);
        //     $stmt->bindValue(":fkCategorie", $entity->getFkCategorie()->getPkCategorie(), PDO::PARAM_INT);

        //     try {
        //         $stmt->execute();
        //         return 1; // succès (clé composite → pas de lastInsertId)
        //     } catch (PDOException $ex) {
        //         error_log($ex->getMessage());
        //         throw new HttpStatusException($ex->getMessage(), 500, $ex);
        //     }
        // }

    function insert(IEntity $entity): int {
        /**  @var CategorieRecette  $entity */
        $pdo = BddSingleton::getInstance()->getPdo();
        $sql = "INSERT INTO categories_recettes (fk_recette, fk_categorie)
                VALUES (:fk_recette, :fk_categorie)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'fk_recette'   => $entity->getRecette()->getPkRecette(),
            'fk_categorie' => $entity->getCategorie()->getPkCategorie()
        ]);

        return $stmt->rowCount();
    }


        function update(IEntity $entity): IEntity {
            throw new Exception("Update non pertinent pour une table de liaison (categories_recettes).");
        }

        function delete(int|array $pk) {
            if (!is_array($pk) || !isset($pk['fk_recette'], $pk['fk_categorie'])) {
                throw new InvalidArgumentException("CategoriesRecettesDao attend une clé composite : ['fk_recette'=>X, 'fk_categorie'=>Y]");
            }

            $pdo = BddSingleton::getInstance()->getPdo();
            $sql = "DELETE FROM categories_recettes WHERE fk_recette = :fkRecette AND fk_categorie = :fkCategorie";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":fkRecette", $pk['fk_recette'], PDO::PARAM_INT);
            $stmt->bindValue(":fkCategorie", $pk['fk_categorie'], PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new HttpStatusException("Erreur lors de la suppression du lien recette/catégorie", 500);
            }
        }
    }
?>