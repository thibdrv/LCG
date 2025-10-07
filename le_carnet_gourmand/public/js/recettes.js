import { myFetch } from "./fetch.js";

export function recettes() {
  const main = document.getElementById("main");
  main.innerHTML = `
    <div
        <h2>Liste des recettes 🍲</h2>
        <label for="filterCat">Filtrer par catégorie :</label>
        <select id="filterCat">
        <option value=""> Toutes </option>
        </select>
    </div>
        <div id="recettesList" class="recettes-grid"></div>
    
  `;

  // Charger les catégories dynamiquement
  myFetch(null, fillCategories, "api.php?route=Categorie", "GET");

  // Charger toutes les recettes par défaut
  loadRecettes();
}

function fillCategories(categories) {
  const select = document.getElementById("filterCat");

  categories.forEach(cat => {
    const option = document.createElement("option");
    option.value = cat.pk_categorie;
    option.textContent = cat.nom;
    select.appendChild(option);
  });

  // Ajouter écouteur après chargement
  select.addEventListener("change", () => {
    loadRecettes(select.value);
  });
}

function loadRecettes(catId = "") {
  let url = "api.php?route=Recette";
  if (catId) {
    url += `&categories=${catId}`;
  }

  myFetch(null, displayRecettes, url, "GET");
}

function displayRecettes(data) {
  const list = document.getElementById("recettesList");
  list.innerHTML = "";

  if (!data || data.length === 0) {
    list.innerHTML = "<p>Aucune recette trouvée.</p>";
    return;
  }

  data.forEach(recette => {
    const div = document.createElement("div");
    div.className = "recette-card";
    div.innerHTML = `
      <h3>${recette.nom}</h3>
      ${recette.image ? `<img src="${recette.image}" alt="${recette.nom}">` : ""}
    `;

    div.addEventListener("click", () => {
      window.location.href = `recette.html?id=${recette.pk_recette}`;
    });

    list.appendChild(div);
  });
}