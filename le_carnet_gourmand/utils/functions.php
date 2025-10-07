<?php

	require_once(ROOT . "/exceptions/HttpStatusException.php");
	require_once(ROOT . "/entities/Compte.php");
    require_once(ROOT . "/services/CompteService.php");

define("MYSQL_DATE_FORMAT", "Y-m-d h:m:s");

function serverBootstrap()          // désactive l’affichage des erreurs PHP sur la page
{
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_STRICT & ~E_NOTICE & ~E_PARSE);
    ini_set('display_errors', 'off');
}


// Pour crée une constante = valeur qui ne changera jamais pendant l’exécution du script
define("START_TIME", "startTime");
define("COMPTE_PK", "pk_compte");


function destroy_Session(): void {
    // Détruire proprement la session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    session_start(); // relancer une nouvelle session propre
}

// verifie si il es connecter
function isLogged() : bool {
    return isset($_SESSION[COMPTE_PK]);
}
// recupere la pk d'un compte
function getComptePkFromSession() : ?int {
    return isLogged() ? $_SESSION[COMPTE_PK] : NULL;
}
// sais qui est connecter
function getCurrentUser(): ?Compte {
    if (!isLogged()) {
        return null;
    }
    $compteDao = new CompteDao();
    return $compteDao->findByPk($_SESSION[COMPTE_PK]);
}
function isUser(): bool {
    $currentUser = getCurrentUser();
    return $currentUser !== null && $currentUser->getRole()->getPkRole() == 1;
}
function isAdmin(): bool {
    $currentUser = getCurrentUser();
    return $currentUser !== null && $currentUser->getRole()->getPkRole() == 2;
}

function headerAndDie ($header) {
    header($header);
    die();
}

function _400_Bad_Request($msg = "Bad Request") {
    http_response_code(400);
    echo json_encode(["error" => $msg]);
    exit;
}

function _401_Unauthorized($msg = "Unauthorized") {
    http_response_code(401);
    echo json_encode(["error" => $msg]);
    exit;
}

function _403_Forbidden($msg = "Forbidden") {
    http_response_code(403);
    echo json_encode(["error" => $msg]);
    exit;
}

function _404_Not_Found($msg = "Not Found") {
    http_response_code(404);
    echo json_encode(["error" => $msg]);
    exit;
}

function _405_Method_Not_Allowed($msg = "Method Not Allowed") {
    http_response_code(405);
    echo json_encode(["error" => $msg]);
    exit;
}

function _499_Authentication_Error($msg = "Authentication Error") {
    http_response_code(499);
    echo json_encode(["error" => $msg]);
    exit;
}

function _500_Internal_Server_Error($msg = "Internal Server Error") {
    http_response_code(500);
    echo json_encode(["error" => $msg]);
    exit;
}

function _501_Not_Implemented($msg = "Not Implemented") {
    http_response_code(501);
    echo json_encode(["error" => $msg]);
    exit;
}

// raccourci catch
function raiseHttpStatus(HttpStatusException $ex) : void
{
    switch($ex->getCode())
    {
        case 400 : _400_Bad_Request($ex->getMessage()); break;
        case 401 : _401_Unauthorized($ex->getMessage()); break;
        case 403 : _403_Forbidden($ex->getMessage()); break;
        case 404 : _404_Not_Found($ex->getMessage()); break;
        case 405 : _405_Method_Not_Allowed(); break;
        case 499 : _499_Authentication_Error($ex->getMessage()); break;
        case 500 : // Ici on veut savoir ce qu'il se passe dans le serveur 
            error_log($ex); // c'est un cas d'exception non souhaité...
            _500_Internal_Server_Error($ex->getMessage()); break;
        default: throw new Exception("Http Status Exception not managed") . $ex->getCode();
        case 501:
        _501_Not_Implemented($ex->getMessage());
        break;
    }
}

function extractForm() : array {
    // test de la méthode, sinon 405
    switch ($_SERVER['REQUEST_METHOD'])
    {
        case 'GET' : return $_GET;
        case 'POST' : return $_POST;
        case 'PUT' : 
            $raw = file_get_contents(('php://input')); // cache php
            $form = [];
            parse_str($raw, $form);     // builtin php d'extraction de formulaire
            return $form;
        case 'DELETE' : return $_GET;
        default : _405_Method_Not_Allowed("Method not allowed");
    }
}

