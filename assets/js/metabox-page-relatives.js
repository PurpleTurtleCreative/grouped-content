jQuery(function($) {

  /* Classic Editor */
  try {
    if ( typeof wp.data === "undefined" || wp.data.select('core/editor') === null ) {
      /* Add parent page option */
      if ( ptc_page_relatives.post_parent > 0 && ptc_page_relatives.post_parent_title !== '' ) {
        let parentPageSelect = jQuery('#pageparentdiv select#parent_id');
        if ( parentPageSelect.length === 1 && parentPageSelect.val() !== ptc_page_relatives.post_parent ) {
          parentPageSelect.append('<option class="level-0" value="'+ptc_page_relatives.post_parent+'">'+ptc_page_relatives.post_parent_title+'</option>');
          parentPageSelect.val(ptc_page_relatives.post_parent);
        }
      }
    }
  } catch(error) {
    console.error(error.message);
  }

  /* Gutenberg Editor */
  try {
    if ( typeof wp.data !== "undefined" && wp.data.select('core/editor') !== null ) {

      /* Add parent page option */
      if ( ptc_page_relatives.post_parent > 0 && ptc_page_relatives.post_parent_title !== '' ) {
        let postParentAttributesInterval = setInterval(function() {
          console.log('[Grouped Content] Checking for post parent select input...');
          let parentPageSelect = jQuery('div.editor-page-attributes__parent select');
          if ( parentPageSelect.length === 1 && parentPageSelect.val() !== ptc_page_relatives.post_parent ) {
            clearInterval(postParentAttributesInterval);
            if ( ptc_page_relatives.post_parent == wp.data.select('core/editor').getCurrentPostAttribute('parent') ) {
              parentPageSelect.append('<option value="'+ptc_page_relatives.post_parent+'">'+ptc_page_relatives.post_parent_title+'</option>');
              parentPageSelect.val(ptc_page_relatives.post_parent);
            }
          }
        }, 500);
      }

      /* Refresh Page Relatives metabox if post_parent is updated */
      let hasChangedParentPage = false;//keeps track if parent page was actually changed between multiple saves
      let requestRefreshTimers = [];//keep track of timers for clearing once successful

      wp.data.subscribe(function() {

        let isSavingPost = wp.data.select('core/editor').isSavingPost();//boolean
        let isAutosavingPost = wp.data.select('core/editor').isAutosavingPost();//boolean
        let hadSuccessfulSave = wp.data.select('core/editor').didPostSaveRequestSucceed();//boolean

        let currentParentPage = wp.data.select('core/editor').getCurrentPostAttribute('parent');//int
        let editedParentPage = wp.data.select('core/editor').getEditedPostAttribute('parent');//string

        if (
          isSavingPost
          && ! isAutosavingPost
          && editedParentPage == currentParentPage
          && hadSuccessfulSave
          && hasChangedParentPage
        ) {

          requestRefresh = setTimeout(function() {

            let metaboxContainer = $('#ptc-grouped-content div.inside');

            let currentHTML = metaboxContainer.html();
            metaboxContainer.html('<p id="ptc-notice-refreshing-metabox"><i class="fa fa-refresh fa-spin"></i>Refreshing page relatives...</p>');

            let data = {
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