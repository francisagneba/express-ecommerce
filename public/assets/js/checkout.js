const paymentMethods = document.querySelector(".payment-methods i");
const paypalMethodComponent = document.querySelector("#paypal-method");
const stripeMethodComponent = document.querySelector("#stripe-method");
let stripeMethod = true;
let paypalMethod = false;

const main_content = document.querySelector(".main_content");
const cart = JSON.parse(main_content?.dataset?.cart || "[]");
const stripe_public_key = main_content?.dataset?.stripe_public_key || "";
const orderId = main_content?.dataset?.orderid || ""; // Assure-toi que c'est bien "orderid" et pas "orderId" ici

document.addEventListener("DOMContentLoaded", () => {
    let shipping_address = "";
    let billing_address = "";
    let displayPayBtn = false;
    let comment = "";

    const billing_address_select = document.querySelector('select[name="billing_address"]');
    const shipping_address_select = document.querySelector('select[name="shipping_address"]');
    const comments_textarea = document.querySelector('textarea');
    const payBtn = document.querySelector('.payment_method'); // ⚠️ Vérifier si payBtn est bien trouvé

    console.log("payBtn:", payBtn); // 🔍 Debugging

    const updateButton = () => {
        displayPayBtn = !!billing_address && !!shipping_address;
        console.log("Afficher bouton:", displayPayBtn);

        if (payBtn) {
            if (displayPayBtn) {
                payBtn.classList.remove("d-none");
                console.log("✅ Bouton affiché !");
            } else {
                payBtn.classList.add("d-none");
                console.log("❌ Bouton caché !");
            }
        } else {
            console.error("⚠️ ERREUR : payBtn est introuvable !");
        }
    };

    paymentMethods.onclick = () => {
        stripeMethod = !stripeMethod
        paypalMethod = !paypalMethod
        if (stripeMethod) {
            paymentMethods.className = "fa-solid fa-toggle-off"
            stripeMethodComponent.classList.remove("d-none")
            paypalMethodComponent.classList.add("d-none")
        } else {
            paymentMethods.className = "fa-solid fa-toggle-on"
            stripeMethodComponent.classList.add("d-none")
            paypalMethodComponent.classList.remove("d-none")
        }
    }

    if (billing_address_select) {
        billing_address_select.addEventListener("change", (event) => {
            billing_address = event.target.value;
            console.log("Nouvelle adresse de facturation:", billing_address);
            updateButton();
        });
    } else {
        console.error("❌ ERREUR : Élément 'billing_address_select' introuvable !");
    }

    if (shipping_address_select) {
        shipping_address_select.addEventListener("change", (event) => {
            shipping_address = event.target.value;
            console.log("Nouvelle adresse de livraison:", shipping_address);
            updateButton();
        });
    } else {
        console.error("❌ ERREUR : Élément 'shipping_address_select' introuvable !");
    }

    if (comments_textarea) {
        comments_textarea.addEventListener("change", (event) => {
            comment = event.target.value;
            updateButton();
        });
    }

    // ✅ Déplacer ici l'événement onclick
    if (payBtn) {
        payBtn.onclick = async () => {
            try {
                const response = await fetch("/api/order", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(
                        { billing_address, shipping_address }
                    )
                });

                const text = await response.text(); // ⚠️ Lire d'abord la réponse brute
                console.log("Réponse brute:", text);

                const result = JSON.parse(text); // ⚠️ Ensuite, essayer de parser en JSON
                console.log("Résultat JSON:", result);

            } catch (error) {
                console.error("❌ Erreur lors de la requête API:", error);
            }
        };
    }


    // Stripe component


    console.log("Order ID:", orderId);
    // Vérifie qu'il est bien défini


    // This is your test publishable API key.
    const stripe = Stripe(stripe_public_key);

    // The items the customer wants to buy
    const items = cart.items;

    let elements;

    initialize();

    document.querySelector("#payment-form").addEventListener("submit", handleSubmit);

    let emailAddress = '';
    // Fetches a payment intent and captures the client secret
    async function initialize() {

        console.log("Order ID:", orderId); // <-- Debug

        const { clientSecret } = await fetch(`/api/stripe/payment-intent/${orderId}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({})
        }).then((r) => r.json());


        elements = stripe.elements({ clientSecret });

        const paymentElementOptions = {
            layout: "accordion"
        };

        const paymentElement = elements.create("payment", paymentElementOptions);
        paymentElement.mount("#payment-element");
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setLoading(true);

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: { // Make sure to change this to your payment completion page
                return_url: window.location.origin + "/stripe/payment/success",
                receipt_email: emailAddress
            }
        });

        // This point will only be reached if there is an immediate error when
        // confirming the payment. Otherwise, your customer will be redirected to
        // your `return_url`. For some payment methods like iDEAL, your customer will
        // be redirected to an intermediate site first to authorize the payment, then
        // redirected to the `return_url`.
        if (error.type === "card_error" || error.type === "validation_error") {
            showMessage(error.message);
        } else {
            showMessage("An unexpected error occurred.");
        }
        setLoading(false);
    }

    // ------- UI helpers -------

    function showMessage(messageText) {
        const messageContainer = document.querySelector("#payment-message");

        messageContainer.classList.remove("hidden");
        messageContainer.textContent = messageText;

        setTimeout(function () {
            messageContainer.classList.add("hidden");
            messageContainer.textContent = "";
        }, 4000);
    }

    // Show a spinner on payment submission
    function setLoading(isLoading) {
        if (isLoading) { // Disable the button and show a spinner
            document.querySelector("#submit").disabled = true;
            document.querySelector("#spinner").classList.remove("hidden");
            document.querySelector("#button-text").classList.add("hidden");
        } else {
            document.querySelector("#submit").disabled = false;
            document.querySelector("#spinner").classList.add("hidden");
            document.querySelector("#button-text").classList.remove("hidden");
        }
    }
})

// Implementons le Paypal

window.paypal
    .Buttons({
        style: {
            shape: "rect",
            layout: "vertical",
            color: "gold",
            label: "paypal",
        },
        message: {
            amount: 100,
        },

        async createOrder() {
            try {
                const response = await fetch("/api/paypal/orders", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    // use the "body" param to optionally pass additional order information
                    // like product ids and quantities
                    body: JSON.stringify({
                        orderId,
                    }),
                });

                const orderData = await response.json();

                if (orderData.id) {
                    return orderData.id;
                }
                const errorDetail = orderData?.details?.[0];
                const errorMessage = errorDetail
                    ? `${errorDetail.issue} ${errorDetail.description} (${orderData.debug_id})`
                    : JSON.stringify(orderData);

                throw new Error(errorMessage);
            } catch (error) {
                console.error(error);
                // resultMessage(`Could not initiate PayPal Checkout...<br><br>${error}`);
            }
        },

        async onApprove(data, actions) {
            try {
                const response = await fetch(
                    `/api/orders/${data.orderID}/capture`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                    }
                );

                const orderData = await response.json();
                // Three cases to handle:
                //   (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                //   (2) Other non-recoverable errors -> Show a failure message
                //   (3) Successful transaction -> Show confirmation or thank you message

                const errorDetail = orderData?.details?.[0];

                if (errorDetail?.issue === "INSTRUMENT_DECLINED") {
                    // (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                    // recoverable state, per
                    // https://developer.paypal.com/docs/checkout/standard/customize/handle-funding-failures/
                    return actions.restart();
                } else if (errorDetail) {
                    // (2) Other non-recoverable errors -> Show a failure message
                    throw new Error(
                        `${errorDetail.description} (${orderData.debug_id})`
                    );
                } else if (!orderData.purchase_units) {
                    throw new Error(JSON.stringify(orderData));
                } else {
                    // (3) Successful transaction -> Show confirmation or thank you message
                    // Or go to another URL:  actions.redirect('thank_you.html');
                    const transaction =
                        orderData?.purchase_units?.[0]?.payments
                            ?.captures?.[0] ||
                        orderData?.purchase_units?.[0]?.payments
                            ?.authorizations?.[0];
                    resultMessage(
                        `Transaction ${transaction.status}: ${transaction.id}<br>
          <br>See console for all available details`
                    );
                    console.log(
                        "Capture result",
                        orderData,
                        JSON.stringify(orderData, null, 2)
                    );
                }
            } catch (error) {
                console.error(error);
                resultMessage(
                    `Sorry, your transaction could not be processed...<br><br>${error}`
                );
            }
        },
    })
    .render("#paypal-button-container"); 
