const adminAjax = dynamicObjects.adminAjax
jQuery(document).ready(function($) {
  function wp360_subscription_toggleSubscriptionTab() {
      var checkbox              = $('#_wp360_subscription_product');
      var tabcontentsubsettting = $("#wp360_sub_product_target_section")
      var settingtab            = $('.wp360_sub_product_tab');
      if (checkbox.prop('checked')) {
          settingtab.show();
       //   tabcontentsubsettting.show();
      } else {
          settingtab.hide();
        //  tabcontentsubsettting.hide();
      }
  }
  wp360_subscription_toggleSubscriptionTab();
  $('#_wp360_subscription_product').on('change', function() {
      wp360_subscription_toggleSubscriptionTab();
  });
});
