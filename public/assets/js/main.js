
import { displayCompare, displayCart, } from './library.js';

window.onload = () => {
    console.log("cart");
    let mainContent = document.querySelector('.main_content')
    let cart = JSON.parse(mainContent?.dataset?.cart || false)

    displayCart(cart)

    console.log("compare");
    mainContent = document.querySelector('.compare_container')
    let compare = JSON.parse(mainContent?.dataset?.compare || false)

    displayCompare(compare)

}
