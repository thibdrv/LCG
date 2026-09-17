# Le Carnet Gourmand

Application web de partage de recettes de cuisine. Le front-end est en JavaScript vanilla (SPA légère) et le back-end en PHP orienté objet, avec une architecture en couches (4-tiers).

Développé dans un OBJECTIF d'APPRENTISSAGE. Bien que NON TERMINE, ce projet m'a permis de pratiquer la conception d'architecture logicielle, la mise en place de tests automatisés et l'organisation du développement via un suivi Kanban sur Trello.

Déploiement : En ligne via FileZilla / Hébergé sur Alwaysdata

## 📋 Sommaire

- [Architecture générale](#architecture-générale)
- [Back-end — Architecture en couches](#back-end--architecture-en-couches)
- [Front-end — SPA légère](#front-end--spa-légère)
- [Authentification](#authentification)
- [Fonctionnalités](#fonctionnalités)
- [Installation](#installation)

## Architecture générale

```
Requête HTTP (front JS)
        ↓
   api.php (point d'entrée unique)
        ↓
   Controllers (validation + orchestration)
        ↓
   Services (logique métier + règles de sécurité)
        ↓
   DAOs (accès base de données)
        ↓
   Entities (représentation des données)
```

Chaque couche ne connaît que celle immédiatement en dessous d'elle. Un contrôleur ne fait jamais de SQL directement, un service ne construit jamais de réponse JSON. Cette séparation permet de faire évoluer une couche sans impacter les autres.

## Back-end : Architecture en couches

### `api.php` : Point d'entrée unique

Tous les appels du front transitent par ce seul fichier, qui :
- démarre la session PHP ;
- extrait les données de la requête selon la méthode HTTP (`extractForm()` gère GET, POST, PUT, DELETE) ;
- détermine la route demandée (`extractRoute()`, ex. `route=Recette`) ;
- instancie dynamiquement le contrôleur correspondant à la route **et** à la méthode HTTP (ex. `route=Recette` + `GET` → `RecetteGetController`) ;
- exécute le contrôleur et renvoie sa réponse en JSON ;
- centralise la gestion des erreurs via un `catch(HttpStatusException)`.

### Controllers : Validation et orchestration

Chaque route/méthode possède son propre contrôleur (`RecetteGetController`, `NotePostController`, etc.), tous héritant d'`AbstractController`, qui impose un cycle de vie en 4 étapes :

| Étape | Rôle |
|---|---|
| `checkForm()` | Les paramètres attendus sont-ils présents ? |
| `checkCybersec()` | Sont-ils du bon type/format (entier, email valide, longueur minimale…) ? |
| `checkRights()` | L'utilisateur a-t-il le droit d'effectuer cette action (connecté, admin…) ? |
| `processRequest()` | Exécute la logique métier via un Service et prépare `$this->response` |

`AbstractController::processResponse()` encode ensuite la réponse en JSON de façon uniforme pour tous les contrôleurs.

### Services : Logique métier

Chaque entité possède son service (`NoteService`, `FavoriService`, `CommentaireService`…), qui porte les règles métier : validation des bornes (ex. une note entre 0 et 5, par pas de 0.5), contrôle d'accès fin (un utilisateur ne peut consulter que ses propres notes, sauf s'il est admin), détection des doublons, etc.

Les services héritent d'`AbstractService`, qui fournit les opérations CRUD génériques (`insert`, `update`, `delete`, `findByPk`, `findAll`), déléguées au DAO correspondant.

### DAOs (Data Access Objects) : Accès base de données

Seul endroit du code où du SQL est écrit, exclusivement via des requêtes préparées PDO (protection contre l'injection SQL). Les DAOs héritent d'`AbstractDao`, qui centralise les opérations communes.

> Certaines tables utilisent une **clé primaire composite** (ex. `mettre_note`, identifiée par `fk_compte` + `fk_recette`), gérée explicitement dans le DAO concerné plutôt que par la clé simple du DAO abstrait.

### Entities : Représentation des données

Classes simples (`Recette`, `Compte`, `Note`, `Commentaire`…) représentant une ligne de base de données sous forme d'objet PHP, avec getters/setters. Elles ne contiennent ni logique métier ni SQL — de purs objets de transport entre les couches.

### Utilitaires transverses

- **`SessionManager`** — gère le cycle de vie de la session (login, logout, expiration).
- **`functions.php`** — fonctions globales (`isLogged()`, `getCurrentUser()`, `sanitizeString()`, les helpers `_400_Bad_Request()`, `_401_Unauthorized()`, etc. qui formatent les réponses d'erreur HTTP).
- **`HttpStatusException`** — exception dédiée portant un code HTTP, remontée jusqu'à `api.php` pour une gestion d'erreurs centralisée et uniforme.

## Front-end : SPA légère

Un unique fichier `index.html` porte la structure statique (header, navbar, footer, modale de connexion). Tout le contenu dynamique est injecté dans `#main` par JavaScript, via des fonctions comme :

- `accueil()` — page d'accueil
- `recettes()` — liste des recettes, avec filtrage par catégorie et pagination
- `partager()` — formulaire de création de recette
- `detailRecette(id)` — détail d'une recette (ingrédients, commentaires, notes, favoris)

Chacune de ces fonctions remplace le contenu de `#main` sans recharger la page.

Toutes les communications avec le backend passent par une fonction unique, **`myFetch()`** (dans `fetch.js`), qui uniformise la gestion des succès et des erreurs pour l'ensemble des appels API.

### Structure des fichiers JS :

| Fichier | Rôle |
|---|---|
| `script.js` | Point d'entrée, navigation, navbar dynamique |
| `fetch.js` | Wrapper unique pour tous les appels à l'API |
| `accueil.js` | *(page d'accueil, si présente)* |
| `recettes.js` | Liste des recettes, filtres, pagination |
| `recetteDetail.js` | Détail recette : favoris, notes, commentaires |
| `formulaireRecette.js` | Création d'une recette |
| `compte.js` | Connexion, inscription, déconnexion |
| `modal.js` | Gestion de la fenêtre modale |
| `exceptions.js` | Exceptions front personnalisées |

## Authentification

### Connexion

1. `compte.js` (`doLogin()`) ouvre une modale avec un formulaire email / mot de passe.
2. À la soumission, un `POST` est envoyé vers `api.php` avec `route=Login`.
3. `LoginPostController` vérifie les identifiants via `CompteService`, initialise la session (`SessionManager::login()`) et stocke les informations utilisateur nécessaires en session (pseudo, email, rôle **jamais le mot de passe**).
4. La navbar se rafraîchit automatiquement (`script.js`) en interrogeant `SessionGetController` pour afficher l'état de connexion courant.

### Identifiants de test

Si vous souhaitez vous connecter, un compte de test est disponible :

* **Email :** ceci@estuntest.fr
* **Mot de passe :** AZERTYUIOPazertyuiop!

### Création de compte

1. `doCreateAccount()` ouvre un formulaire (email, pseudo, mot de passe + confirmation).
2. Le front vérifie la correspondance des deux mots de passe avant l'envoi.
3. `POST` vers `route=Compte` → `ComptePostController` crée l'entrée en base.
4. L'utilisateur doit ensuite se connecter séparément (pas de connexion automatique post-inscription).

## Fonctionnalités

- 🔐 Inscription / connexion / déconnexion
- 📖 Consultation des recettes, filtrage par catégorie, pagination configurable (10 / 20 / 50 / toutes)
- ➕ Partage d'une recette (nom, catégories multiples, ingrédients, description)
- ⭐ Notation des recettes (0 à 5, par pas de 0.5), modification possible
- ❤️ Ajout / retrait des favoris
- 💬 Commentaires sur une recette (soumis à modération avant publication)

## Installation

```bash
# Cloner le dépôt
git clone <https://github.com/thibdrv/LCG>

# Configurer la base de données
# (importer le schéma SQL, configurer les identifiants de connexion)

# Lancer un serveur PHP local (ex. XAMPP) pointant sur la racine du projet
```

> ⚠️ Assure-toi que les appels API dans le front (`fetch.js` et les fichiers appelants) pointent vers le bon chemin selon ton environnement de déploiement (local ou hébergement distant).

_________

### Ce que ce projet m'a apporté :

- Architecture full-stack : Structuration propre de l'application avec une séparation claire entre le frontend et le backend.

- Culture du test : Écriture de tests unitaires et d'intégration pour sécuriser le code et valider la logique métier.

- Bonnes pratiques : Organisation des dossiers, gestion des données et découpage des composants.

### Problèmes rencontrés et solutions :

- Complexité de la structure : Recherche de l'équilibre parfait au niveau de l'architecture pour éviter la sur-complexification initiale.

- Configuration des tests : Simulation des données (mocking) et configuration fine des environnements de test frontend et backend.

- Gestion des erreurs : Interception et traitement propre des erreurs réseau et des cas limites côté client.

### Technologies utilisées :

- Frontend : HTML5 / CSS3 / JavaScript

- Backend : PHP

- Tests & API : Postman

- Base de données : MariaDB

- Gestion de projet & Déploiement : Trello (Kanban), FileZilla, Alwaysdata
