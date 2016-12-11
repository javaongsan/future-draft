(function( $ ) {
	'use strict';

	$("#new-tab").live('click',function(){
			var post_data = {
				action : 'create_new_draft',
				post_type : $(this).data('post-type'),
				post_id : $(this).data('post-id'),
				version_no : $(this).data('version-no'),
				future_draft_version_nonce: future_draft_version_vars.nonce
			};

			$.post(future_draft_version_vars.ajaxurl, post_data, function(response) {
				console.log(response);
				if (response) {
					window.location = response;
				}
			});
			return false;
		});

	$.fn.extend({ 
		tabify_tabs: function() {
			//Iterate over the current set of matched elements
			return this.each(function() {
				var obj = $(this);

				$( ".tabify-tab", obj ).on("click", function( evt ) {
					evt.preventDefault();

					$( ".tabify-tab", obj ).removeClass( 'nav-tab-active' );
					$( this, obj ).addClass( 'nav-tab-active' );

					var id = evt.target.id.replace( 'tab-', "");
					tabify_show_tab( id, $( this ).closest('.tabify-tabs') );
				});

				function tabify_show_tab( id, holder ) {
					if( id && id.length != 0 ) {
						$( ".tabifybox" ).hide();
						$( ".current_tab", holder ).val( id );

						$( ".tabifybox-" + id ).each( function( index ) {
							var checkbox = $( '#' + $(this).attr('id') + '-hide' );

							if( checkbox.attr('type') != 'checkbox' || checkbox.is(':checked') ) {
								$(this).show();
							}
						}).promise().done( function(){ tabify_fix_editors() } );
					}
				}

				function tabify_fix_editors() {
					var editors = $('.wp-editor-tools');
					editors.each(function( index ) {
						editor = $( this );

						if ( editor.closest('.tabifybox').is(':visible') ) {
							if( ! editor.width() ) {
								$(document).trigger('postbox-toggled');

								return false;
							}
						}
					});
				}
			});
		}
	});

})( jQuery );
