import { myFetch } from './fetch.js';
import { closeModal, prepareModal } from './modal.js';

export function afficheLoginZone(sessionInfo) {
    const loginArea = document.getElementById("loginArea");
    loginArea.innerHTML = "";

    if (sessionInfo.isLogged) {
        const spanUser = document.createElement('span');
        spanUser.textContent = `Bonjour, ${sessionInfo.pseudo ?? "Utilisateur"}`;
        loginArea.appendChild(spanUser);

        let aLogout = document.createElement('a');
        aLogout.textContent = "Se déconnecter";
        aLogout.href = "#";
        aLogout.addEventListener('click', (e) => {
            e.preventDefault();
            doLogout();
        });
        loginArea.appendChild(aLogout);

    } else {
        let aLogin = document.createElement('a');
        aLogin.textContent = "S'identifier";
        aLogin.href = "#";
        aLogin.addEventListener('click', (e) => {
            e.preventDefault();
            doLogin();
        });
        loginArea.appendChild(aLogin);

        let aCreate = document.createElement('a');
        aCreate.textContent = "Créer un compte";
        aCreate.href = "#";
        aCreate.addEventListener('click', (e) => {
            e.preventDefault();
            doCreateAccount();
        });
        loginArea.appendChild(aCreate);
    }
}

export function doLogin() {
    const container = prepareModal();
    const form = document.createElement('form');
    form.innerHTML = `
        <input type="hidden" name="route" value="Login">
        <input type="email" name="email" placeholder="Votre email" required>
        <input type="password" name="mot_de_passe" placeholder="Votre mot de passe" required>
        <button type="submit">Se connecter</button>
    `;
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        myFetch(new FormData(form), () => {
            closeModal();
            window.accueil();
        }, 'api.php', 'POST');
    });
    container.appendChild(form);
}

export function doCreateAccount() {
    const container = prepareModal();
    const form = document.createElement('form');
    form.innerHTML = `
        <input type="hidden" name="route" value="Compte">
        <input type="email" name="email" placeholder="Votre email" required>
        <input type="text" name="pseudo" placeholder="Votre pseudo" required>
        <input type="password" name="mot_de_passe" placeholder="Votre mot de passe" required>
        <input type="password" name="password_confirm" placeholder="Confirmez le mot de passe" required>
        <button type="submit">Créer le compte</button>
    `;
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const pwd = form.querySelector('input[name="mot_de_passe"]').value;
        const pwd2 = form.querySelector('input[name="password_confirm"]').value;
        if (pwd !== pwd2) {
            alert("Les mots de passe ne correspondent pas.");
            return;
        }
        myFetch(new FormData(form), () => {
            alert("Compte créé !");
            closeModal();
            window.accueil();
        }, 'api.php', 'POST');
    });
    container.appendChild(form);
}

export function doLogout() {
    myFetch(null, () => {
        window.accueil();
    }, 'api.php?route=Logout', 'GET');
}
