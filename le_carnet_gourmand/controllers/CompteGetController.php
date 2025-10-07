<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/entities/Compte.php");
require_once(ROOT . "/services/CompteService.php");


class CompteGetController extends AbstractController implements IController
{
    private CompteService $compteService;
    private ?int $pk = null; // pk_compte optionnel

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->compteService = new CompteService();
    }

    /**
     * checkForm : pk_compte est optionnel. Si présent, doit être numérique.
     */
    protected function checkForm(): void
    {
        // DEBUG
        error_log("DEBUG pk_compte brut = " . var_export($this->form['pk_compte'] ?? 'NON DEFINI', true));

        // Si pk_compte est vide ou absent → on veut findAll()
        if (!isset($this->form['pk_compte']) || $this->form['pk_compte'] === '' || $this->form['pk_compte'] === null) {
            return;
        }

        $val = (string)$this->form['pk_compte'];

        // Vérifie que c’est bien un entier positif
        if (!ctype_digit($val)) {
            error_log("CYBERSEC Receive bad request (pk_compte invalide) => " . var_export($val, true));
            _400_Bad_Request("pk_compte invalide");
        }

        $this->pk = intval($val);
    }


    // checkCybersec : pas de vérification supplémentaire ici.
    protected function checkCybersec(): void {}

    // checkRights : s'assurer que l'utilisateur est au minimum connecté.
    // Les contrôles plus fins (admin / propriétaire) sont faits dans CompteService.
    protected function checkRights(): void
    {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté.");
        }
    }

    // processRequest : appel du service selon présence ou non de pk.
    protected function processRequest(): void
    {
        if ($this->pk !== null) {
            // Récupérer un compte précis (le service vérifiera les droits)
            $compte = $this->compteService->findByPk($this->pk);
            if ($compte === null) {
                _404_Not_Found("Compte introuvable");
            }
            $this->response = $compte;
        } else {
            // Récupérer tous les comptes (le service gère l'autorisation)
            $comptes = $this->compteService->findAll();
            $this->response = $comptes;
        }
    }


    // processResponse : encoder proprement la réponse en JSON.
    protected function processResponse(): string
    {
        if (is_null($this->response)) {
            throw new HttpStatusException("Unexpected error: empty response", 500);
        }

        // Si la response est un objet qui a toArray(), l'utiliser pour un rendu propre
        if (is_object($this->response) && method_exists($this->response, 'toArray')) {
            $out = $this->response->toArray();
        } else {
            $out = $this->response; // peut être objet, tableau, liste...
        }

        // Retourner JSON avec prise en charge des caractères unicode
        $json = json_encode($out, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            // fallback safety : encoder un message d'erreur succinct
            error_log("CompteGetController::processResponse - json_encode failed: " . json_last_error_msg());
            throw new HttpStatusException("JSON encoding error", 500);
        }
        return $json;
    }
}
?>