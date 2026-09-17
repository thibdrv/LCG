// script.js
import { myFetch } from "./fetch.js";
import { recettes } from "./recettes.js";  // ⬅️ on importe recettes.js
import { afficheLoginZone, doCreateAccount, doLogin, doLogout } from "./compte.js";
import { partager } from "./formulaireRecette.js";

window.partager = partager;
window.accueil = accueil;
window.recettes = recettes; 
window.doCreateAccount = doCreateAccount;
window.doLogin = doLogin;
window.doLogout = doLogout;


function renderNavbar() {
  const navbar = document.getElementById("navbar");
  navbar.innerHTML = "";

  myFetch(null, (sessionInfo) => {
    let aAccueil = document.createElement("a");
    aAccueil.textContent = "Accueil";
    aAccueil.href = "#";
    aAccueil.onclick = (e) => { e.preventDefault(); accueil(); };
    navbar.appendChild(aAccueil);

    let aRecettes = document.createElement("a");
    aRecettes.textContent = "Recettes";
    aRecettes.href = "#";
    aRecettes.onclick = (e) => { e.preventDefault(); recettes(); };
    navbar.appendChild(aRecettes);

    if (sessionInfo.isLogged) {
      let spanUser = document.createElement("span");
      spanUser.className = "user-info";
      spanUser.textContent = `Bonjour, ${sessionInfo.pseudo ?? "Utilisateur"}`;
      navbar.appendChild(spanUser);

      let aLogout = document.createElement("a");
      aLogout.textContent = "Se déconnecter";
      aLogout.href = "#";
      aLogout.onclick = (e) => { e.preventDefault(); doLogout(); };
      navbar.appendChild(aLogout);
    } else {
      let aSignup = document.createElement("a");
      aSignup.textContent = "S’inscrire";
      aSignup.href = "#";
      aSignup.onclick = (e) => { e.preventDefault(); doCreateAccount(); };
      navbar.appendChild(aSignup);

      let aLogin = document.createElement("a");
      aLogin.textContent = "Se connecter";
      aLogin.href = "#";
      aLogin.onclick = (e) => { e.preventDefault(); doLogin(); };
      navbar.appendChild(aLogin);
    }
  }, "api.php?route=Session", "GET", (error) => {
    console.error("Erreur navbar:", error);
  });
}


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
    <a href="#" id="footerPartager" class="accueil-link">
      <div class="accueil-section">
      <h3>👉 Cliquez ici pour partager votre recette</h3>
        <p>
          Vous avez une recette originale, familiale ou créative que vous aimeriez partager avec la communauté ?  
          Ne la gardez pas pour vous ! Notre site vous permet de publier vos propres recettes afin que d’autres gourmands puissent les découvrir, les cuisiner et les apprécier.  
          Ajoutez vos ingrédients, vos astuces et une belle photo pour mettre en valeur votre plat.
        </p>
      </div>
    </a>
`;

  // Recharge la navbar
  renderNavbar();
}


// Affichage initial au chargement de la page
document.addEventListener("DOMContentLoaded", () => {
    accueil();

    document.getElementById("currentYear").textContent = new Date().getFullYear();

    document.getElementById("footerAccueil")?.addEventListener("click", (e) => {
        e.preventDefault();
        accueil();
    });

    document.getElementById("footerRecettes")?.addEventListener("click", (e) => {
        e.preventDefault();
        recettes();
    });

    // Footer partager recette
    document.getElementById("footerPartager")?.addEventListener("click", (e) => {
        e.preventDefault();
        partager();
    });

    document.getElementById("backToTop")?.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });

});