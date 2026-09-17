<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/CategorieRecette.php");
require_once(ROOT . "/services/CategorieRecetteService.php");


class CategorieRecetteGetController extends AbstractController implements IController
{
    private CategorieRecetteService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new CategorieRecetteService();
    }

    protected function checkForm() {
        if (!isset($this->form['fk_recette']) && !isset($this->form['fk_categorie'])) {
            throw new HttpStatusException("Il faut fournir fk_recette ou fk_categorie", 400);
        }
    }

    protected function checkCybersec() {
        if (isset($this->form['fk_recette']) && !ctype_digit((string)$this->form['fk_recette'])) {
            throw new HttpStatusException("fk_recette doit être un entier", 400);
        }
        if (isset($this->form['fk_categorie']) && !ctype_digit((string)$this->form['fk_categorie'])) {
            throw new HttpStatusException("fk_categorie doit être un entier", 400);
        }
    }

    protected function checkRights() {
        // Lecture publique → aucune restriction
    }

    protected function processRequest() {
        if (isset($this->form['fk_recette'])) {
            $this->response = $this->service->findByRecette((int)$this->form['fk_recette']);
        } else {
            $this->response = $this->service->findByCategorie((int)$this->form['fk_categorie']);
        }
    }
}
?>