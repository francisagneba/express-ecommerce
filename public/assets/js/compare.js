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

window.onload = () => {
    console.log("compare");

    let mainContent = document.querySelector('.compare_container');

    if (mainContent) {
        let compare = JSON.parse(mainContent?.dataset?.compare || '[]');
        addCompareEventListener();
        displayCompare(compare);
    }
};