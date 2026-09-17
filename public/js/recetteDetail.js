import { myFetch } from "./fetch.js";

let currentRecetteId = null;

export function detailRecette(id) {
  currentRecetteId = id;
  const main = document.getElementById("main");
  main.innerHTML = `<div id="recetteDetail">Chargement...</div>`;

  if (!id) {
    document.getElementById("recetteDetail").innerHTML = "<p>Recette introuvable.</p>";
    return;
  }

  myFetch(
    null,
    displayRecette,
    `api.php?route=Recette&pk=${id}`,
    "GET",
    (error) => {
      document.getElementById("recetteDetail").innerHTML =
        `<p>Erreur lors du chargement de la recette : ${error.message}</p>`;
    }
  );
}

function displayRecette(recette) {
  const container = document.getElementById("recetteDetail");
  if (!recette) {
    container.innerHTML = "<p>Recette introuvable.</p>";
    return;
  }

  container.innerHTML = `
    <div class="recette-actions">
      <h2>${recette.nom}</h2>

        <div class="recette-fav_note">
          <button id="favoriBtn">Ajouter aux favoris</button>
          <div id="noteZone">
            <span id="noteMoyenne">Note : --</span>
            <span id="noterZone"></span>
        </div>
      </div>
    </div>

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

    <div class="recette-commentaires">
      <h3>Commentaires</h3>
      <div id="commentForm"></div>
      <div id="commentairesList">Chargement des commentaires...</div>
    </div>
  `;

  loadFavoriState();
  loadNoteMoyenne();
  loadNoterZone();
  loadCommentaires();
}

// ---------- FAVORIS ----------

function loadFavoriState() {
  const btn = document.getElementById("favoriBtn");

  myFetch(
    null,
    (data) => {
      updateFavoriButton(data.estFavori);
    },
    `api.php?route=Favori&pk_recette=${currentRecetteId}`,
    "GET",
    (error) => {
      // Non connecté ou erreur : on désactive juste le bouton visuellement
      btn.disabled = true;
      btn.title = "Connectez-vous pour ajouter en favori";
    }
  );

  btn.addEventListener("click", () => {
    const isFavori = btn.dataset.favori === "true";
    if (isFavori) {
      removeFavori();
    } else {
      addFavori();
    }
  });
}

function updateFavoriButton(estFavori) {
  const btn = document.getElementById("favoriBtn");
  btn.dataset.favori = estFavori ? "true" : "false";
  btn.textContent = estFavori ? "Retirer des favoris" : "Ajouter aux favoris";
  btn.classList.toggle("favori-actif", estFavori);
}

function addFavori() {
  const formData = new URLSearchParams();
  formData.append("route", "Favori");
  formData.append("fk_recette", currentRecetteId);

  myFetch(
    formData,
    () => updateFavoriButton(true),
    "api.php",
    "POST",
    (error) => alert("Erreur : " + error.message)
  );
}

function removeFavori() {
  myFetch(
    null,
    () => updateFavoriButton(false),
    `api.php?route=Favori&fk_recette=${currentRecetteId}`,
    "DELETE",
    (error) => alert("Erreur : " + error.message)
  );
}

// ---------- NOTES ----------

function loadNoteMoyenne() {
  myFetch(
    null,
    (data) => {
      renderStarsAverage(data.moyenne ?? 0);
    },
    `api.php?route=Note&pk_recette=${currentRecetteId}`,
    "GET",
    (error) => {
      document.getElementById("noteMoyenne").textContent = "Note indisponible";
    }
  );
}

function renderStarsAverage(moyenne) {
  const container = document.getElementById("noteMoyenne");
  let starsHtml = "";
  for (let i = 1; i <= 5; i++) {
    if (moyenne >= i) {
      starsHtml += "★";
    } else if (moyenne >= i - 0.5) {
      starsHtml += "⯨"; // demi-étoile (à ajuster selon la police/icônes disponibles)
    } else {
      starsHtml += "☆";
    }
  }
  container.innerHTML = `<span class="stars-average">${starsHtml}</span> (${moyenne}/5)`;
}

