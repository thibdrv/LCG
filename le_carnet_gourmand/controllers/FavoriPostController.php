<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/Favori.php");
require_once(ROOT . "/services/FavoriService.php");

class FavoriPostController extends AbstractController implements IController
{
    private FavoriService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new FavoriService();
    }


    protected function checkForm() {
        if (!isset($this->form['fk_recette'])) {
            throw new HttpStatusException("fk_recette est obligatoire", 400);
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
        $favori = new Favori();

        // Associer le compte connecté
        $favori->setCompte(getCurrentUser()); // objet Compte

        // Associer la recette
        $recette = new Recette();
        $recette->setPkRecette((int)$this->form['fk_recette']);
        $favori->setRecette($recette);

        try {
            $this->service->insert($favori);

            $this->response = [
                "success" => true,
                "message" => "Favori ajouté avec succès."
            ];
        } catch (Exception $e) {
            $this->response = [
                "success" => false,
                "message" => "Échec lors de l'ajout du favori.",
                "error"   => $e->getMessage()
            ];
        }
    }
}
?>