window.onload = () => {
    const mainContent = document.querySelector('.main_content');
    const tbody = document.querySelector('tbody');
    const cart_total_amounts = document.querySelectorAll('.cart_total_amount');

    let cart = JSON.parse(mainContent?.dataset?.cart || false);

    const formatPrice = (price) => {
        price = Number(price); // Convertir en nombre
        if (isNaN(price) || price < 0) {
            return 'Invalid Price';
        }

        // S'assurer que le prix est en centimes
        return Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(price / 100); // Divise par 100 si les prix sont en centimes
    };

    const updateCartTotals = () => {
        // Vérifiez que `cart` et `cart.data` existent avant d'accéder à `subTotalTTC`
        const subTotalValue = cart && cart.data ? cart.data.subTotalTTC : undefined;
        console.log("SubTotalTTC:", subTotalValue);

        if (typeof subTotalValue === 'number' && !isNaN(subTotalValue)) {
            cart_total_amounts.forEach(cart_total_amount => {
                cart_total_amount.innerHTML = formatPrice(subTotalValue);
            });
        } else {
            cart_total_amounts.forEach(cart_total_amount => {
                cart_total_amount.innerHTML = 'Invalid Price';
                console.warn("Invalid sub total value:", subTotalValue);
            });
        }
    };

    const addFlashMessage = (message, status = "success") => {
        const text = `
        <div class="alert alert-${status}" role="alert">
        ${message}
        </div>
        `;
        const audio = document.createElement("audio");
        audio.src = "/assets/audios/success.wav";

        audio.play();
        console.log(text);
        document.querySelector(".notification").innerHTML += text;

        setTimeout(() => {
            document.querySelector(".notification").innerHTML = "";
        }, 3000);  // Augmenté à 3000ms pour laisser plus de temps à la notification d'être visible
    };

    const fetchData = async (requestUrl) => {
        try {
            const response = await fetch(requestUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'  // Indique qu'il s'agit d'une requête Ajax
                }
            });
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.statusText}`);
            }

            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                const jsonResponse = await response.json();
                console.log("Fetched cart data:", jsonResponse); // Ajoutez ce log
                return jsonResponse;
            } else {
                const responseText = await response.text();
                console.error("Response is not JSON:", responseText);
                throw new Error("Expected JSON response but received HTML or another format");
            }
        } catch (error) {
            console.error("Fetch operation error:", error);
            return null;
        }
    };


    const manageLink = async (event) => {
        event.preventDefault();
        const link = event.target.href ? event.target : event.target.parentNode;
        const requestUrl = link.href;

        const parts = requestUrl.split('/');
        const productId = parts[parts.length - 1]; // Dernier segment pour ID du produit

        console.log("Request URL:", requestUrl);
        console.log("Product ID:", productId);

        if (!productId) {
            addFlashMessage("Invalid link or quantity!", "danger");
            return;
        }

        // Appelez fetchData pour mettre à jour le panier
        const response = await fetchData(requestUrl);
        console.log("Response from delete from cart:", response);

        // Vérifiez si la réponse est valide
        if (!response || response.status !== 'success' || !response.cart) {
            addFlashMessage("Failed to update cart.", "danger");
            return;
        }

        // Mettez à jour le panier
        cart = response.cart;
        addFlashMessage("Product removed from cart!");
        initCart();
        updateHeaderCart();
    };

    const addEventListenerToLink = () => {
        const links = document.querySelectorAll('tbody a');
        links.forEach((link) => {
            link.addEventListener("click", manageLink);
        });
        const add_to_cart_links = document.querySelectorAll('li.add-to-cart a, a.item_remove');
        add_to_cart_links.forEach((link) => {
            link.addEventListener("click", manageLink);
        });
    };

    const initCart = () => {
        if (!cart) {
            addEventListenerToLink();
            return;
        }

        if (tbody) {
            tbody.innerHTML = "";

            if (Array.isArray(cart.items)) {
                cart.items.forEach((item) => {
                    const { product, quantity, sub_total } = item;
                    const content = `
                        <tr>
                            <td class="product-thumbnail"><a><img width="50" alt="product1" src="/assets/images/products/${product.imageUrls[0]}"></a></td>
                            <td data-title="Product" class="product-name"><a>${product.name}</a></td>
                            <td data-title="Price" class="product-price">${formatPrice(product.regularPrice)}</td>
                            <td data-title="Quantity" class="product-quantity">
                                <div class="quantity">
                                    <a href="/cart/delete/${product.id}/1"><input type="button" value="-" class="minus"></a>
                                    <input type="text" name="quantity" value="${quantity}" title="Qty" size="4" class="qty">
                                    <a href="/cart/add/${product.id}/1"><input type="button" value="+" class="plus"></a>
                                </div>
                            </td>
                            <td data-title="Total" class="product-subtotal">${formatPrice(sub_total)}</td>
                            <td data-title="Remove" class="product-remove">
                                <a href="/cart/delete-all/${product.id}/${item.quantity}"><i class="ti-close"></i></a>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += content;
                });
            } else {
                console.warn("Cart items is not an array:", cart.items);
            }

            console.log("Cart data:", cart);
            console.log("Sub total value:", cart.data.subTotalTTC);
            console.log("Updated cart after adding product:", cart);

            updateCartTotals(); // Mettre à jour les totaux après l'initialisation du panier
        }
        addEventListenerToLink();
    };

    const updateHeaderCart = async () => {
        const cart_count = document.querySelector(".cart_count");
        const cart_list = document.querySelector(".cart_list");
        const cart_price_value = document.querySelector(".cart_price_value");

        if (!cart) {
            cart = await fetchData("/cart/get");
            if (!cart) {
                console.log("Cart data could not be fetched.");
                return;
            }
        }

        if (!cart || !Array.isArray(cart.items) || !cart.data) {
            console.log("Cart is undefined or does not have items or data.");
            return;
        }
        cart_count.innerHTML = cart.cart_count;
        console.log("Cart data:", cart);

        // Vérifiez si `subTotalTTC` existe dans `cart.data`
        if (cart.data.subTotalTTC !== undefined) {
            console.log("SubTotalTTC:", cart.data.subTotalTTC);
            console.log("Formatted Price:", formatPrice(cart.data.subTotalTTC));
            cart_price_value.innerHTML = formatPrice(cart.data.subTotalTTC);
        } else {
            console.warn("subTotalTTC is not defined.");
            cart_price_value.innerHTML = 'Invalid Price';
        }

        cart_list.innerHTML = "";
        cart.items.forEach(item => {
            const { product, quantity } = item;
            cart_list.innerHTML += `
                <li>
                    <a href="/cart/delete-all/${product.id}/${quantity}" class="item_remove">
                        <i class="ion-close"></i>
                    </a>
                    <a href="/product/${product.slug}">
                        <img width="50" height="50" alt="cart_thumb1" src="/assets/images/products/${product.imageUrls[0]}">
                        ${product.name}
                    </a>
                    <span class="cart_quantity"> ${quantity} x
                        <span class="cart_amount">
                            <span class="price_symbole">${formatPrice(product.regularPrice)}</span>
                        </span>
                    </span>
                </li>
            `;
        });

        addEventListenerToLink();
    };

    initCart();
    updateHeaderCart();
};
