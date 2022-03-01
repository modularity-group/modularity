jQuery(function($){

  $('.modularity a[href="#update"][data-module]').click(function(e){
    e.preventDefault();
    const $this = $(this);

    if (!$this.hasClass('is-deleting') && confirm('Really update '+$(this).attr('data-module')+'?')) {
      $this.addClass('is-deleting');

      $.ajax({
        type: 'POST',
        url: '/wp-admin/admin-ajax.php',
        data: 'action=delete_module&name='+$(this).attr('data-module'),
        success: function(status){
          if (status == 'true') {
            window.location.reload();
          }
        }
      });
    }
  });

});
