<?php

    require_once(ROOT . "/utils/sessioninfo/ISessionInfoProvider.php");
    require_once(ROOT . "/utils/SessionManager.php");

class BaseSessionInfoProvider implements ISessionInfoProvider {

    public function getComptePk(): ?int {
        return SessionManager::getComptePkFromSession();
    }

    public function getRolePk(): ?int {
        return SessionManager::getRolePkFromSession();
    }

}