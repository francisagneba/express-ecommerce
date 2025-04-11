import {
    displayCompare,
    displayCart,
    displayWishlist,
    formatPrice,
    addCartEventListenerToLink,
    addWiwhListEventListenerToLink,
    addCompareEventListener
} from './library.js';

window.onload = () => {
    let mainContent;

    // ===================== COMPARE =====================
    console.log("compare");
    mainContent = document.querySelector('.compare_container');
    if (mainContent) {
        let compare = JSON.parse(mainContent.dataset.compare || 'null');
        displayCompare(compare);
    }

    // ===================== WISHLIST =====================
    console.log("wishlist");
    mainContent = document.querySelector('.wishlist_content');
    if (mainContent) {
        let wishlist = JSON.parse(mainContent.dataset.wishlist || 'null');
        displayWishlist(wishlist);
    }

    // ===================== CART =====================
    console.log("cart");
    mainContent = document.querySelector('.cart_content');
    if (mainContent) {
        let cart = JSON.parse(mainContent.dataset.cart || 'null');
        displayCart(cart);

        // Gestion du formulaire de transporteur
        const form = document.querySelector(".carrier_form form");
        const select = document.querySelector(".carrier_form select");

        let carriers = mainContent.dataset.carriers ? JSON.parse(mainContent.dataset.carriers) : [];

        if (carriers && carriers.length > 0 && select) {
            select.innerHTML = "";

            carriers.forEach(carrier => {
                let selected = cart?.carrier?.id === carrier.id ? "selected" : "";
                select.innerHTML += `
                    <option value="${carrier.id}" ${selected}>
                        ${carrier.name} (${formatPrice(carrier.price / 100)})
                    </option>
                `;
            });

            select.addEventListener("change", () => {
                form.submit();
            });
        } else {
            console.warn("Carriers data is missing or select element not found.");
        }
    }

    // ===================== GLOBAL EVENTS =====================
    addCartEventListenerToLink();
    addWiwhListEventListenerToLink();
    addCompareEventListener();
};
