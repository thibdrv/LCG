// public/js/accueil.js

import { myFetch } from './fetch.js';
import { afficheLoginZone } from './compte.js';

let accueilLoaded = false;

export function accueil() {
    // Empêcher les doubles appels
    if (accueilLoaded) {
        console.log("🏠 Accueil déjà chargé");
        return;
    }
    
    console.log("🏠 Accueil chargé !");
    accueilLoaded = true;
    
    const main = document.getElementById("main");
    if (!main) {
        console.error("⚠️ Impossible de trouver #main dans le DOM");
        return;
    }

    // Appel de l'affichage de la page
    renderAccueil(main);
} // <-- Accolade de fermeture ajoutée ici !

function renderAccueil(main) {
    // Afficher le contenu de la page d'accueil
    main.innerHTML = `
        <div style="max-width: 900px; margin: 0 auto; padding: 20px;">
            <h2 style="font-size: 36px; font-weight: 700; color: #1a1a2e; margin-bottom: 10px;">
                Bienvenue sur Cook & Share 🍳
            </h2>
            <p style="color: #666; font-size: 18px; margin-bottom: 40px;">
                Découvrez, partagez et inspirez-vous avec notre communauté de passionnés de cuisine !
            </p>

            <!-- Section YouTube -->
            <a href="https://www.youtube.com/channel/UCOmtyPKnEgK3p1vRHU7Ie1A" 
                target="_blank" 
                style="text-decoration: none; display: block; margin-bottom: 20px;">
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: transform 0.3s;">
                    <h3 style="color: #e94560; margin-bottom: 10px;">🎬 Découvrez nos recettes en vidéo</h3>
                    <p style="color: #444; line-height: 1.6;">
                        Retrouvez une sélection de recettes gourmandes et faciles à réaliser directement en vidéo.  
                        Pas à pas, vous apprendrez à cuisiner comme un chef en suivant nos tutoriels sur notre chaîne YouTube.  
                        Que vous soyez débutant ou passionné de cuisine, il y en a pour tous les goûts !
                    </p>
                    <span style="display: inline-block; margin-top: 10px; color: #e94560; font-weight: 600;">Voir la chaîne →</span>
                </div>
            </a>

            <!-- Section Partagez vos recettes -->
            <a href="#" onclick="event.preventDefault(); window.formulaireRecette();" 
                style="text-decoration: none; display: block; margin-bottom: 20px;">
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: transform 0.3s;">
                    <h3 style="color: #00d2ff; margin-bottom: 10px;">📝 Partagez vos recettes</h3>
                    <p style="color: #444; line-height: 1.6;">
                        Vous avez une recette originale, familiale ou créative que vous aimeriez partager avec la communauté ?  
                        Ne la gardez pas pour vous ! Notre site vous permet de publier vos propres recettes afin que d'autres gourmands puissent les découvrir, les cuisiner et les apprécier.  
                        Ajoutez vos ingrédients, vos astuces et une belle photo pour mettre en valeur votre plat.
                    </p>
                    <p style="color: #1a1a2e; font-weight: 600;">👉 Cliquez ici pour partager votre recette</p>
                    <span style="display: inline-block; margin-top: 10px; color: #00d2ff; font-weight: 600;">Partager maintenant</span>
                </div>
            </a>
            
            <!-- Zone de connexion / déconnexion -->
            <div id="login-area" style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 12px;">
                <p style="text-align: center; color: #666;">Chargement de la session...</p>
            </div>
        </div>
    `;

    // Gérer la zone de connexion
    manageLoginArea();
}

function manageLoginArea() {
    myFetch(null, afficheLoginZone, 'api.php?route=Session', 'GET');
}
