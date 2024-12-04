export const formatPrice = (price) => {
    return Intl.NumberFormat('en-US', { style: 'currency', currency: 'EUR' })
        .format(price);
}

export const addFlashMessage = (message, status = "success") => {
    let text = `
    <div class="alert alert-${status}" role="alert">
    ${message}
    </div>
    `
    let audio = document.createElement("audio")
    audio.src = "/assets/audios/success.wav"

    audio.play()
    document.querySelector(".notification").innerHTML += text

    setTimeout(() => {
        document.querySelector(".notification").innerHTML = ""
    }, 2000)
}

export const fetchData = async (requestUrl) => {
    try {
        let response = await fetch(requestUrl);
        console.log("Response URL:", requestUrl);
        console.log("Response:", response);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            let data = await response.json();
            console.log("Fetched Data:", data);
            return data;
        } else {
            throw new Error("Expected JSON, but received: " + contentType);
        }
    } catch (error) {
        console.error("Error fetching data:", error);
        addFlashMessage("An error occurred. Please try again.", "danger");
        return null;
    }
};

export const manageCartLink = async (event) => {
    event.preventDefault();
    let link = event.target.href ? event.target : event.target.parentNode;
    let requestUrl = link.href;
    const cart = await fetchData(requestUrl); // Récupère les nouvelles données du panier

    // Mise à jour de l'affichage du panier avec les nouvelles données
    displayCart(cart);
    updateHeaderCart(cart); // Mise à jour du nombre d'articles dans le header

    let productId = requestUrl.split('/')[5];
    let product = await fetchData("/product/get/" + productId);

    if (requestUrl.search('/cart/add/') != -1) {
        addFlashMessage(`Product (${product ? product.name : 'Item'}) added to cart!`);
    }

    if (requestUrl.search('/cart/remove/') != -1) {
        addFlashMessage(`Product (${product ? product.name : 'Item'}) removed from cart!`, "danger");
    }
};

export const manageCompareLink = async (event) => {
    event.preventDefault();
    let link = event.target.href ? event.target : event.target.parentNode;
    let requestUrl = link.href;

    try {
        const compare = await fetchData(requestUrl);
        let productId = requestUrl.split('/')[5];
        let product = await fetchData("/product/get/" + productId);

        if (requestUrl.search('/compare/add/') !== -1) {
            if (product) {
                addFlashMessage(`Product (${product.name}) added to compare list!`);
            } else {
                addFlashMessage("Product added to compare list!");
            }
            // Redirection après le message
            setTimeout(() => {
                window.location.href = '/compare';
            }, 1000); // Délai d'une seconde pour afficher le message avant de rediriger
        }

        if (requestUrl.search('/compare/remove/') !== -1) {
            if (product) {
                addFlashMessage(`Product (${product.name}) removed from compare list!`, "danger");
            } else {
                addFlashMessage("Product removed from compare list!", "danger");
            }
            displayCompare();
        }
    } catch (error) {
        console.error("Erreur:", error);
        addFlashMessage("An error occurred. Please try again.", "danger");
    }
};

export const displayCompare = async (compare = null) => {
    let tbody = document.querySelector('table.compare_table tbody')
    if (tbody) {
        if (!compare) {
            compare = await fetchData("/compare/get")
        }

        if (compare) {
            let imageContainer = document.querySelector('table.compare_table tbody tr.pr_image')
            imageContainer.innerHTML = ""
            let nameContainer = document.querySelector('table.compare_table tbody tr.pr_title')
            nameContainer.innerHTML = ""
            let priceContainer = document.querySelector('table.compare_table tbody tr.pr_price')
            priceContainer.innerHTML = ""
            let addToCart = document.querySelector('table.compare_table tbody tr.pr_add_to_cart')
            addToCart.innerHTML = ""
            let romoveFromCart = document.querySelector('table.compare_table tbody tr.pr_remove')
            romoveFromCart.innerHTML = ""
            compare.forEach((product) => {
                imageContainer.innerHTML += `
                <td class="row_img">
                <img src="/assets/images/products/${product.imageUrls[0]}" alt="compare-img">
                </td>     
                `
                nameContainer.innerHTML += `
                <td class="product_name">
                    <a href="shop-product-detail.html">${product.name}</a>
                </td>
                `
                priceContainer.innerHTML += `
                <td class="product_price">
                <span class="price">${formatPrice(product.soldePrice / 100)}</span></td>
                `
                addToCart.innerHTML += `
                <td class="row_btn">
                <a href="/cart/add/${product.id}/1" 
                class="btn btn-fill-out add-to-cart"><i
                class="icon-basket-loaded"></i> Add To Cart</a>
                </td>
                `
                console.log("/cart/add/" + product.id + "/1");
                romoveFromCart.innerHTML += `
                <td class="row_remove">
                    <a href="/compare/remove/${product.id}" class="remove_compare_item">
                        <span>Remove</span> <i class="fa fa-times"></i>
                    </a>
                </td>
                `
            });
        }
    }
    addCompareEventListener()
}

