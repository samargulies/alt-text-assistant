(function($, _){

	var media = wp.media, l10n;

	// Link any localized strings.
	l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;

	var oldMediaFramePost = wp.media.view.MediaFrame.Post;
	wp.media.view.MediaFrame.Post = oldMediaFramePost.extend({
	
		mainInsertToolbar: function( view ) {
			var controller = this;

			this.selectionStatusToolbar( view );

			view.set( 'insert', {
				style:    'primary',
				priority: 80,
				text:     l10n.insertIntoPost,
				requires: { selection: true },

				/**
				 * @fires wp.media.controller.State#insert
				 */
				click: function() {
					var state = controller.state(),
						selection = state.get('selection');
								
					// this is where the click happens. We want to hijack that when there isn't an alt tag
					$.when.apply( $, selection.map( function( attachment ) {
		
						if( !attachment.attributes.alt ) {
							// caught ya!					
							alert( altTextAssistant.alertMessage );
							$('.attachment-details .setting[data-setting=alt] span').css('color','#C00');
							$('.attachment-details .setting[data-setting=alt]').css('background-color','#FFE5E5');
							$('.attachment-details .setting[data-setting=alt] input').select();
					
							return;
						} else {
							// ok, do the normal close and insert
							controller.close();
							state.trigger( 'insert', selection ).reset();
						}
			
					}, this ) );
			


				}
			});
		}
	});

}(jQuery, _));