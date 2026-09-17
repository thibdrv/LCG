<?php

require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/services/CompteService.php");

class ComptePostController extends AbstractController implements IController
{
    protected array $form;
    protected string $email = "";
    protected string $pseudo = "";
    protected string $motdepasse = "";
    protected CompteService $compteService;
    protected mixed $response;
    
    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->compteService = new CompteService();
    }

    protected function checkForm(): void
    {
        if (
            !isset($this->form['email']) ||
            !isset($this->form['mot_de_passe']) ||
            !isset($this->form['pseudo'])
        ) {
            throw new HttpStatusException("Champs requis : email, mot_de_passe, pseudo", 400);
        }
    }

    protected function checkCybersec(): void
    {
        error_log("Form reçu : " . print_r($this->form, true));

        if (!isEmail($this->form['email'])) {
            throw new HttpStatusException("param email est invalide", 400);
        }
        $this->email = trim($this->form['email']);

        if (!isPassword($this->form['mot_de_passe'])) {
            throw new HttpStatusException("param mot_de_passe est invalide", 400);
        }
        $this->motdepasse = $this->form['mot_de_passe'];

        if (!isPseudo($this->form['pseudo'])) {
            throw new HttpStatusException("param pseudo est invalide", 400);
        }
        $this->pseudo = trim($this->form['pseudo']);

    }

    protected function checkRights(): void {
        if (isLogged()) {
            throw new HttpStatusException("Déjà authentifié", 499);
        }
    }

    protected function processRequest(): void
    {
        // On utilise la factory Compte::create
        $compte = Compte::create($this->email, $this->pseudo, $this->motdepasse);

        error_log("Création Compte: email={$compte->getEmail()}, pseudo={$compte->getPseudo()}, mdp={$compte->getMotDePasse()}");

        $pk = $this->compteService->insert($compte);

        $this->response = [
            "status" => "success",
            "pk" => $pk
        ];
    }
}
?>