export const addCompareEventListener = () => {
    let links = document.querySelectorAll(".add-to-compare, .compare_table .remove_compare_item")
    console.log({ links });
    links.forEach(link => {
        link.addEventListener("click", manageCompareLink)
    });
}

export const addCartEventListenerToLink = () => {
    let links = document.querySelectorAll('tbody a');
    links.forEach((link) => {
        link.addEventListener("click", manageCartLink); // Vérifier que l'événement est bien attaché
    });

    let add_to_cart_links = document.querySelectorAll('a.add-to-cart, a.item_remove,  a.btn-addtocart');
    add_to_cart_links.forEach((link) => {
        link.addEventListener("click", manageCartLink); // Vérifier ici aussi
    });
}

export const displayCart = (cart = null) => {
    // Met à jour l'affichage du panier dans le DOM
    updateHeaderCart(cart);

    if (!cart) return;

    let tbody = document.querySelector('.shop_cart_table tbody');
    let cart_total_amounts = document.querySelectorAll('.cart_total_amount');

    if (tbody) {
        tbody.innerHTML = ""; // Vide le tableau des produits

        // Ajoute les nouveaux produits dans le tableau
        cart.items.forEach((item) => {
            let { product, quantity, sub_total } = item;
            let content = `
             <tr>
                 <td class="product-thumbnail"><a><img width="50" alt="product1" src="/assets/images/products/${product.imageUrls[0]}"></a></td>
                 <td class="product-name"><a>${product.name}</a></td>
                 <td class="product-price">${formatPrice(product.soldePrice / 100)}</td>
                 <td class="product-quantity">
                     <div class="quantity">
                         <a href="/cart/delete/${product.id}/1">
                             <input type="button" value="-" class="minus">
                         </a>
                         <input type="text" name="quantity" value="${quantity}" title="Qty" size="4" class="qty">
                         <a href="/cart/add/${product.id}/1">
                             <input type="button" value="+" class="plus">
                         </a>
                     </div>
                 </td>
                 <td class="product-subtotal">${formatPrice(sub_total / 100)}</td>
                 <td class="product-remove">
                     <a href="/cart/delete-all/${product.id}/${item.quantity}">
                         <i class="ti-close"></i>
                     </a>
                 </td>
             </tr>
             `;
            tbody.innerHTML += content;
        });

        // Met à jour le total du panier
        cart_total_amounts.forEach(cart_total_amount => {
            cart_total_amount.innerHTML = formatPrice(cart.sub_total / 100);
        });
    }
};

export const updateHeaderCart = async (cart = null) => {
    let cart_count = document.querySelector(".cart_count")
    let cart_list = document.querySelector(".cart_list")
    let cart_price_value = document.querySelector(".cart_price_value")
    if (!cart) {
        // cart not found
        cart = await fetchData("/cart/get")
    }

    if (cart && cart.items && cart.items.length > 0) {
        cart_count.textContent = cart.items.length
        cart_price_value.textContent = formatPrice(cart.sub_total / 100)
        let content = "";
        cart.items.forEach((item) => {
            content += `
            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="/assets/images/products/${item.product.imageUrls[0]}" alt="product image">
                </div>
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.product.name}</div>
                    <div class="cart-item-price">${formatPrice(item.product.soldePrice / 100)}</div>
                </div>
            </div>
            `;
        });
        cart_list.innerHTML = content;
    }
};
