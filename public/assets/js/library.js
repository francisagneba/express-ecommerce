export const formatPrice = (price) => {
    return Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price);
};

export const addFlashMessage = (message, status = "success") => {
    const notificationContainer = document.querySelector(".notification");
    if (!notificationContainer) return;

    if (notificationContainer.querySelector(".alert")) {
        return;
    }

    let alert = `
        <div class="alert alert-${status}" role="alert">
            ${message}
        </div>
    `;

    const audio = new Audio("/assets/audios/success.wav");
    audio.play();

    notificationContainer.innerHTML += alert;

    setTimeout(() => {
        notificationContainer.innerHTML = "";
    }, 2000);
};

const fetchData = async (url, method = 'POST', body = null) => {
    const forcePostRoutes = ['/cart/add/', '/wishlist/add/', '/compare/add/'];

    // Assure-toi d'utiliser POST pour ces routes
    if (forcePostRoutes.some(route => url.includes(route))) {
        method = 'POST';
    }

    try {
        const options = {
            method,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (['POST', 'PUT', 'PATCH'].includes(method) && body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        const response = await fetch(url, options);
        const text = await response.text();
        const contentType = response.headers.get('content-type');

        if (response.ok && contentType?.includes('application/json')) {
            return JSON.parse(text);
        } else {
            throw new Error(`Réponse inattendue : ${text}`);
        }
    } catch (error) {
        console.error("Erreur lors de la récupération des données:", error);
        throw error;
    }
};



export const manageCartLink = async (event) => {
    event.preventDefault();

    let link = event.currentTarget.closest("a");
    let requestUrl = link ? link.href : null;

    // Vérifiez que l'URL est définie avant de procéder
    if (!requestUrl) {
        console.error("L'URL de la requête est indéfinie !");
        addFlashMessage("Une erreur est survenue. Veuillez réessayer.", "danger");
        return;
    }

    try {
        const cart = await fetchData(requestUrl);

        // Vérifiez que l'URL contient un identifiant de produit valide
        const urlParts = requestUrl.split('/');
        const productId = urlParts.length > 5 ? urlParts[5] : null;

        if (!productId) {
            console.error("L'ID du produit est introuvable dans l'URL !");
            addFlashMessage("Une erreur est survenue. Veuillez réessayer.", "danger");
            return;
        }

        // Récupérer les informations du produit
        let product = await fetchData("/product/get/" + productId);

        // Gérer les messages selon le type d'action (ajouter ou supprimer)
        if (requestUrl.includes('/cart/add/')) {
            if (product && product.name) {
                addFlashMessage(`Produit (${product.name}) ajouté au panier !`);
            } else {
                addFlashMessage("Produit ajouté au panier !");
            }
        }

        if (requestUrl.includes('/cart/delete/') || requestUrl.includes('/cart/delete-all/')) {
            if (product && product.name) {
                addFlashMessage(`Produit (${product.name}) retiré du panier !`, "danger");
            } else {
                addFlashMessage("Produit retiré du panier !", "danger");
            }
        }

        // Mettre à jour l'affichage du panier
        displayCart(cart);
        updateHeaderCart(cart);
    } catch (error) {
        console.error("Erreur lors de la récupération des données : ", error);
        addFlashMessage("Une erreur est survenue lors de la mise à jour du panier. Veuillez réessayer.", "danger");
    }
};





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
                    <a href="/product/${product.slug}">${product.name}</a>
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
    const links = document.querySelectorAll('.shop_cart_table tbody a, a.add-to-cart, a.item_remove, a.btn-addtocart');
    links.forEach((link) => {
        link.addEventListener("click", manageCartLink);
    });
};

export const displayCart = (cart = null) => {
    // Vérifiez si cart existe et si cart.items est un tableau
    if (!cart || !Array.isArray(cart.items)) {
        console.warn("Cart ou cart.items est manquant ou invalide");
        return; // Ne continuez pas si les données sont incorrectes
    }

    // Met à jour l'affichage du panier dans le DOM
    if (cart) {
        updateHeaderCart(cart);
    }

    let tbody = document.querySelector('.shop_cart_table tbody');
    let cart_total_amounts = document.querySelectorAll('.cart_total_amount1');
    let cart_total_amountssss = document.querySelectorAll('.cart_total_amount4');
    let cart_total_amountss = document.querySelectorAll('.cart_total_amount2');
    let cart_total_amountsss = document.querySelectorAll('.cart_total_amount3');

    if (tbody) {
        tbody.innerHTML = ""; // Vide le tableau des produits

        let totalTTC = 0; // Total TTC

        // Ajoute les nouveaux produits dans le tableau
        cart.items.forEach((item) => {
            let product = item.product || {};
            let quantity = item.quantity || 0;
            let sub_total = item.sub_total || 0;
            let taxe = item.taxe || 0;
            let sub_total_ht = item.order_cost_ht || 0;
            let productPrice = product.soldePrice || 0;
            let productImage = product.imageUrls ? product.imageUrls[0] : 'placeholder.jpg';
            let productName = product.name || 'Unknown Product';

            totalTTC += sub_total;

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
                 <td class="product-subtotal">${formatPrice(taxe / 100)}</td>
                 <td class="product-subtotal">${formatPrice(sub_total_ht / 100)}</td>
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

        // Mise à jour des totaux
        cart_total_amounts.forEach(cart_total_amount => {
            cart_total_amount.innerHTML = `<span>${formatPrice(cart.data.subTotalHT / 100)}</span>`;
        });
        cart_total_amountssss.forEach(cart_total_amount => {
            cart_total_amount.innerHTML = `<span>${formatPrice(cart.data.taxe / 100)}</span>`;
        });
        cart_total_amountss.forEach(cart_total_amount => {
            cart_total_amount.innerHTML = `<span>${formatPrice(cart.data.carrier_price / 100)}</span>`;
        });
        cart_total_amountsss.forEach(cart_total_amount => {
            cart_total_amount.innerHTML = `<span>${formatPrice((totalTTC + cart.data.carrier_price) / 100)}</span>`;
        });
    }

    // Réattacher les événements sur les nouveaux éléments
    addCartEventListenerToLink();

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
                    <a href="/product/${product.slug}">${product.name}</a>
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

// Met à jour l'affichage du panier dans l'en-tête
export const updateHeaderCart = async (cart = null) => {
    const cart_count = document.querySelector(".cart_count");
    const cart_list = document.querySelector(".cart_list");
    const cart_price_value_ht = document.querySelector(".cart_price_value_ht");
    const cart_taxe_value = document.querySelector(".cart_taxe_value");
    const cart_price_value_ttc = document.querySelector(".cart_price_value_ttc");

    // Vérifie si les éléments du DOM sont bien présents
    if (!cart_count || !cart_list || !cart_price_value_ht) {
        console.warn("Un ou plusieurs éléments du header cart sont introuvables.");
        return;
    }

    // Si aucun panier passé en paramètre, on récupère les données via l'API
    if (!cart) {
        cart = await fetchData("/cart/get");
    }

    // Si panier valide avec des articles
    if (cart?.items?.length > 0) {
        const totalItems = cart.items.reduce((total, item) => total + item.quantity, 0);
        cart_count.textContent = totalItems;

        // Données de prix depuis l'objet "cart.data"
        const subTotalHT = (cart.data?.subTotalHT || 0) / 100;
        const taxe = (cart.data?.taxe || 0) / 100;
        const subTotalTTC = (cart.data?.subTotalTTC || 0) / 100;

        cart_price_value_ht.textContent = formatPrice(subTotalHT);
        cart_taxe_value.textContent = formatPrice(taxe);
        cart_price_value_ttc.textContent = formatPrice(subTotalTTC);

        // Génération dynamique de la liste des articles
        cart_list.innerHTML = cart.items.map(item => {
            const product = item.product || {};
            const image = product.imageUrls?.[0] || 'placeholder.jpg';
            const name = product.name || 'Produit inconnu';
            const price = product.soldePrice || 0;
            const quantity = item.quantity;

            return `
                <div class="cart-item">
                    <div class="cart-item-image">
                        <img src="/assets/images/products/${image}" alt="${name}">
                    </div>
                    <div class="cart-item-info">
                        <div class="cart-item-name">${name}</div>
                        <div>${quantity} x ${formatPrice(price / 100)} = ${formatPrice(price * quantity / 100)}</div>
                        <div class="cart-item-remove">
                            <a href="/cart/delete-all/${product.id}/${quantity}"
                               class="remove-from-header-cart"
                               data-product-id="${product.id}"
                               data-quantity="${quantity}">
                                <i class="ti-close"></i>
                            </a>
                        </div>
                    </div>
                </div>`;
        }).join("");
    } else {
        // Panier vide
        cart_count.textContent = "0";
        cart_price_value_ht.textContent = formatPrice(0);
        cart_taxe_value.textContent = formatPrice(0);
        cart_price_value_ttc.textContent = formatPrice(0);
        cart_list.innerHTML = "<div class='empty-cart'>Votre panier est vide !</div>";
    }

    // Réattache les événements après mise à jour du DOM
    bindAddToCartButtons();
    addRemoveItemFromHeaderCart();
};

export const bindAddToCartButtons = () => {
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        const clone = button.cloneNode(true);
        button.replaceWith(clone);

        clone.addEventListener('click', async (e) => {
            e.preventDefault();

            const productId = clone.dataset.productId;
            const quantity = parseInt(clone.dataset.quantity || "1");

            if (!productId || isNaN(quantity)) {
                console.warn("ID produit ou quantité invalide :", clone);
                return;
            }

            // Désactive le bouton
            clone.disabled = true;

            try {
                const res = await fetchData(`/cart/add/${productId}/${quantity}`, 'POST');
                if (res?.items) {
                    await updateHeaderCart(res);
                }
            } catch (error) {
                console.error("Erreur lors de l'ajout au panier :", error);
            } finally {
                // Réactive le bouton une fois l'opération terminée (ou en cas d'erreur)
                clone.disabled = false;
            }
        });
    });
};


export const addRemoveItemFromHeaderCart = () => {
    document.querySelectorAll('.remove-from-header-cart').forEach(link => {
        const clone = link.cloneNode(true);
        link.replaceWith(clone); // supprime les anciens listeners

        clone.addEventListener('click', async (e) => {
            e.preventDefault();

            const productId = clone.dataset.productId;
            const quantity = clone.dataset.quantity;

            if (!productId || !quantity) {
                console.warn("Produit ou quantité manquante :", clone);
                return;
            }

            try {
                const res = await fetchData(`/cart/delete-all/${productId}/${quantity}`);
                if (res?.items) {
                    await updateHeaderCart(res);
                }
            } catch (error) {
                console.error("Erreur lors de la suppression :", error);
            }
        });
    });
};