import {
    formatPrice,
    displayCompare,
    addCompareEventListener,
    addFlashMessage,
    fetchData,
    manageCartLink,
    addCartEventListenerToLink,
    initCart,
    updateHeaderCart,
    manageCompareLink
} from './library.js';

// Fonction d'initialisation principale
const initializeCartPage = () => {
    const mainContent = document.querySelector('.main_content');

    if (mainContent) {
        const cart = JSON.parse(mainContent.dataset.cart || 'false');
        if (cart) {
            initCart(cart);
            updateHeaderCart(cart);
        } else {
            console.warn("Aucune donnée panier disponible.");
        }
    }
};

// Fonction pour initialiser le bloc de comparaison si présent
const initializeCompareSection = () => {
    const compareContainer = document.querySelector('.compare_container');

    if (compareContainer) {
        const compare = JSON.parse(compareContainer.dataset.compare || 'false');
        addCompareEventListener();
        displayCompare(compare);
    }
};

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    initializeCartPage();
    initializeCompareSection();
});
