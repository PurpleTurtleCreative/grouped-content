jQuery(function($) {

  /* Parent page selection */
  $('#content-generator select#parent-page-id').change(function() {
    if( $(this).val() == -1 ) {
      $('#create-new-parent-page').css('display', 'block');
      $('#new-parent-page-title').prop('required', true);
    } else {
      $('#create-new-parent-page').css('display', 'none');
      $('#new-parent-page-title').prop('required', false);
    }
  });

  /* Form Validation */
  $('#content-generator form').submit(function( event ) {
    if(
      $('#content-generator select#parent-page-id').val() == -1
      && $.trim( $('#new-parent-page-title').val() ) == ''
    ) {
      alert('Please enter a title to create a new parent page.');
      event.preventDefault();
      return false;
    }
    return true;
  });

});