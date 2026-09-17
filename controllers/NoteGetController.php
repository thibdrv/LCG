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
        $pkRecette = $this->form["pk_recette"] ?? null;

        if ($pkRecette !== null) {
            $moyenne = $this->service->totalNoteParRecette((int)$pkRecette);

            $this->response = [
                "success"   => true,
                "recetteId" => (int)$pkRecette,
                "moyenne"   => $moyenne ?? 0
            ];
            return;
        }

        $fkCompte = getComptePkFromSession();
        if ($fkCompte === null) {
            throw new HttpStatusException("Vous devez être connecté pour voir vos notes.", 401);
        }

        $notes = $this->service->findByCompte((int)$fkCompte);

        $this->response = [
            "success"  => true,
            "compteId" => (int)$fkCompte,
            "notes"    => $notes
        ];
    }
}
?>