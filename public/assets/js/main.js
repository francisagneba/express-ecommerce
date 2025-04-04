
import { displayCompare, displayCart, displayWishlist, formatPrice } from './library.js';

window.onload = () => {

    let mainContent;

    console.log("compare");
    mainContent = document.querySelector('.compare_container')
    let compare = JSON.parse(mainContent?.dataset?.compare || false)

    displayCompare(compare)

    /*************************************** */

    console.log("wishlist");
    mainContent = document.querySelector('.wishlist_content')
    let wishlist = JSON.parse(mainContent?.dataset?.wishlist || false)

    displayWishlist(wishlist)

    /*************************************** */

    console.log("cart");
    mainContent = document.querySelector('.cart_content')
    let cart = JSON.parse(mainContent?.dataset?.cart || false)

    const form = document.querySelector(".carrier_form form")
    const select = document.querySelector(".carrier_form select")

    let carriers = mainContent?.dataset?.carriers ? JSON.parse(mainContent.dataset.carriers) : [];

    console.log(carriers);

    if (carriers && carriers.length > 0) {
        select.innerHTML = ""; // Vider le select avant d'ajouter les options

        carriers.forEach(carrier => {
            let selected = cart?.carrier?.id === carrier.id ? "selected" : "";

            select.innerHTML += `
                <option value="${carrier.id}" ${selected}>
                    ${carrier.name} (${formatPrice(carrier.price / 100)})
                </option>
            `;
        });

        console.log("Options added to select:", select.innerHTML);
    } else {
        console.warn("Carriers data is missing or empty:", carriers);
    }


    const handleSubmit = (event) => {
        event.preventDefault();
    };

    const handleChange = async (event) => {
        event.preventDefault();
        const id = event.target.value
        if (id) {
            const response = await fetch('/api/cart/update/carrier/' + id)
            const result = await response.json()

            if (result.isSuccess) {
                const { data } = result
                displayCart(data)
            }

        }
        console.log({ id });
    }

    form?.addEventListener('submit', handleSubmit);
    select?.addEventListener('change', handleChange);

    displayCart(cart)

}
