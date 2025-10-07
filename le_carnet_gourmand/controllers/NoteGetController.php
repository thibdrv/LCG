<?php

    require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
    require_once(ROOT . "/entities/Note.php");
	require_once(ROOT . "/services/NoteService.php");


class NoteGetController extends AbstractController implements IController
{
    private NoteService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new NoteService();
    }

    protected function checkForm() {
        // Ici GET peut lister toutes les notes de l’utilisateur connecté
        // donc pas de champs obligatoires
    }

    protected function checkCybersec() {
        // Rien à vérifier ici
    }

    protected function checkRights() {
        // tout le monde peut voir les notes
    }

    protected function processRequest() {
        // Vérifie si l'utilisateur veut la moyenne d'une recette
        $pkRecette = $_GET["pk_recette"] ?? null;

        if ($pkRecette !== null) {
            try {
                $moyenne = $this->service->totalNoteParRecette((int)$pkRecette);

                echo json_encode([
                    "success"   => true,
                    "recetteId" => (int)$pkRecette,
                    "moyenne"   => $moyenne ?? 0
                ]);
                return; // ✅ stoppe ici pour éviter d’exécuter la suite
            } catch (Exception $e) {
                _500_Internal_Server_Error("Erreur : " . $e->getMessage());
            }
        }

        // Sinon → renvoyer les notes de l’utilisateur connecté
        $fkCompte = getComptePkFromSession();
        if ($fkCompte === null) {
            _401_Unauthorized("Vous devez être connecté pour voir vos notes.");
        }

        try {
            $notes = $this->service->findByCompte((int)$fkCompte);

            echo json_encode([
                "success" => true,
                "compteId" => (int)$fkCompte,
                "notes" => $notes
            ]);
        } catch (Exception $e) {
            _500_Internal_Server_Error("Erreur : " . $e->getMessage());
        }
    }
}
?>