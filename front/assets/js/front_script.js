
// const ciGetStripePK= dynamicObjects.ciGetStripePK
// const stripe = Stripe(ciGetStripePK) //ciGetStripePK is test publish key
// const clientSecret = document.getElementById('clientSecretKey').value;
// const elements = stripe.elements({
//     clientSecret: clientSecret
// })
// const paymentElements = elements.create('payment')
// paymentElements.mount('#payment-form')

// const form = document.getElementById('payment-form')
// form.addEventListener('submit', async (e) => {
//     e.preventDefault();
//     const {error} = await stripe.confirmPaymentIntent({
//         elements,
//         confirmParams:{
//             return_url: window.location.href.split('?')[0] + 'complete.php'
//         }
//     })
//     if(error){
//         const messages = document.getElementById('error-messages')
//         messages.innerText = error.message
//     }
// })