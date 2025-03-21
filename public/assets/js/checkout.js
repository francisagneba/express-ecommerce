document.addEventListener("DOMContentLoaded", () => {
    const paymentMethods = document.querySelector(".payment-methods i");
    const paypalMethodComponent = document.querySelector("#paypal-method");
    const stripeMethodComponent = document.querySelector("#stripe-method");
    let stripeMethod = true;
    let paypalMethod = false;

    const main_content = document.querySelector(".main_content");
    const cart = JSON.parse(main_content?.dataset?.cart || "[]");
    const stripe_public_key = main_content?.dataset?.stripe_public_key || "";
    const orderId = main_content?.dataset?.orderid || "";

    let shipping_address = null;
    let billing_address = null;
    let comment = "";
    let displayPayBtn = false;

    const billing_address_select = document.querySelector('select[name="billing_address"]');
    const shipping_address_select = document.querySelector('select[name="shipping_address"]');
    const comments_textarea = document.querySelector('textarea');
    const payBtn = document.querySelector('.payment_method');

    // Fonction pour récupérer uniquement l'ID de l'adresse
    function getAddressId(selectElement) {
        return selectElement.value || null;
    }

    // Fonction pour mettre à jour l'affichage du bouton de paiement
    const updateButton = () => {
        displayPayBtn = billing_address && shipping_address;
        if (payBtn) {
            payBtn.classList.toggle("d-none", !displayPayBtn);
        }
    };

    // Toggle Payment Methods
    if (paymentMethods) {
        paymentMethods.addEventListener("click", () => {
            stripeMethod = !stripeMethod;
            paypalMethod = !paypalMethod;

            paymentMethods.className = stripeMethod ? "fa-solid fa-toggle-off" : "fa-solid fa-toggle-on";
            stripeMethodComponent.classList.toggle("d-none", !stripeMethod);
            paypalMethodComponent.classList.toggle("d-none", stripeMethod);
        });
    }

    // Gestion des changements d'adresses
    billing_address_select?.addEventListener("change", () => {
        billing_address = getAddressId(billing_address_select);
        updateButton();
    });

    shipping_address_select?.addEventListener("change", () => {
        shipping_address = getAddressId(shipping_address_select);
        updateButton();
    });

    // Gestion des changements de commentaire
    comments_textarea?.addEventListener("change", (event) => {
        comment = event.target.value;
    });

    // Gestion du clic sur le bouton de paiement
    payBtn?.addEventListener("click", async () => {
        try {
            if (!orderId) {
                console.error("❌ Erreur : Order ID manquant !");
                return;
            }

            if (!billing_address || !shipping_address) {
                console.error("❌ Erreur : Adresses manquantes !");
                return;
            }

            const data = {
                orderid: orderId,
                billing_address: billing_address,
                shipping_address: shipping_address
            };

            console.log("Données envoyées à l'API :", data);

            const response = await fetch("/api/order", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(data),
            });

            const text = await response.text();

            try {
                const result = JSON.parse(text);
                console.log("Résultat JSON:", result);

                if (result.success) {
                    console.log("✅ Commande mise à jour !");
                } else {
                    console.error("❌ Erreur API :", result.error);
                }
            } catch (jsonError) {
                console.error("❌ Réponse inattendue de l'API (pas du JSON) :", text);
            }
        } catch (error) {
            console.error("❌ Erreur lors de la requête API:", error);
        }
    });

    // Stripe Initialization
    if (stripe_public_key) {
        const stripe = Stripe(stripe_public_key);
        let elements;

        initializeStripe();

        document.querySelector("#payment-form")?.addEventListener("submit", handleStripeSubmit);

        async function initializeStripe() {
            try {
                if (!orderId) {
                    console.error("❌ Erreur : Order ID manquant pour Stripe !");
                    return;
                }

                const response = await fetch(`/api/stripe/payment-intent/${orderId}`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({}),
                });

                const { clientSecret } = await response.json();

                elements = stripe.elements({ clientSecret });
                const paymentElement = elements.create("payment", { layout: "accordion" });
                paymentElement.mount("#payment-element");
            } catch (error) {
                console.error("❌ Erreur lors de l'initialisation de Stripe :", error);
            }
        }

        async function handleStripeSubmit(e) {
            e.preventDefault();
            setLoading(true);

            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: window.location.origin + "/stripe/payment/success",
                    receipt_email: document.querySelector("#email")?.value || "",
                },
            });

            if (error) {
                showMessage(error.message);
            }
            setLoading(false);
        }

        function showMessage(message) {
            const messageContainer = document.querySelector("#payment-message");
            messageContainer.textContent = message;
            messageContainer.classList.remove("hidden");

            setTimeout(() => {
                messageContainer.classList.add("hidden");
                messageContainer.textContent = "";
            }, 4000);
        }

        function setLoading(isLoading) {
            document.querySelector("#submit").disabled = isLoading;
            document.querySelector("#spinner").classList.toggle("hidden", !isLoading);
            document.querySelector("#button-text").classList.toggle("hidden", isLoading);
        }
    }

    // PayPal Integration
    if (window.paypal) {
        window.paypal.Buttons({
            style: { shape: "rect", layout: "vertical", color: "gold", label: "paypal" },
            async createOrder() {
                try {
                    if (!orderId) {
                        console.error("❌ Erreur : Order ID manquant pour PayPal !");
                        return;
                    }

                    const response = await fetch("/api/paypal/orders", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ orderId }),
                    });

                    const orderData = await response.json();
                    if (orderData.id) return orderData.id;

                    throw new Error(orderData?.details?.[0]?.description || "Erreur inconnue");
                } catch (error) {
                    console.error("❌ Erreur PayPal :", error);
                }
            },
            async onApprove(data, actions) {
                try {
                    const response = await fetch(`/api/orders/${data.orderID}/capture`, {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                    });

                    const orderData = await response.json();

                    if (orderData?.details?.[0]?.issue === "INSTRUMENT_DECLINED") {
                        return actions.restart();
                    } else if (orderData.purchase_units) {
                        window.location.href = window.location.origin + "/paypal/payment/success";
                    } else {
                        throw new Error("Erreur inconnue lors de la capture de paiement.");
                    }
                } catch (error) {
                    console.error("❌ Erreur PayPal :", error);
                }
            }
        }).render("#paypal-button-container");
    }
});
