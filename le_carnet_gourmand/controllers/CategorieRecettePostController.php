<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/CategorieRecette.php");
require_once(ROOT . "/services/CategorieRecetteService.php");


class CategorieRecettePostController extends AbstractController implements IController
{
    private CategorieRecetteService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new CategorieRecetteService();
    }


    // verifie si existe
    protected function checkForm() {
        if (!isset($this->form['fk_recette'], $this->form['fk_categorie'])) {
            throw new HttpStatusException("fk_recette et fk_categorie sont obligatoires", 400);
        }
    }

    // verifie si valde : string, int...
    protected function checkCybersec() {
        if (!ctype_digit((string)$this->form['fk_recette']) || !ctype_digit((string)$this->form['fk_categorie'])) {
            throw new HttpStatusException("fk_recette et fk_categorie doivent être des entiers", 400);
        }
    }

    // Verifie les droits
    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté pour ajouter une catégorie à une recette.");
        }
    }

    // transmet au service
    protected function processRequest() {
        $categorieRecette = new CategorieRecette();

        $recetteService = new RecetteService();
        $recette = $recetteService->findByPk((int)$this->form['fk_recette']);
        $categorieRecette->setRecette($recette);

        $categorieService = new CategorieService();
        $categorie = $categorieService->findByPk((int)$this->form['fk_categorie']);
        $categorieRecette->setCategorie($categorie);

        $this->response = $this->service->insert($categorieRecette);
    }
}
?>