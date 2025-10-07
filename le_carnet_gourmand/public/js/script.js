// script.js
import { myFetch } from "./fetch.js";
import { recettes } from "./recettes.js";  // ⬅️ on importe recettes.js
import { doCreateAccount, doLogin, doLogout } from "./compte.js";

// On expose uniquement les fonctions globales utiles
window.accueil = accueil;
window.recettes = recettes; // ⬅️ expose la fonction recettes au global
window.doCreateAccount = doCreateAccount;
window.doLogin = doLogin;
window.doLogout = doLogout;

function accueil() { 
  const main = document.getElementById("main");
  if (!main) {
    console.error("⚠️ Impossible de trouver #main dans le DOM");
    return;
  }

  main.innerHTML = `
    <h2>Bienvenue sur l'accueil</h2>

    <!-- Section YouTube -->
    <a href="https://www.youtube.com/channel/UCOmtyPKnEgK3p1vRHU7Ie1A" 
      target="_blank" 
      class="accueil-link">
      <div class="accueil-section">
        <h3>Découvrez nos recettes en vidéos !</h3>
        <p>
          Retrouvez une sélection de recettes gourmandes et faciles à réaliser directement en vidéo.  
          Pas à pas, vous apprendrez à cuisiner comme un chef en suivant nos tutoriels sur notre chaîne YouTube.  
          Que vous soyez débutant ou passionné de cuisine, il y en a pour tous les goûts !
        </p>
      </div>
    </a>

    <!-- Section Partagez vos recettes -->
    <a href="formulaireRecette.html" class="accueil-link">
      <div class="accueil-section">
        <h3>Partagez vos recettes</h3>
        <p>
          Vous avez une recette originale, familiale ou créative que vous aimeriez partager avec la communauté ?  
          Ne la gardez pas pour vous ! Notre site vous permet de publier vos propres recettes afin que d’autres gourmands puissent les découvrir, les cuisiner et les apprécier.  
          Ajoutez vos ingrédients, vos astuces et une belle photo pour mettre en valeur votre plat.
        </p>
        <p><strong>👉 Cliquez ici pour partager votre recette</strong></p>
      </div>
    </a>
`;

  // ✅ Gestion du lien "Créer une recette"
const createLink = document.getElementById("createRecetteLink");
if (createLink) {
  createLink.addEventListener("click", (e) => {
    e.preventDefault();
    // Redirection directe vers le formulaire
    window.location.href = "formulaireRecette.html";
  });
}

  // Recharge la navbar
  renderNavbar();
}



function renderNavbar() {
  const navbar = document.getElementById("navbar");
  navbar.innerHTML = "";

  // Récupérer l’état de la session pour afficher les bons boutons
  myFetch(null, (sessionInfo) => {
    // ✅ Bouton Accueil
    let aAccueil = document.createElement("a");
    aAccueil.textContent = "Accueil";
    aAccueil.href = "#";
    aAccueil.onclick = (e) => { e.preventDefault(); accueil(); };
    navbar.appendChild(aAccueil);

    // ✅ Bouton Recettes
    let aRecettes = document.createElement("a");
    aRecettes.textContent = "Recettes";
    aRecettes.href = "#";
    aRecettes.onclick = (e) => { e.preventDefault(); recettes(); }; // ⬅️ utilise la fonction de recettes.js
    navbar.appendChild(aRecettes);

    if (sessionInfo.isLogged) {
      // ✅ Message utilisateur
      let spanUser = document.createElement("span");
      spanUser.className = "user-info";
      spanUser.textContent = `Bonjour, ${sessionInfo.pseudo ?? "Utilisateur"}`;
      navbar.appendChild(spanUser);

      // ✅ Déconnexion
      let aLogout = document.createElement("a");
      aLogout.textContent = "Se déconnecter";
      aLogout.href = "#";
      aLogout.onclick = (e) => { e.preventDefault(); doLogout(); };
      navbar.appendChild(aLogout);
    } else {
      // ✅ Inscription
      let aSignup = document.createElement("a");
      aSignup.textContent = "S’inscrire";
      aSignup.href = "#";
      aSignup.onclick = (e) => { e.preventDefault(); doCreateAccount(); };
      navbar.appendChild(aSignup);

      // ✅ Connexion
      let aLogin = document.createElement("a");
      aLogin.textContent = "Se connecter";
      aLogin.href = "#";
      aLogin.onclick = (e) => { e.preventDefault(); doLogin(); };
      navbar.appendChild(aLogin);
    }
  }, "api.php?route=Session", "GET");
}
