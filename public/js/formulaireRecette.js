import { myFetch } from "./fetch.js";

export function partager() {
  const main = document.getElementById("main");
  main.innerHTML = `
    <h2>Partager une recette</h2>
    <form id="formRecette">
      <label for="nom">Nom de la recette</label>
      <input type="text" id="nom" name="nom" required>

      <label>Catégories</label>
      <div id="categoriesList">Chargement des catégories...</div>

      <label for="ingredients">Ingrédients (un par ligne)</label>
      <textarea id="ingredients" name="ingredients" rows="6" required placeholder="200g de farine&#10;3 oeufs&#10;..."></textarea>

      <label for="details">Description</label>
      <textarea id="details" name="details" required></textarea>

      <div class="error-message" style="color:red;"></div>
      <button type="submit">Créer la recette</button>
    </form>
  `;

  const errorDiv = document.querySelector("#formRecette .error-message");

  myFetch(
    null,
    (categories) => {
      const list = document.getElementById("categoriesList");
      list.innerHTML = categories.map(cat => `
        <label>
          <input type="checkbox" name="categories" value="${cat.pk_categorie}">
          ${cat.nom}
        </label>
      `).join("");
    },
    "api.php?route=Categorie",
    "GET",
    (error) => {
      document.getElementById("categoriesList").innerHTML = "Erreur de chargement des catégories.";
    }
  );

  const form = document.getElementById("formRecette");
  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const categoriesCoches = Array.from(
      form.querySelectorAll('input[name="categories"]:checked')
    ).map(cb => cb.value);

    if (categoriesCoches.length === 0) {
      errorDiv.textContent = "Sélectionnez au moins une catégorie.";
      return;
    }

    const formData = new URLSearchParams();
    formData.append("route", "Recette");
    formData.append("nom", document.getElementById("nom").value.trim());
    formData.append("details", document.getElementById("details").value.trim());
    formData.append("ingredients", document.getElementById("ingredients").value.trim());
    formData.append("fk_categorie", categoriesCoches[0]); // première catégorie, requise à la création

    myFetch(
      formData,
      (data) => {
        const pkRecette = data["recette n°"]; // nom de clé renvoyé par RecettePostController
        ajouterCategoriesSupplementaires(pkRecette, categoriesCoches.slice(1));
      },
      "api.php",
      "POST",
      (error) => {
        errorDiv.textContent = error.message || "Erreur lors de la création de la recette.";
      }
    );
  });
}

function ajouterCategoriesSupplementaires(pkRecette, categoriesRestantes) {
  if (categoriesRestantes.length === 0) {
    alert("Recette créée avec succès 🎉");
    window.recettes();
    return;
  }

  const [premiere, ...reste] = categoriesRestantes;
  const formData = new URLSearchParams();
  formData.append("route", "CategorieRecette");
  formData.append("fk_recette", pkRecette);
  formData.append("fk_categorie", premiere);

  myFetch(
    formData,
    () => ajouterCategoriesSupplementaires(pkRecette, reste),
    "api.php",
    "POST",
    (error) => {
      console.error("Erreur association catégorie supplémentaire:", error);
      // On continue quand même les autres catégories plutôt que de tout bloquer
      ajouterCategoriesSupplementaires(pkRecette, reste);
    }
  );
}