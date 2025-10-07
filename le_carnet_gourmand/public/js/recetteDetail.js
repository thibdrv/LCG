// recetteDetail.js
import { myFetch } from "./fetch.js";

document.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get("id");

  if (!id) {
    document.getElementById("recetteDetail").innerHTML = "<p>Recette introuvable.</p>";
    return;
  }

  myFetch(null, displayRecette, `api.php?route=Recette&pk=${id}`, "GET");
});

function displayRecette(recette) {
  const container = document.getElementById("recetteDetail");
  if (!recette) {
    container.innerHTML = "<p>Recette introuvable.</p>";
    return;
  }

  container.innerHTML = `
    <h2>${recette.nom}</h2>

    ${recette.image 
      ? `<div class="recette-image">
           <img src="${recette.image}" alt="${recette.nom}" style="max-width:400px;">
         </div>` 
      : ""}

    <div class="recette-infos">
      <div class="ingredients">
        <p><strong>Ingrédients :</strong></p>
        <p>${recette.ingredients}</p>
      </div>
      <div class="details">
        <p><strong>Détails :</strong></p>
        <p>${recette.details}</p>
      </div>
    </div>

    ${recette.lien 
      ? `<div class="recette-lien">
           <h3>Voir en vidéo :</h3>
           <iframe src="${recette.lien}" frameborder="0" allowfullscreen></iframe>
         </div>` 
      : ""}
  `;
}