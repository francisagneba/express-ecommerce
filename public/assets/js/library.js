export const formatPrice = (price) => {
    return Intl.NumberFormat('en-US', { style: 'currency', currency: 'EUR' })
        .format(price);
}

export const addFlashMessage = (message, status = "success") => {
    // Empêche l'ajout de multiples messages flash simultanément
    const notificationContainer = document.querySelector(".notification");
    if (!notificationContainer) return;

    // Vérifie si un message est déjà présent et empêche d'ajouter un autre
    if (notificationContainer.querySelector(".alert")) {
        return;  // Ne pas ajouter un autre message si un message est déjà visible
    }

    let text = `
    <div class="alert alert-${status}" role="alert">
    ${message}
    </div>
    `;
    let audio = document.createElement("audio");
    audio.src = "/assets/audios/success.wav";
    audio.play();

    notificationContainer.innerHTML += text;

    setTimeout(() => {
        notificationContainer.innerHTML = "";
    }, 2000);
};

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
        } else if (contentType && contentType.includes("text/html")) {
            let errorText = await response.text();
            console.error("HTML response received instead of JSON:", errorText);
            throw new Error("Expected JSON, but received HTML");
        } else {
            let errorText = await response.text();
            console.error("Unexpected response type:", contentType, errorText);
            throw new Error("Unexpected response type: " + contentType);
        }
    } catch (error) {
        console.error("Error fetching data:", error);
        addFlashMessage("An error occurred. Please try again.", "danger");
        return null;
    }
};





export const manageCartLink = async (event) => {
    event.preventDefault();
    let link = event.target.href ? event.target : event.target.parentNode
    let requestUrl = link.href
    const cart = await fetchData(requestUrl)

    let productId = requestUrl.split('/')[5];
    let product = await fetchData("/product/get/" + productId)



    if (requestUrl.search('/cart/add/') != -1) {
        // add to cart
        if (product) {
            addFlashMessage(`Product (${product.name}) added to cart !`)
        } else {
            addFlashMessage("Product added to cart !")
        }
    }
    if (requestUrl.search('/cart/remove/') != -1) {
        // remove from cart
        if (product) {
            addFlashMessage(`Product (${product.name}) removed to cart !`, "danger")
        } else {
            addFlashMessage("Product removed to cart !", "danger")
        }
    }
    displayCart(cart)
    updateHeaderCart()
}


export const manageCompareLink = async (event) => {
    event.preventDefault();
    let link = event.target.href ? event.target : event.target.parentNode;
    let requestUrl = link.href;

    const compare = await fetchData(requestUrl);
    let productId = requestUrl.split('/')[5];
    let product = await fetchData("/product/get/" + productId);

    if (!product) {
        addFlashMessage("Product not found for comparison.", "danger");
        return;  // Si le produit n'est pas trouvé, arrêter l'exécution
    }

    if (requestUrl.search('/compare/add/') != -1) {
        // add to compare
        addFlashMessage(`Product (${product.name}) added to compare list!`);
    }

    if (requestUrl.search('/compare/remove/') != -1) {
        // remove from compare
        addFlashMessage(`Product (${product.name}) removed from compare list!`, "danger");
    }

    displayCompare();
};


