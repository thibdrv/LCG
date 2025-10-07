<?php

    require_once(ROOT . "/exceptions/HttpStatusException.php");
    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/utils/SessionManager.php");
    require_once(ROOT . "/entities/Compte.php");
	require_once(ROOT . "/services/CompteService.php");

class LoginPostController extends AbstractController implements IController
{
    private CompteService $compteService;
    private string $email;
    private string $motdepasse;

    public function __construct(array $form)
    {
        parent::__construct($form, "LoginPostController");
        $this->compteService = new CompteService();
    }

    protected function checkForm()
    {
        if (!isset($this->form['email'])) {
            throw new HttpStatusException("parameter email not exists", 400);
        }
        if (!isset($this->form['mot_de_passe'])) {
            throw new HttpStatusException("parameter mot_de_passe not exists", 400);
        }
    }

    protected function checkCybersec()
    {
        if (!isEmail($this->form['email'])) {
            throw new HttpStatusException("parameter email is not valid", 400);
        }
        $this->email = sanitizeString($this->form['email']);

        if (!isSanitizedString($this->form['mot_de_passe'])) {
            throw new HttpStatusException("parameter mot de passe is not valid", 400);
        }
        $this->motdepasse = sanitizeString($this->form['mot_de_passe']);
    }

    protected function checkRights()
    {
        // Pas besoin de vérification de droits pour login
    }

    protected function processRequest()
    {
        if (isLogged()) {
            throw new HttpStatusException("Already Authenticated", 499);
        }

        // Vérifier les identifiants
        $compte = Compte::createForCredential($this->email, $this->motdepasse);
        $pk = $this->compteService->isValidCredential($compte);

        error_log("LoginPostController - pk reçu du service (immédiat) = " . var_export($pk, true));

        if (is_null($pk)) {
            throw new HttpStatusException("Invalid Credential", 401);
        }

    /** @var Compte $fullCompte */
    $fullCompte = $this->compteService->findByPkForLogin($pk);

        SessionManager::login($pk);
        $_SESSION['email']       = $fullCompte->getEmail();
        $_SESSION['pseudo']      = $fullCompte->getPseudo();
        $_SESSION['est_supprime'] = $fullCompte->getEstSupprime();
        $_SESSION['est_banni']    = $fullCompte->getEstBanni();
        $_SESSION['fk_role']      = $fullCompte->getRole()->getPkRole();

        error_log("LoginPostController - après login, session=" . print_r($_SESSION, true));

        $this->response = json_encode([
            "success" => true,
            "message" => "Vous êtes connecté 🎉",
            "compte_pk" => $pk,
            "pseudo" => $fullCompte->getPseudo()
        ]);
    }
}
?>