function extractRoute( array $FORM) : string
{
    if (!isset($FORM['route']))     // si le paramètre n'existe pas, 400
    {
        _400_Bad_Request("No parameter : route");
    }
    // on extrait la route, puis on sécurise la variable pour éviter les injections
    $ROUTE = $FORM['route'];
    if (preg_match("/^[A-Z][A-Za-z]{1,63}$/", $ROUTE))
    {
        return $ROUTE; // commence par une lettre majuscule, suivi d'une à 63 lettres
    }
    _400_Bad_Request("Wrong syntax : route");
}

function createController ($FORM, $ROUTE)
{
    // Mise en forme de la méthode
    $METHOD = createMethod();         

    // instancier dynamiquement un contrôleur en fonction de la route et de la méthode HTTP
    $CLASS_NAME = $ROUTE . $METHOD . "Controller"; // ex : Article Get Controller

    //Construction du chemin absolu
    $FILE = ROOT . "/controllers/". $CLASS_NAME . ".php";

    // si le fichier n'existe pas, exception
    if (!file_exists($FILE))
    {
        throw new HttpStatusException("Unknow Controller : " . $ROUTE . $METHOD, 404);
    }
    try {
        // Chargement et instanciation
        require($FILE);
        $CONTROLLER = new $CLASS_NAME($FORM); // new Article Get Controller ($FORM)
        return $CONTROLLER;
    } catch (ParseError $e) {
        error_log($e);
        _500_Internal_Server_Error("Internal Server Error");
        exit;
    }
}

function createMethod() {
    $method = strtolower($_SERVER["REQUEST_METHOD"]); // tout en minuscule
    return ucfirst($method); // 1ere lettre en majuscule
}

function isPassword(string $password) : bool
{
	$regex = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[^A-Za-z0-9]).{12,}$/u'; 
	return preg_match($regex, $password);
}

function isPseudo(string $pseudo) {
    return true;
}

function hashPassword(string $str) {
        return password_hash($str, PASSWORD_BCRYPT);
}

// Controller si la chaine est un entier naturel [0,N]
function isNaturalInteger (string $str) : bool {
    return ctype_digit($str);
}

function getSanitizedString(array $form, string $name) : string {
	if (!isSanitizedString( $form[$name] ))
        {
		throw _400_Bad_Request($msg = "")("CYBERSEC Receive bad request, param is not a sanitized string : " . $msg);
		}
	return $form[$name];
}

function sanitizeString(string $input) : string {
	return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); // Convertit les caractères spéciaux en entités HTML pour éviter les injections XSS
}

function isSanitizedString(string $str) : bool {
	return $str == sanitizeString($str);
}
 
function isSanitizedContent(string $str): bool
{
	// Vérifie qu'il n'y a pas de balises HTML ou de scripts
	if (strip_tags($str) !== $str) {
		return false;
	}
	// Vérifie qu'il n'y a pas de caractères interdits (comme <, >, etc.)
    // Autorise les caractères spéciaux et les accents
	return !preg_match('/[<>]/', $str);
}

function checkFormElement(array $form, string $name)
{
	if (! isset($form[$name])) // l'id doit etre présent
		{
		throw _400_Bad_Request($msg = "")("CYBERSEC Receive bad request, no parameter : " . $msg);
		}
}

function isWord(string $str, int $start = 1, int $end = 64) : bool
{
	return preg_match("/^[A-Za-z]{" .$start . "," .$end . "}$/", $str);
}
function getWord(array $form, string $name) : int {
	if (!isWord($form[$name]))
        {
		{
		throw _400_Bad_Request($msg = "")("CYBERSEC Receive bad request, param is not a word : " . $msg);
		}
    }
	return $form[$name];
}

function isBool(string $bool) : bool 
{
	return $bool === "true" || $bool === "false";
}
function getBoolean(array $form, string $name) : bool {
	if (!isBool($form[$name]))
        {
		throw _400_Bad_Request($msg = "")("CYBERSEC Receive bad request, param is not a boolean : " . $msg);
		}
        return $form[$name] == "true";
}

function isEmail(string $mail) : bool {
	return filter_var($mail, FILTER_VALIDATE_EMAIL) !== false;
} 
    
?>