function loadNoterZone() {
  const zone = document.getElementById("noterZone");
  zone.innerHTML = `
    <p>Votre note :</p>
    <div id="starRating" class="star-rating">
      ${[1,2,3,4,5].map(i => `<span class="star" data-value="${i}">☆</span>`).join("")}
    </div>
  `;

  let selectedNote = 0;
  const stars = zone.querySelectorAll(".star");

  stars.forEach(star => {
    star.addEventListener("mouseenter", () => {
      highlightStars(stars, parseInt(star.dataset.value, 10));
    });

    star.addEventListener("click", () => {
      selectedNote = parseInt(star.dataset.value, 10);
      envoyerNote(selectedNote);
    });
  });

  zone.addEventListener("mouseleave", () => {
    highlightStars(stars, selectedNote);
  });
}

function highlightStars(stars, value) {
  stars.forEach(star => {
    const starValue = parseInt(star.dataset.value, 10);
    star.textContent = starValue <= value ? "★" : "☆";
  });
}

function envoyerNote(note) {
  const formData = new URLSearchParams();
  formData.append("route", "Note");
  formData.append("fk_recette", currentRecetteId);
  formData.append("note", note);

  myFetch(
    formData,
    () => {
      loadNoteMoyenne();
    },
    "api.php",
    "POST",
    (error) => alert("Erreur : " + error.message)
  );
}

// ---------- COMMENTAIRES ----------

function loadCommentaires() {
  renderCommentForm();

  myFetch(
    null,
    displayCommentaires,
    `api.php?route=Commentaire&fk_recette=${currentRecetteId}`,
    "GET",
    (error) => {
      document.getElementById("commentairesList").innerHTML =
        `<p>Erreur lors du chargement des commentaires : ${error.message}</p>`;
    }
  );
}

function renderCommentForm() {
  const formZone = document.getElementById("commentForm");
  formZone.innerHTML = `
    <form id="addCommentForm">
      <textarea id="commentContenu" placeholder="Votre commentaire..." required></textarea>
      <button id="FormSubmit" type="submit">Publier</button>
    </form>
  `;

  document.getElementById("addCommentForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const contenu = document.getElementById("commentContenu").value.trim();
    if (contenu.length < 2) {
      alert("Le commentaire est trop court.");
      return;
    }

    const formData = new URLSearchParams();
    formData.append("route", "Commentaire");
    formData.append("fk_recette", currentRecetteId);
    formData.append("contenu", contenu);

    myFetch(
      formData,
      () => {
        document.getElementById("commentContenu").value = "";
        alert("Commentaire envoyé, en attente de modération.");
      },
      "api.php",
      "POST",
      (error) => alert("Erreur : " + error.message)
    );
  });
}

function displayCommentaires(commentaires) {
  const list = document.getElementById("commentairesList");
  list.innerHTML = "";

  if (!commentaires || commentaires.length === 0) {
    list.innerHTML = "<p>Aucun commentaire pour l'instant.</p>";
    return;
  }

  commentaires.forEach(c => {
    const div = document.createElement("div");
    div.className = "commentaire";
    div.innerHTML = `
      <p><strong>${c.pseudo ?? "Utilisateur"}</strong> — ${c.date_creation ?? ""}</p>
      <p>${c.contenu}</p>
    `;
    list.appendChild(div);
  });
}

function mettreAJourNote(note) {
  const formData = new URLSearchParams();
  formData.append("route", "Note");        // ← route ajoutée ICI, dans le formData
  formData.append("fk_recette", currentRecetteId);
  formData.append("note", note);

  myFetch(
    formData,
    () => {
      loadNoteMoyenne();
    },
    "api.php",                              // ← URL SANS "?route=Note"
    "PUT",
    (error) => alert("Erreur lors de la mise à jour de la note : " + error.message)
  );
}