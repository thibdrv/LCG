<?php

    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Categorie.php");
	require_once(ROOT . "/services/CategorieService.php");


class CategoriePostController extends AbstractController implements IController
{
    private CategorieService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new CategorieService();
    }

    // existe t il ?
    protected function checkForm() {
        if (!isset($this->form['nom'])) {
            throw new HttpStatusException("Le champ 'nom' est obligatoire", 400);
        }
    }
    // verifie forme : string, int...
    protected function checkCybersec() {
    $nom = trim($this->form['nom'] ?? '');
    // Vérifie que c'est bien une chaîne et qu'il y a au moins X caractères
    if (!is_string($nom) || strlen($nom) < 3) {
        throw new HttpStatusException("Le nom de la catégorie est invalide (trop court)", 400);
    }
    // Vérifie qu'il ne contient pas de balises HTML ou caractères interdits
    if (preg_match('/[<>]/', $nom)) {
        throw new HttpStatusException("Le nom de la catégorie contient des caractères interdits", 400);
    }
    // Si tout va bien, on peut éventuellement le réassigner "nettoyé"
    $this->form['nom'] = htmlspecialchars($nom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

    // a t-il le droit ?
    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté.");
        }
    }

    // transmet au service
    protected function processRequest() {
        $categorie = new Categorie();
        $categorie->setNom(trim($this->form['nom']));

        $categoriePk = $this->service->insert($categorie);

        $this->response = [
            "message" => "Catégorie créée avec succès",
            "id" => $categoriePk
        ];
    }

}
?>