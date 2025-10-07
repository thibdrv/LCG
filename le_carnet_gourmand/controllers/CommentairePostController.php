<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/Commentaire.php");
require_once(ROOT . "/services/CommentaireService.php");


class CommentairePostController extends AbstractController implements IController
{
    private CommentaireService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new CommentaireService();
    }


    protected function checkForm() {
        if (!isset($this->form['fk_recette'], $this->form['contenu'])) {
            throw new HttpStatusException("fk_recette et contenu sont obligatoires", 400);
        }
    }

    protected function checkCybersec() {
        if (!ctype_digit((string)$this->form['fk_recette'])) {
            throw new HttpStatusException("fk_recette doit être un entier", 400);
        }
        if (!is_string($this->form['contenu']) || strlen(trim($this->form['contenu'])) < 2) {
            throw new HttpStatusException("Le contenu du commentaire est invalide", 400);
        }
    }

    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté pour poster un commentaire.");
        }
    }

    protected function processRequest() {
        $commentaire = new Commentaire();
        $commentaire->setContenu(trim($this->form['contenu']));
        $commentaire->setDateCreation(new DateTime());
        $commentaire->setEstApprouve(false);
        $commentaire->setEstSupprime(false);

        $compteService = new CompteService();
        $compte = $compteService->findByPk(\getComptePkFromSession());
        $commentaire->setCompte($compte);

        $recetteService = new RecetteService();
        $recette = $recetteService->findByPk((int)$this->form['fk_recette']);
        $commentaire->setRecette($recette);

            $pk = $this->service->insert($commentaire);

        if ($pk) {
            echo json_encode([
                "success" => true,
                "message" => "Commentaire en attente",
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Échec"
            ]);
        }


        $this->response = $this->service->insert($commentaire);
    }
}
?>