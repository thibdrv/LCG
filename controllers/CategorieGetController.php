<?php

    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Categorie.php");
	require_once(ROOT . "/services/CategorieService.php");

    
class CategorieGetController extends AbstractController implements IController
{
    private CategorieService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new CategorieService();
    }

    // existe t il ?
    protected function checkForm() {
        // GET peut lister toutes les catégories ou une seule par ID
        // donc pas de champs obligatoires
    }

    // es-ce valide : string, int
    protected function checkCybersec() {
        if (isset($this->form['pk']) && !ctype_digit((string)$this->form['pk'])) {
            throw new HttpStatusException("pk doit être un entier", 400);
        }
    }

    // a t il le droit ?
    protected function checkRights() {
        // Lecture libre → aucune restriction
    }

    // transmet au service
    protected function processRequest() {
        if (isset($this->form['pk'])) {
            $this->response = $this->service->findByPk((int)$this->form['pk']);
        } else {
            $this->response = $this->service->findAll();
        }
    }
}
?>