jQuery(function($) {

  console.log('Loaded page relatives metabox JS!');

  try {
    if (
      typeof wp.data !== undefined
      && wp.data.select('core/editor') !== null
    ) {

      var hasChangedParentPage = false;//keeps track if parent page was actually changed between multiple saves
      var requestRefreshTimers = [];//keep track of timers for clearing once successful

      wp.data.subscribe(function() {

        var isSavingPost = wp.data.select('core/editor').isSavingPost();//boolean
        var isAutosavingPost = wp.data.select('core/editor').isAutosavingPost();//boolean
        var hadSuccessfulSave = wp.data.select('core/editor').didPostSaveRequestSucceed();//boolean

        var currentParentPage = wp.data.select( 'core/editor' ).getCurrentPostAttribute( 'parent' );//int
        var editedParentPage = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'parent' );//string

        if (
          isSavingPost
          && ! isAutosavingPost
          && editedParentPage == currentParentPage
          && hadSuccessfulSave
          && hasChangedParentPage
        ) {

          requestRefresh = setTimeout(function() {

            var metaboxContainer = $('#ptc-grouped-content div.inside');

            var currentHTML = metaboxContainer.html();
            metaboxContainer.html('<p id="ptc-notice-refreshing-metabox"><i class="fas fa-sync-alt fa-spin"></i>Refreshing page relatives...</p>');

            var data = {
              'action': 'refresh_page_relatives',
              'nonce': ptc_page_relatives.nonce,
              'post_id': wp.data.select('core/editor').getCurrentPostId(),
              'edited_parent': editedParentPage
            };

            $.post(ajaxurl, data, function(res) {

              if(res.status == 'success') {
                //update metabox content
                metaboxContainer.html(res.data);
                //cancel future runs after success
                requestRefreshTimers.forEach(clearTimeout);
                requestRefreshTimers = [];
                //reset to check for new change
                hasChangedParentPage = false;
              } else {
                // console.error(res.data);
                metaboxContainer.html(currentHTML);
              }

            }, 'json')
              .fail(function() {
                console.error('Failed to refresh Page Relatives metabox content.');
                metaboxContainer.html(currentHTML);
              });

          }, 1000);

          requestRefreshTimers.push(requestRefresh);

        } else if(
          isSavingPost
          && ! isAutosavingPost
          && editedParentPage != currentParentPage
          && hadSuccessfulSave
        ) {
          hasChangedParentPage = true;
        }//end if successfully updated parent page

      });//end wp.data.subscribe listener

    }//end if wp.data Gutenberg
  } catch(error) {
    console.error(error.message);
  }

});//end document ready