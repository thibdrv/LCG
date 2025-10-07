<?php

    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Recette.php");
    require_once(ROOT . "/services/CategorieService.php");
    require_once(ROOT . "/services/CategorieRecetteService.php");



class RecettePutController extends AbstractController implements IController
{
    private RecetteService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new RecetteService();
    }

    protected function checkForm() {
        if (!isset($this->form['pk_recette'])) {
            throw new HttpStatusException("Le champ 'pk' est obligatoire", 400);
        }
    }

    protected function checkCybersec() {
        // pk est obligatoire
        if (!isset($this->form['pk_recette']) || !ctype_digit((string)$this->form['pk_recette'])) {
            throw new HttpStatusException("Le champ 'pk' est obligatoire et doit être un entier", 400);
        }

        // nom optionnel
        if (isset($this->form['nom'])) {
            if (!is_string($this->form['nom']) || strlen(trim($this->form['nom'])) < 3) {
                throw new HttpStatusException("Nom invalide", 400);
            }
        }

        // details/description optionnel
        if (isset($this->form['details'])) {
            if (!is_string($this->form['details']) || strlen(trim($this->form['details'])) < 5) {
                throw new HttpStatusException("Details invalide", 400);
            }
        }

        // fk_categorie optionnel
        if (isset($this->form['fk_categorie'])) {
            if (!ctype_digit((string)$this->form['fk_categorie'])) {
                throw new HttpStatusException("fk_categorie doit être un entier", 400);
            }
        }
    }


    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté pour modifier une recette.");
        }
    }

protected function processRequest() {
    // Si c’est une demande d’approbation admin
    if (isset($this->form['est_approuve'])) {
        $approve = filter_var($this->form['est_approuve'], FILTER_VALIDATE_BOOLEAN);
        $this->service->approve((int)$this->form['pk_recette'], $approve);

        $this->response = [
            "message" => "Recette " . ($approve ? "approuvée" : "refusée") . " avec succès",
            "id" => (int)$this->form['pk_recette']
        ];
        return;
    }

    // Charger la recette
    $recette = $this->service->findByPk((int)$this->form['pk_recette']);
    if (!($recette instanceof Recette)) {
        throw new RuntimeException("Expected Recette from service");
    }

    // Vérification des droits
    $currentUser = (new CompteService())->findByPk(getComptePkFromSession());
    if ($recette->getCompte()->getPkCompte() !== $currentUser->getPkCompte() && !isAdmin()) {
        _403_Forbidden("Vous n'avez pas le droit de modifier cette recette.");
    }

    // Mise à jour partielle
    if (isset($this->form['nom'])) {
        $recette->setNom(trim($this->form['nom']));
    }
    if (isset($this->form['details'])) {
        $recette->setDetails(trim($this->form['details']));
    }
    if (isset($this->form['ingredients'])) {
        $recette->setIngredients(trim($this->form['ingredients']));
    }
    if (isset($this->form['image'])) {
        $recette->setImage(trim($this->form['image']));
    }
    if (isset($this->form['lien'])) {
        $recette->setLien(trim($this->form['lien']));
    }

    // Mettre à jour la recette
    $this->service->update($recette);

    // Mettre à jour la catégorie uniquement si demandée
    if (isset($this->form['fk_categorie'])) {
        $categorieService = new CategorieService();
        $categorie = $categorieService->findByPk((int)$this->form['fk_categorie']);

        $categorieRecetteService = new CategorieRecetteService();
        $categorieRecette = CategorieRecette::create($recette, $categorie);
        $categorieRecetteService->insert($categorieRecette);
    }

    // Réponse
    $this->response = [
        "message" => "Recette mise à jour avec succès",
        "id" => $recette->getPkRecette()
    ];
}

}
?>