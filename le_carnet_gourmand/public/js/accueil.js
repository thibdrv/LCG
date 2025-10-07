import { myFetch } from './fetch.js';
import { afficheLoginZone } from './compte.js';

export function accueil() {
    console.log("Accueil chargé !");
    manageLoginArea();
}

function manageLoginArea() {
    myFetch(null, afficheLoginZone, 'api.php?route=Session', 'GET');
}
