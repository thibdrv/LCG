<?php

    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Categorie.php");
	require_once(ROOT . "/services/CategorieService.php");


class CategorieDeleteController extends AbstractController implements IController
{
    private CategorieService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new CategorieService();
    }

    // existe ?
    protected function checkForm() {
        // verifie si existe dans form
        if (!isset($this->form['pk_categorie'])) {
            throw new HttpStatusException("La pk de la catégorie est obligatoire pour la suppression", 400);
        }
    }
    // valide ? string, int
    protected function checkCybersec() {
        // verifie si chiffre
        if (!ctype_digit((string)$this->form['pk_categorie'])) {
            throw new HttpStatusException("pk doit être un entier", 400);
        }
    }
    // a t-il le droit ?
    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté.");
        }
    }
    // transmet au service
    protected function processRequest() {
        $this->service->delete((int)$this->form['pk_categorie']);
        $this->response = ["message" => "Catégorie supprimée avec succès"];
    }
}
?>