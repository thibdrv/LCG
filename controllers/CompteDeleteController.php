<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/services/CompteService.php");
require_once(ROOT . "/exceptions/HttpStatusException.php");

class CompteDeleteController extends AbstractController implements IController {
    private CompteService $service;

    public function __construct(array $form) {
        parent::__construct($form);
        $this->service = new CompteService();
    }

    protected function checkForm() {
        if (!isset($this->form['pk_compte'])) {
            throw new HttpStatusException("Le champ 'pk_compte' est obligatoire", 400);
        }
    }

    protected function checkCybersec() {
        if (!ctype_digit((string)$this->form['pk_compte'])) {
            throw new HttpStatusException("pk_compte doit être un entier", 400);
        }
    }

    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté pour supprimer un compte.");
        }
        // ⚠️ Les droits plus fins (admin ou propriétaire)
        // sont gérés dans CompteService::delete()
    }

    protected function processRequest() {
        $pk = (int)$this->form['pk_compte'];

        // Suppression via le service (qui gère les droits)
        $this->service->delete($pk);

        $this->response = [
            "status"   => "success",
            "message"  => "Compte supprimé avec succès",
            "pk_compte" => $pk
        ];
    }
}
