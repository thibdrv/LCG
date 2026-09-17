import { AuthenticationException } from './exceptions.js';

const defaultResponseCallback = function(response) {
    return response.json().then(data => {
        if (!response.ok) {
            const msg = data.error || 'Network response was not ok';
            if (response.status === 499) {
                throw new AuthenticationException(msg);
            }
            const err = new Error(msg);
            err.status = response.status;
            throw err;
        }
        return data;
    });
}

const defaultDataCallback = function(data) {
    console.log("Data :\n", data);
}

const defaultErrorCallback = function(error) {
    console.error('Erreur:', error);
}

// FETCH Fait la communication : api.fetch (donnée du form) -> api.php(REST) -> controller
// formData = formulaire / URL = point d'entrée : API.PHP -> transmet au Controller...
// Tout est transmis a API.PHP
export function myFetch(formData, dataCallback, url, method, errorCallback = null, responseCallback = null, contentType = 'application/x-www-form-urlencoded') {
    fetch(url, {
        method: method,
        headers: { 'Content-Type': contentType },
        body: method == 'GET' || method == 'DELETE' ? null : new URLSearchParams(formData).toString() // DELETE et GET n'ont pas de body, les paramètres passent dans l'URL
    })
    .then(response => (responseCallback == null ? defaultResponseCallback(response) : responseCallback(response) ) )
    .then(data => (dataCallback == null ? defaultDataCallback(data) : dataCallback(data) ))
    .catch(error => (errorCallback == null ? defaultErrorCallback(error): errorCallback(error) ));
}