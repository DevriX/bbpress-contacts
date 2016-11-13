jQuery(document).ready(function($){

    $(document).on("click", "a.bbpc-btn", function(evt){
      evt.preventDefault();
      var e = $(this)
        , n = e.data('nonce')
        , u = e.data('contact-id')
        , d = {action: 'bbp_contacts',bbpc_nonce: n,contact_ID: u,task: e.hasClass('bbpc-remove')?'remove':'add'}
      if ( e.attr( "disabled" ) )
        return;
      var others = $( '.bbpc-btn[data-contact-id="' + u + '"]' );
      e.attr("disabled", "disabled").addClass('loading');
      others.attr("disabled", "disabled").addClass('loading');
      $.ajax({
          type: 'POST',
          data: d,
          url: BBPC.AJAX,
          success: function(data){
            if ( data.success && data.button ) {
              others.replaceWith(data.button);
              e.replaceWith(data.button);
            } else {
              alert(data.message || "ERROR: Something went wrong.");
              e.removeAttr("disabled").removeClass("loading");
              others.removeAttr("disabled").removeClass("loading");
            }
          },
          error: function() {
            alert("ERROR: Something went wrong.");
            others.removeAttr("disabled").removeClass("loading");
          }
      });
    });

    var postAjaxLoad = function() {
      $(".bbp-contacts .rem").removeAttr('onclick');
    }
    postAjaxLoad();

    $(document).on("click", ".bbp-contacts .rem", function(evt){
      evt.preventDefault();
      var e = $(this)
        , l = e.closest('li')
        , n = e.data('nonce')
        , u = e.data('contact-id')
        , d = {action: 'bbp_contacts',bbpc_nonce: n,contact_ID: u,task: 'remove'}
      if ( !confirm(e.data('conf')) ) return;
      $.ajax({
          type: 'POST',
          data: d,
          url: BBPC.AJAX,
          success: function(data){
            if ( data.success ) {
              l.fadeOut('fast', function(){
                $(this).remove();
              });
            } else {
              alert(data.message || "ERROR: Something went wrong.");
            }
          },
          error: function() {
            alert("ERROR: Something went wrong.");
          }
      });


    });

    $(document).on("submit", ".bbp-contacts form", function(evt){
      evt.preventDefault();
      var f = $(this)
        , i = $('input',f)
        , p = f.closest('.bbp-contacts')
        , u = p.data('user-id')
        , d = {action: 'bbp_contacts_list',user_id: u,csearch: i.val()}
      $.ajax({
          type: 'POST',
          data: d,
          url: BBPC.AJAX,
          success: function(data){
            p.replaceWith(data);
            postAjaxLoad();
          },
          error: function() {
            alert("ERROR: Something went wrong.");
          }
      });
    });

    $(document).on("click", ".bbp-contacts .pagination a", function(evt){
      evt.preventDefault();
      var e = $(this)
        , p = e.closest('.bbp-contacts')
        , i = $('form input',p)
        , u = p.data('user-id')
        , d = {action: 'bbp_contacts_list',user_id: u,cpage: e.data('page')}
      if ( $.trim(i.val()) ) {
        d.csearch = $.trim( i.val() );
      }
      $.ajax({
          type: 'POST',
          data: d,
          url: BBPC.AJAX,
          success: function(data){
            p.replaceWith(data);
            postAjaxLoad();
          },
          error: function() {
            alert("ERROR: Something went wrong.");
          }
      });
    });

});