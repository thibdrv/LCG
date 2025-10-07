<?php

define("ROOT", dirname(__FILE__) ) ; // tous les appels seront en chemin absolu / pour definir un chemin pour ne pas avoir de soucis 

require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/utils/functions.php");
require_once(ROOT . "/utils/SessionManager.php");


// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_log("Session ID: " . session_id());
error_log("Session: " . print_r($_SESSION, true));

serverBootstrap();  // programme qui amorce un autre programme pour annuler les erreurs php de xampp
SessionManager::manageSession();
// test dans le terminal
// Get-Content "C:\xampp\apache\logs\error.log" -Wait -Tail 10 | ForEach-Object { $_ -replace "\\n", "`n" }


//Extraire la méthode supportée
$FORM = extractForm();     // extrait le formulaire, selon la méthode
//Créer la route 
$ROUTE = extractRoute($FORM);

//Créer le controller et les exceptions
try
{
    $CONTROLLER = createController($FORM, $ROUTE);
    $response = $CONTROLLER->execute();
    header('Content-Type: application/json');
    echo $response;
}
catch(HttpStatusException $ex)
{
    error_log("HttpStatusException: " . $ex->getCode() . " - " . $ex->getMessage());
    raiseHttpStatus ($ex);
}