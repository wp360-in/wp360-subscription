const stripePublishKey = custom_stripe_vars.publishable_key
console.log(stripePublishKey)
console.log('stripePublishKey')
document.addEventListener('DOMContentLoaded', function () {
    if (window.Stripe === undefined) {
        // Define stripe with your public key
        var stripe = Stripe(stripePublishKey);
    }

    function initStripeElements() {
        const subscrFrm = document.querySelector('#payment-form');
        let elements = stripe.elements();
        var style = {
            base: {
                lineHeight: '30px',
            }
        };
        let cardElement = elements.create('card', { style: style });
        cardElement.mount('#payment-elements');
    }

    // Wait for Stripe.js to be fully loaded
    if (typeof stripe !== 'undefined') {
        initStripeElements();
    } else {
        document.addEventListener('stripe-js-loaded', initStripeElements);
    }
});