<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/Commentaire.php");
require_once(ROOT . "/services/CommentaireService.php");

class CommentaireDeleteController extends AbstractController implements IController {
    private CommentaireService $service;

    public function __construct(array $form) {
        parent::__construct($form);
        $this->service = new CommentaireService();
    }

    protected function checkForm() {
        if (!isset($this->form['fk_recette'])) {
            throw new HttpStatusException("Le champ 'fk_recette' est obligatoire", 400);
        }
    }

    protected function checkCybersec() {
        if (!ctype_digit((string)$this->form['fk_recette'])) {
            throw new HttpStatusException("fk_recette doit être un entier", 400);
        }
    }

    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté pour supprimer un commentaire.");
        }
    }

    protected function processRequest() {
        // Le compte courant supprime SON commentaire
        $compte = getCurrentUser();
        $pk = [
            "fk_compte"  => $compte->getPkCompte(),
            "fk_recette" => (int)$this->form['fk_recette']
        ];

        $this->service->delete($pk);

        $this->response = [
            "message" => "Commentaire supprimé avec succès",
            "compte"  => $pk["fk_compte"],
            "recette" => $pk["fk_recette"]
        ];
    }
}
?>