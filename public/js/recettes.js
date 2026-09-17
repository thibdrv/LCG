import { myFetch } from "./fetch.js";
import { detailRecette } from "./recetteDetail.js";

let allRecettes = [];
let currentPage = 1;
let perPage = "all";

export function recettes() {
  const main = document.getElementById("main");
  main.innerHTML = `
    <div>
        <h2>Nombre de recettes : <div id="recetteCount" </div> </h2>
          <div class="recettes-filter">
            <label for="filterCat">Filtrer par catégorie :</label>
            <select id="filterCat">
            <option value=""> Toutes </option>
            </select>

            <label for="perPageSelect">Par page :</label>
            <select id="perPageSelect">
              <option value="10">10</option>
              <option value="20" selected>20</option>
              <option value="50">50</option>
            </select>
          </div>
    </div>
    <div id="recettesList" class="recettes-grid"></div>
    <div id="pagination" class="pagination"></div>
  `;

  document.getElementById("perPageSelect").addEventListener("change", (e) => {
    perPage = e.target.value === "all" ? "all" : parseInt(e.target.value, 10);
    currentPage = 1;
    renderPage();
  });

  myFetch(null, fillCategories, "api.php?route=Categorie", "GET");
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
  myFetch(null, (data) => {
    allRecettes = data || [];
    currentPage = 1;
    renderPage();
  }, url, "GET");
}

function renderPage() {
  const countSpan = document.getElementById("recetteCount");
  countSpan.textContent = allRecettes.length;

  if (perPage === "all") {
    displayRecettes(allRecettes);
    renderPagination(1); // pas de pagination à afficher
    return;
  }

  const totalPages = Math.max(1, Math.ceil(allRecettes.length / perPage));
  if (currentPage > totalPages) currentPage = totalPages;

  const start = (currentPage - 1) * perPage;
  const pageItems = allRecettes.slice(start, start + perPage);

  displayRecettes(pageItems);
  renderPagination(totalPages);
}

function displayRecettes(data) {
  const list = document.getElementById("recettesList");
  const countSpan = document.getElementById("recetteCount");
  list.innerHTML = "";

  const total = data ? data.length : 0;
  countSpan.textContent = total;

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
      detailRecette(recette.pk_recette);
    });

    list.appendChild(div);
  });
}

function renderPagination(totalPages) {
  const container = document.getElementById("pagination");
  container.innerHTML = "";

  if (totalPages <= 1) return;

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement("button");
    btn.textContent = i;
    btn.className = i === currentPage ? "active" : "";
    btn.addEventListener("click", () => {
      currentPage = i;
      renderPage();
    });
    container.appendChild(btn);
  }
}