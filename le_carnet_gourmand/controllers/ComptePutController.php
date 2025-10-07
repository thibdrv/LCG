<?php

    require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/controllers/IController.php");
    require_once(ROOT . "/controllers/AbstractController.php");
    require_once(ROOT . "/entities/Compte.php");
    require_once(ROOT . "/exceptions/HttpStatusException.php");
    require_once(ROOT . "/services/CompteService.php");


class ComptePutController extends AbstractController implements IController
{
    private CompteService $compteService;
    private ?int $pk = null;

    // Données du formulaire
    private ?string $motdepasse = null;
    private ?bool $estBanni = null;
    private $role = null;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->compteService = new CompteService();
    }

    // Vérifie que pk_compte est présent
    protected function checkForm(): void
    {
        if (!isset($this->form['pk_compte'])) {
            _400_Bad_Request("Missing parameter: pk_compte");
        }
    }

    // Sécurise et valide les entrées
    protected function checkCybersec(): void
    {
        // pk_compte
        if (!ctype_digit((string)$this->form['pk_compte'])) {
            _400_Bad_Request("Invalid pk_compte");
        }
        $this->pk = intval($this->form['pk_compte']);

        // mot_de_passe (optionnel)
        if (isset($this->form['mot_de_passe'])) {
            if (!isSanitizedString($this->form['mot_de_passe'])) {
                _400_Bad_Request("Invalid mot_de_passe");
            }
            $this->motdepasse = $this->form['mot_de_passe'];
        }

        // est_banni (optionnel, doit être 0/1)
        if (isset($this->form['est_banni'])) {
            $val = $this->form['est_banni'];
            if ($val !== "0" && $val !== "1") {
                _400_Bad_Request("Invalid est_banni");
            }
            $this->estBanni = ($val === "1");
        }
    }

    // Vérifie que l'utilisateur est connecté
    protected function checkRights(): void
    {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté.");
        }
    }

    // Prépare un objet Compte à partir du formulaire
    private function createFromForm(): Compte
    {
        // Email/pseudo temporaires juste pour respecter le constructeur
        $compte = new Compte("tmp@mail.com", "tmpPseudo", "tmpPassword"); 
        $compte->setPkCompte($this->pk);

        if ($this->motdepasse !== null) {
            $compte->setMotDePasse($this->motdepasse);
        }
        if ($this->estBanni !== null) {
            $compte->setEstBanni($this->estBanni);
        }

        return $compte;
    }


    /**
     * Exécute la mise à jour via le service
     */
    protected function processRequest(): void
    {
        $compte = $this->createFromForm();
        $updated = $this->compteService->update($compte);
        $this->response = ["status" => "success", "Compte mis à jour" => $updated];
    }

    protected function processResponse(): string
    {
        if (is_null($this->response)) {
            throw new HttpStatusException("Unexpected error", 500);
        }
        return json_encode($this->response, JSON_UNESCAPED_UNICODE);
    }
}
?>