<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/Commentaire.php");
require_once(ROOT . "/services/CommentaireService.php");


class CommentairePutController extends AbstractController implements IController
{
    private CommentaireService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);

        // Récupération du body JSON (si présent)
        $json = json_decode(file_get_contents("php://input"), true);
        if ($json && is_array($json)) {
            $this->form = array_merge($this->form, $json);
        }

        $this->service = new CommentaireService();
    }

    protected function checkForm() {
        if (!isset($this->form['fk_compte'], $this->form['fk_recette'])) {
            throw new HttpStatusException(
                "Les champs 'fk_compte' et 'fk_recette' sont obligatoires pour modifier un commentaire",
                400
            );
        }
        if (!isset($this->form['est_approuve'])) {
            throw new HttpStatusException(
                "Le champ 'est_approuve' est obligatoire pour approuver/refuser un commentaire",
                400
            );
        }
    }

    protected function checkCybersec() {
        if (!ctype_digit((string)$this->form['fk_compte']) || !ctype_digit((string)$this->form['fk_recette'])) {
            throw new HttpStatusException("fk_compte et fk_recette doivent être des entiers", 400);
        }
        if (!in_array((string)$this->form['est_approuve'], ['0','1','true','false'], true)) {
            throw new HttpStatusException("est_approuve doit être un booléen", 400);
        }
    }

    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté.");
        }
    }

    public function processRequest(): void {
        // Construire un objet Commentaire
        $commentaire = new Commentaire();

        // Compte cible (celui qui a posté le commentaire)
        $compte = new Compte();
        $compte->setPkCompte((int)$this->form["fk_compte"]);
        $commentaire->setCompte($compte);

        // Recette
        $recette = new Recette();
        $recette->setPkRecette((int)$this->form["fk_recette"]);
        $commentaire->setRecette($recette);

        // Seul champ modifiable → estApprouve
        $commentaire->setEstApprouve(filter_var($this->form["est_approuve"], FILTER_VALIDATE_BOOLEAN));

        try {
            $result = $this->service->update($commentaire);

            if ($result) {
                $this->response = [
                    "success" => true,
                    "message" => "Commentaire mis à jour avec succès."
                ];
            } else {
                _500_Internal_Server_Error("Échec lors de la mise à jour du commentaire.");
            }

        } catch (Exception $e) {
            _500_Internal_Server_Error("Erreur : " . $e->getMessage());
        }
    }
}
?>