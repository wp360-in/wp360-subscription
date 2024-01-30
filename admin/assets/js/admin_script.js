const adminAjax = dynamicObjects.adminAjax

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

});
