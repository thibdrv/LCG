<?php

    require_once(ROOT . "/controllers/IController.php");
    require_once(ROOT . "/controllers/AbstractController.php");
    require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Commentaire.php");
    require_once(ROOT . "/services/CommentaireService.php");


class CommentaireGetController extends AbstractController implements IController
{
    private CommentaireService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new CommentaireService();
    }

    protected function checkForm() {
        // pas d'erreur si fk_recette est absent → on gérera ça dans processRequest
    }

    protected function checkCybersec() {
        if (isset($this->form['fk_recette']) && !ctype_digit((string)$this->form['fk_recette'])) {
            throw new HttpStatusException("fk_recette doit être un entier", 400);
        }
    }

    protected function checkRights() {

    }

    protected function processRequest() {
        if (isset($this->form['fk_recette'])) {
            // Cas 1 : commentaires d’une recette
            $this->response = $this->service->findByRecette((int)$this->form['fk_recette']);
        } else {
            // Cas 2 : tous les commentaires (admin only)
            $this->response = $this->service->findAll();
        }
    }
}

?>