export const manageWishListLink = async (event) => {
    event.preventDefault();
    console.log("manageWishListLink");
    let link = event.target.href ? event.target : event.target.parentNode;
    let requestUrl = link.href;

    console.log({ requestUrl });


    const wishlist = await fetchData(requestUrl);
    console.log(wishlist);

    let productId = requestUrl.split('/')[5];
    let product = await fetchData("/product/get/" + productId);

    if (requestUrl.search('/wishlist/add/') != -1) {
        if (product) {
            addFlashMessage(`Product (${product.name}) added to wish list!`);
        } else {
            addFlashMessage("Product added to wish list!");
        }

    }

    if (requestUrl.search('/wishlist/remove/') != -1) {
        if (product) {
            addFlashMessage(`Product (${product.name}) removed from wish list!`, "danger");
        } else {
            addFlashMessage("Product removed from wish list!", "danger");
        }

    }

    displayWishlist(wishlist);
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

export const addWiwhListEventListenerToLink = () => {
    let links = document.querySelectorAll(".add-to-wishlist, .wishlist_table .remove-to-wishlist")

    links.forEach(link => {
        link.addEventListener("click", manageWishListLink)
    });
}

export const addCartEventListenerToLink = () => {

    let links = document.querySelectorAll('.shop_cart_table tbody a, a.add-to-cart, a.item_remove,  a.btn-addtocart')
    links.forEach((link) => {
        link.addEventListener("click", manageCartLink)
    })

    // let add_to_cart_links = document.querySelectorAll('a.add-to-cart, a.item_remove,  a.btn-addtocart')
    // add_to_cart_links.forEach((link) => {
    //     link.addEventListener("click", manageCartLink)
    // })
}

export const displayCart = (cart = null) => {
    // Met à jour l'affichage du panier dans le DOM
    updateHeaderCart(cart);

    if (!cart) return;

    let tbody = document.querySelector('.shop_cart_table tbody');
    let cart_total_amounts = document.querySelectorAll('.cart_total_amount1');
    let cart_total_amountss = document.querySelectorAll('.cart_total_amount2');
    let cart_total_amountsss = document.querySelectorAll('.cart_total_amount3');

    if (tbody) {
        tbody.innerHTML = ""; // Vide le tableau des produits

        let totalHT = 0; // Total Hors Taxes
        let totalTTC = 0; // Total Toutes Taxes Comprises

        // Ajoute les nouveaux produits dans le tableau
        cart.items.forEach((item) => {
            let product = item.product || {};
            let quantity = item.quantity || 0;
            let sub_total = item.sub_total || 0;
            let productPrice = product.soldePrice || 0;
            let productImage = product.imageUrls ? product.imageUrls[0] : 'placeholder.jpg';
            let productName = product.name || 'Unknown Product';

            totalHT += sub_total; // Ajout du sous-total HT
            totalTTC += sub_total * 1.2; // Ajout du sous-total TTC avec TVA à 20%

            let content = `
             <tr>
                 <td class="product-thumbnail">
                     <a><img width="50" alt="${productName}" src="/assets/images/products/${product.imageUrls[0]}"></a>
                 </td>
                 <td class="product-name"><a>${productName}</a></td>
                 <td class="product-price">${formatPrice(productPrice / 100)}</td>
                 <td class="product-quantity">
                     <div class="quantity">
                         <a href="/cart/delete/${product.id || 0}/1" class="item_remove">
                             <input type="button" value="-" class="minus">
                         </a>
                         <input type="text" name="quantity" value="${quantity}" title="Qty" size="4" class="qty">
                         <a href="/cart/add/${product.id || 0}/1">
                             <input type="button" value="+" class="plus">
                         </a>
                     </div>
                 </td>
                 <td class="product-subtotal">${formatPrice(sub_total / 100)}</td>
                 <td class="product-remove">
                     <a href="/cart/delete-all/${product.id || 0}/${quantity}" class="item_remove">
                         <i class="ti-close"></i>
                     </a>
                 </td>
             </tr>
             `;
            tbody.innerHTML += content;
        });

        // Mise à jour du total HT et TTC dans l'affichage
        cart_total_amounts.forEach(cart_total_amount => {
            cart_total_amount.innerHTML = `
                <span> ${formatPrice(totalHT / 100)}</span>            
            `;
        });
        cart_total_amountss.forEach(cart_total_amount => {
            cart_total_amount.innerHTML = `
                <span></span>
            `;
        });
        cart_total_amountsss.forEach(cart_total_amount => {
            cart_total_amount.innerHTML = `
                <span> ${formatPrice(totalTTC / 100)}</span>
            `;
        });

        // Réattacher les événements pour incrémentation/décrémentation
        //addCartEventListenerToLink();

    }
};

export const displayWishlist = (wishlist = null) => {

    addWiwhListEventListenerToLink()
    if (!wishlist) return;

    let tbody = document.querySelector('.wishlist_table tbody');


    if (tbody) {
        tbody.innerHTML = ""; // Vide le tableau des produits

        // Ajoute les nouveaux produits dans le tableau
        wishlist.forEach((product) => {

            let content = `
            <tr>
                <td class="product-thumbnail">
                    <a href="#"><img width="50" height="50" alt="product1" src="/assets/images/products/${product.imageUrls[0]}"></a>
                </td>
                <td data-title="Product" class="product-name">
                    <a href="#">${product.name}</a>
                </td>
                <td data-title="Price" class="product-price">
                    ${formatPrice(product.soldePrice / 100)}</td>
                <td data-title="Stock Status" class="product-stock-status">
                    ${product.stock}
                    <span class="badge badge-pill badge-success">In Stock</span>
                </td>
                <td class="product-add-to-cart">
                    <a href="/cart/add/${product.id}/1" class="btn btn-fill-out btn-addtocart">
                        <i class="icon-basket-loaded"></i>
                        Add to Cart
                    </a>
                </td>
                <td data-title="Remove" class="product-remove">
                    <a href="/wishlist/remove/${product.id}" class ="remove-to-wishlist">
                        <i class="ti-close"></i>
                    </a>
                </td>
            </tr>
             `;
            tbody.innerHTML += content;
        });
    }
    addWiwhListEventListenerToLink()
};


export const updateHeaderCart = async (cart = null) => {
    let cart_count = document.querySelector(".cart_count");
    let cart_list = document.querySelector(".cart_list");
    let cart_price_value = document.querySelector(".cart_price_value");

    if (!cart) {
        // Récupération des données du panier depuis l'API
        cart = await fetchData("/cart/get");
    }

    console.log('Cart:', cart); // Debug : afficher le panier complet

    if (cart && cart.items && cart.items.length > 0) {
        // Mise à jour du nombre d'articles dans le header
        cart_count.textContent = cart.items.reduce((total, item) => total + item.quantity, 0);

        // Calcul du sous-total
        let subTotal = cart.items.reduce((total, item) => {
            let productPrice = item.product.soldePrice || 0;
            return total + productPrice * item.quantity;
        }, 0);

        // Mise à jour du sous-total
        cart_price_value.textContent = formatPrice(subTotal / 100);

        let content = "";
        cart.items.forEach((item) => {
            let product = item.product || {};
            let productImage = product.imageUrls ? product.imageUrls[0] : 'placeholder.jpg';
            let productName = product.name || 'Unknown Product';
            let productPrice = product.soldePrice || 0;
            let quantity = item.quantity || 0;

            content += `
            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="/assets/images/products/${productImage}" alt="${productName}">
                </div>
                <div class="cart-item-info">
                    <div class="cart-item-name">${productName}</div>
                    <div>${quantity}</div>
                    <div class="cart-item-price">${formatPrice(productPrice / 100)}</div>
                    <div class="cart-item-remove">
                        <a href="/cart/delete-all/${product.id}/${item.quantity}">
                            <i class="ti-close"></i>
                        </a>
                    </div>
                </div>
            </div>
            `;
        });

        cart_list.innerHTML = content;
    } else {
        // Si le panier est vide
        cart_count.textContent = "0";
        cart_price_value.textContent = formatPrice(0);
        cart_list.innerHTML = "<div class='empty-cart'>Votre panier est vide !</div>";
    }
    //addCartEventListenerToLink()
};
