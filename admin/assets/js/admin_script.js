const sub_adminAjax = dynamicObjects.adminAjax

jQuery(document).ready(function($) {
  function toggleSubscriptionTab() {
      var checkbox = $('#_wp360_subscription_product');
      var tabcontentsubsettting = $("#wp360_sub_product_target_section")
      var settingtab = $('.wp360_sub_product_tab');
      if (checkbox.prop('checked')) {
          settingtab.show();
       //   tabcontentsubsettting.show();
      } else {
          settingtab.hide();
        //  tabcontentsubsettting.hide();
      }
  }
  toggleSubscriptionTab();
  $('#_wp360_subscription_product').on('change', function() {
      toggleSubscriptionTab();
  });

  function updateSubscriptionInvoiceMeta(paged) {
    $.ajax({
        url: sub_adminAjax,
        type: 'post',
        data: {
          action: 'update_subscription_invoices_meta',
          paged: paged
      },
      beforeSend: function() {
        $('.wp360_update_invoice_db_notice button').prop('disabled', true);
        $('.wp360_update_invoice_db_notice p').text('Updating');
      },
      success: function(response) {
        console.log(response);

          if (response.success) {
              if (response.data.repeat) {
                  updateSubscriptionInvoiceMeta(response.data.paged);
              } else {
                $('.wp360_update_invoice_db_notice').removeClass('notice-info');
                $('.wp360_update_invoice_db_notice').addClass('notice-success');
                $('.wp360_update_invoice_db_notice p').text(response.data.messages);
                $('.wp360_update_invoice_db_notice button#wp360_invoice_update').remove();
              }
          } else {
            $('.wp360_update_invoice_db_notice').removeClass('notice-info');
            $('.wp360_update_invoice_db_notice').addClass('notice-error');
            $('.wp360_update_invoice_db_notice p').text(response.data.messages);
            $('.wp360_update_invoice_db_notice button#wp360_invoice_update').remove();
          }
      },
      error: function(xhr, status, error) {
          // $('._ajaxMsz').html('AJAX Error: ' + status + ' ' + error).show();
          // $('._ajaxLoading').hide();
          $('.wp360_update_invoice_db_notice').removeClass('notice-info');
          $('.wp360_update_invoice_db_notice').addClass('notice-error');
          $('.wp360_update_invoice_db_notice p').text('Critical Error please try again!: ' + status + ' ' + error);
          console.table(xhr);
      },
    });
}

  $('#wp360_invoice_update').on('click', function() {
      paged = 1;
      updateSubscriptionInvoiceMeta(paged);
  });
  $(window).on('beforeunload', function(){
    if ($('#wp360_invoice_update').attr('disabled')) {
        return 'WP360 Subscriptions: Database update in progress. Are you sure you want to leave?';
    }
  });
  $('.wp360_license_update').on('click', function(e){
      let licenseKey = $('#_wp360_subscription_license_key').val();
      if(licenseKey.length !== 0){
        e.preventDefault();
        let inputStatus = $('input[name="_wp360_subscription_license_key_status"]');      
        let licenseURL = 'https://plugins.wp360.in/wp-json/wp360_license/v1/verify?license_key=' + licenseKey;              
        $.ajax({
          url: licenseURL,
          type: 'get',
          success: function(response) {
            if (response.status.length !== 0) {
                console.log(response.status, response.status.length);
                inputStatus.val(response.status);
                $('.wp360_license_update').unbind('click').trigger('click');
            }
          },
          error: function() {
              alert('Error updating license key! Please try again.')
          }
        });
      }
  });
});

jQuery(document).ready(function($) {
    $('.cancel-subscription').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var subscriptionId = $this.data('subscription-id');
        var postId = $this.data('post-id');
        
        if (!confirm('Are you sure you want to cancel this subscription?')) {
            return;
        }
        
        $this.text('Processing...');
        
        $.ajax({
            url: sub_adminAjax,
            type: 'POST',
            data: {
                action: 'wp360_cancel_subscription',
                subscriptionID: subscriptionId,
                post_id: postId,
                nonce: dynamicObjects.cancelSubscriptionNonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Subscription canceled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                    $this.text('Cancel Subscription');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $this.text('Cancel Subscription');
            }
        });
    });
});


