
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

jQuery(document).ready(function($){
    $('.subscription_detail_header .subs_cancel, .cancel_subscription_dialog .modal-toggle').on('click', function(e) {
      e.preventDefault();
      if($(this).hasClass('modal-toggle')){
        $('.cancel_subscription_dialog').removeClass('is-visible');
      }
      else{
        $('.cancel_subscription_dialog').toggleClass('is-visible');
      }
    });

    $('#cancel_subscription_form').submit(function(e) {
      e.preventDefault();
      $('.cancel_subscription_dialog').addClass('in-progress');
      var formData = $(this).serialize();
      $.ajax({
          url: cancel_subscription_ajax.ajax_url,
          type: 'POST',
          data: {
              action: 'cancel_subscription_ajax',
              form_data: formData
          },
          success: function(response) {
              if (response) {
                  response = JSON.parse(response);
                  $('#wp360_notices_wrapper').html(response.data.message);
                  $('.cancel_subscription_dialog').removeClass('is-visible');
                  setTimeout(function(){
                    $('.cancel_subscription_dialog').removeClass('in-progress');
                  }, 200);
                  if(response.success == true){
                    $('.subscription_detail_header button.subs_cancel').remove();
                  }
              }
          },
          error: function(xhr, status, error) {
              console.error(xhr.responseText);
              $('.cancel_subscription_dialog').removeClass('is-visible');
              setTimeout(function(){
                $('.cancel_subscription_dialog').removeClass('in-progress');
              }, 300);
              alert('Error!');
          }
      });
  });
});