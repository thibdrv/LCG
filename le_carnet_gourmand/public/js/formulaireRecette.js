import { myFetch } from "./fetch.js";

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formRecette");
  const selectCat = document.getElementById("categorie");

  if (!form) {
    console.error("⚠️ Formulaire #formRecette introuvable dans le DOM !");
    return;
  }

  // 🔹 Charger dynamiquement les catégories
  myFetch(
    null,
    (categories) => {
      selectCat.innerHTML = '<option value=""> Sélectionner une catégorie </option>';
      categories.forEach(cat => {
        let opt = document.createElement("option");
        opt.value = cat.pk_categorie;
        opt.textContent = cat.nom;
        selectCat.appendChild(opt);
      });
    },
    "api.php?route=Categorie",
    "GET"
  );

  // 🔹 Soumission du formulaire
  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    formData.set("route", "Recette"); // sécurité

    console.log("Envoi des données :", Object.fromEntries(formData));

    myFetch(
      formData,
      (data) => {
        alert("Recette créée avec succès 🎉");
        window.location.href = "index.html"; // retour à l’accueil
      },
      "api.php",
      "POST",
      (error) => {
        alert("Erreur lors de la création de la recette : " + error.message);
      }
    );
  });
});