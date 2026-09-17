<?php

require_once(ROOT . "/exceptions/HttpStatusException.php");
require_once(ROOT . "/controllers/IController.php");
require_once(ROOT . "/utils/functions.php");

abstract class AbstractController implements IController {

    protected array $form;
    protected mixed $response;

    public function __construct(array $form, string $controllerName = "")
    {
        $this->form = $form;
        $this->controllerName = $controllerName;
    }

    function execute() : string
    {
        $this->checkForm(); //il existe ?
        $this->checkCybersec(); // il est conforme : string, int ?
        $this->checkRights(); // verifie les droits
        $this->processRequest(); // execute la goique metier
        return $this->processResponse(); // transform en JSON
    }

    protected abstract function checkForm(); // Teste si paramètres bien présents existe

    protected abstract function checkCybersec(); // Teste si paramètres conformes : string / int...

    protected abstract function checkRights(); // Verifie les droits

    protected abstract function processRequest(); // Transmet au service

    protected function processResponse()
    {
       if (is_null($this->response))
       {
        error_log("Unable to find something");          // TODO Faire une méthode abstraite
        throw new HttpStatusException("", 404);
       }
       error_log("Response: " . print_r($this->response, true));

       $output = json_encode($this->response, JSON_UNESCAPED_UNICODE);
       $cleanedOutput = ltrim ($output);         // suppression des espaces avant et après
       return $cleanedOutput;
    }

    // AbstractController.php
// protected function processResponse(): string
// {
//    if (is_null($this->response)) {
//        throw new HttpStatusException("", 404);
//    }
//    $out = (is_object($this->response) && method_exists($this->response, 'toArray'))
//       ? $this->response->toArray()
//       : $this->response;
//    $json = json_encode($out, JSON_UNESCAPED_UNICODE);
//    if ($json === false) {
//        error_log("processResponse - json_encode failed: " . json_last_error_msg());
//        throw new HttpStatusException("JSON encoding error", 500);
//    }
//    return ltrim($json);
//}
}
?>
