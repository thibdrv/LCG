<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/Favori.php");
require_once(ROOT . "/services/FavoriService.php");

class FavoriDeleteController extends AbstractController implements IController
{
    private FavoriService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new FavoriService();
    }


    protected function checkForm() {
        if (!isset($this->form['fk_recette'])) {
            throw new HttpStatusException("fk_recette est obligatoire pour supprimer un favori", 400);
        }
    }

    protected function checkCybersec() {
        if (!ctype_digit((string)$this->form['fk_recette'])) {
            throw new HttpStatusException("fk_recette doit être un entier", 400);
        }
    }

    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté.");
        }
    }

    protected function processRequest() {
        $pk = [
            'fk_recette' => (int)$this->form['fk_recette']
        ];
        $this->service->delete($pk);
        $this->response = ["message" => "Favori supprimé"];
    }

}
?>