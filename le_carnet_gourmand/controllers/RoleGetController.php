<?php

	require_once(ROOT . "/controllers/IController.php");
	require_once(ROOT . "/controllers/AbstractController.php");
	require_once(ROOT . "/utils/functions.php");
	require_once(ROOT . "/entities/Role.php");
	require_once(ROOT . "/services/RoleService.php");

class RoleGetController extends AbstractController implements IController
{
    private RoleService $service;
    private ?int $pk = null; // pk_role optionnel

    public function __construct(array $form)
    {
        parent::__construct($form);
        $this->service = new RoleService();
    }

	function checkForm() {
	}

    function checkCybersec() {
    }

    function checkRights()
    {
        if (!isLogged()) {
            _401_Unauthorized("Vous devez être connecté.");
        }
    }

    function processRequest()
    {
        if ($this->pk !== null) {
            $role = $this->service->findByPk($this->pk);
            if ($role === null) {
                _404_Not_Found("Rôle introuvable");
            }
            $this->response = $role;
        } else {
            $roles = $this->service->findAll();
            $this->response = $roles;
        }
    }

    protected function processResponse(): string
    {
        return json_encode($this->response, JSON_UNESCAPED_UNICODE);
    }

}
?>