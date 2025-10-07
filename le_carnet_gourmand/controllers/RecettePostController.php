<?php

    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Recette.php");
	require_once(ROOT . "/services/RecetteService.php");
    require_once(ROOT . "/services/CategorieRecetteService.php");


class RecettePostController extends AbstractController implements IController
{
    private RecetteService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new RecetteService();
    }

    protected function checkForm() {
        // Vérifie la présence des champs obligatoires
        if (!isset($this->form['nom'], $this->form['details'], $this->form['ingredients'], $this->form['fk_categorie'])) {
        throw new HttpStatusException(
            "Les champs 'nom', 'details', 'ingredients' et 'fk_categorie' sont obligatoires",
            400
        );
        }
    }

    protected function checkCybersec() {
        // Vérifie que 'nom' est une chaîne de minimum 3 caractères
        if (!is_string($this->form['nom']) || strlen(trim($this->form['nom'])) < 3) {
            throw new HttpStatusException("Nom invalide", 400);
        }

        // Vérifie que 'description' est une chaîne de minimum 5 caractères
        if (!is_string($this->form['details']) || strlen(trim($this->form['details'])) < 5) {
            throw new HttpStatusException("details invalide", 400);
        }

        // Vérifie que 'fk_categorie' est un entier positif
        if (!ctype_digit((string)$this->form['fk_categorie'])) {
            throw new HttpStatusException("fk_categorie doit être un entier", 400);
        }

        // Vérifie que 'ingredients' existe et est une chaîne non vide
        if (!isset($this->form['ingredients']) || !is_string($this->form['ingredients']) || strlen(trim($this->form['ingredients'])) < 3) {
            throw new HttpStatusException("Ingredients invalide", 400);
        }

        // Vérification contre l'injection SQL basique sur 'nom', 'description' et 'ingredients'
        $fieldsToCheck = ['nom', 'description', 'ingredients'];
        foreach ($fieldsToCheck as $field) {
            $value = trim($this->form[$field]);

            if (preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|--|;)\b/i', $value)) {
                throw new HttpStatusException("Le champ '$field' contient des caractères interdits (SQL Injection)", 400);
            }
        }
    }


    protected function checkRights() {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté pour ajouter une recette.");
        }
    }

    protected function processRequest() {
        $recette = new Recette();
        $recette->setNom(trim($this->form['nom']));
        $recette->setDetails(trim($this->form['details']));
        $recette->setIngredients(trim($this->form['ingredients']));

        // Associer l’auteur
        $compteService = new CompteService();
        $compte = $compteService->findByPk(getComptePkFromSession());
        $recette->setCompte($compte);

        // 1. On insère la recette et récupère son ID
        $recetteId = $this->service->insert($recette);
        $recette->setPkRecette($recetteId);

        // 2. On insère dans la table d’association
        $categorieService = new CategorieService();
        $categorie = $categorieService->findByPk((int)$this->form['fk_categorie']);

        $categorieRecette = CategorieRecette::create($recette, $categorie);
        $categorieRecetteService = new CategorieRecetteService();
        $categorieRecetteService->insert($categorieRecette);


        http_response_code(201);

        $this->response = [
            "message" => "Recette créée avec succès",
            "recette n°" => $recetteId
        ];
    }

}
?>