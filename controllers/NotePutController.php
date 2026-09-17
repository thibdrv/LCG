<?php

require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/controllers/AbstractController.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/entities/Note.php");
require_once(ROOT . "/services/NoteService.php");

class NotePutController extends AbstractController implements IController
{
    private NoteService $service;

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new NoteService();
    }

    protected function checkForm() {
        if (!isset($this->form['fk_recette'], $this->form['note'])) {
            throw new HttpStatusException("fk_recette et note sont obligatoires", 400);
        }
    }

    protected function checkCybersec() {
        if (!ctype_digit((string)$this->form['fk_recette'])) {
            throw new HttpStatusException("fk_recette doit être un entier", 400);
        }
        $note = (float)$this->form['note'];
        if ($note < 0 || $note > 5 || fmod($note, 0.5) !== 0.0) {
            throw new HttpStatusException("La note doit être comprise entre 0 et 5 par pas de 0.5", 400);
        }
    }

    protected function checkRights() {
        if (!isLogged()) {
            throw new HttpStatusException("Vous devez être connecté.", 401);
        }
    }

    protected function processRequest() {
        $note = new Note();
        $note->setCompte(getCurrentUser());

        $recette = new Recette();
        $recette->setPkRecette((int)$this->form['fk_recette']);
        $note->setRecette($recette);

        $note->setNote((float)$this->form['note']);

        $result = $this->service->update($note);

        if (!$result) {
            throw new HttpStatusException("Échec de la mise à jour de la note", 500);
        }

        $this->response = [
            "success" => true,
            "message" => "Note mise à jour avec succès"
        ];
    }
}
?>