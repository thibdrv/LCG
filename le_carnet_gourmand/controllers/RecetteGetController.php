<?php

    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Recette.php");
	require_once(ROOT . "/services/RecetteService.php");


class RecetteGetController extends AbstractController implements IController
{
    private RecetteService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new RecetteService();
    }

    // Vérifie la présence obligatoire des champs dans le formulaire
    protected function checkForm() {
    }

    // Vérifie la validité des champs existants (protection cybersécurité)
    protected function checkCybersec() {
        // Si 'pk' est défini dans le formulaire ET que ce n'est pas un entier positif
        if (isset($this->form['pk']) && !ctype_digit((string)$this->form['pk'])) {
            // On lève une erreur HTTP 400
            throw new HttpStatusException("pk doit être un entier", 400);
        }

        // Même logique pour 'categories'
        if (isset($this->form['categories']) && !ctype_digit((string)$this->form['categories'])) {
            throw new HttpStatusException("categories doit être un entier", 400);
        }
    }

    // as t il le droit
    protected function checkRights() {
    // Lecture publique → aucune restriction
    }

    // transmet au service 
    protected function processRequest() {
        if (isset($this->form['pk'])) {
            $this->response = $this->service->findByPk((int)$this->form['pk']);
        } elseif (isset($this->form['categories'])) {
            $this->response = $this->service->findByCategorie((int)$this->form['categories']);
        } else {
            $this->response = $this->service->findAll();
        }
    }
}
?>