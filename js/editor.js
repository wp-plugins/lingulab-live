/**
 * LinguLab Live Plugin Editor (javascript/jQuery)
 *
 * @author Tom Klingenberg
 */

/**
 * lingulabMain class
 * 
 * LinugLab Live Plugin Main Function
 */
function lingulabMain()
{
	/**
	 * checkKeywordActivity
	 * 
	 * @return void
	 */
	this.checkKeywordActivity = function()
	{
		actualvalue = jQuery('#lingulab-mode').val();
	    if (actualvalue) {
	        splitted = actualvalue.split(","); 
	        if(splitted[1] == 'true'){
	            jQuery('#lingulab-keywordsdiv').slideDown('slow');
	        }
	        else{
	            jQuery('#lingulab-keywordsdiv').slideUp('slow');
	        }
	    }
	}; // checkKeywordActivity function
	
	/**
	 * getEditorText
	 * 
	 * @return string
	 */
	this.getEditorText = function()
	{
		var text = '';
		
		if ( !tinyMCE.activeEditor || switchEditors.mode == 'tinymce' )
		{
			text = jQuery('textarea#content').val();
		} else {
			text = tinyMCE.activeEditor.getContent();
		}
		
		return text;
	}; // getEditorText function
	
	/**
	 * setEditorText
	 * 
	 * @param string text
	 * @return void
	 */
	this.setEditorText = function(text) 
	{
		if ( !tinyMCE.activeEditor || switchEditors.mode == 'tinymce' )
		{
			jQuery('textarea#content').val(text);
		} else {
			tinyMCE.activeEditor.setContent(text);
		}		
	}
	
} // lingulabMain class

/**
 * document ready implementation
 * 
 *  - check for prerequisites
 *  - assign event-hooks to the DOM 
 * 
 */
jQuery(document).ready(function()
{
	/* if there is an error loading the configuration, we have nothing to do left here */
	if ( 0 == jQuery('#lingulab-mode').length )
		return;
	
	var lingulab = new lingulabMain();	
	
	lingulab.checkKeywordActivity();
	jQuery('#lingulab-mode').change(lingulab.checkKeywordActivity);
		
	jQuery('#lingulab-check').click(function ()
	{

		if ( jQuery('#lingulab-check').hasClass('button-primary-disabled') )
			return false;
		
		jQuery('.done').hide();
		
		var text     = lingulab.getEditorText();
		var headline = jQuery('#title').val();
		var teaser	 = jQuery('#excerpt').val();
        var kw1      = jQuery('#lingulab-kw1').val();
        var kw2      = jQuery('#lingulab-kw2').val();
        var kw3      = jQuery('#lingulab-kw3').val();
		var mode     = jQuery('#lingulab-mode').val();
		var lang     = jQuery('#lingulab-lang').val();
				
		//build data string
		var data = 'text=' + encodeURIComponent(text)
					+ '&mode=' + encodeURIComponent(mode)
					+ '&lang=' + encodeURIComponent(lang)
 	                + '&kw1='  + encodeURIComponent(kw1)
                    + '&kw2='  + encodeURIComponent(kw2)
    		        + '&kw3='  + encodeURIComponent(kw3)
					+ '&h3='   + encodeURIComponent(teaser)
					+ '&h1='   + encodeURIComponent(headline);
		
		//send request
		jQuery('#lingulab-check').addClass('button-primary-disabled');
		jQuery('#lingulab .ajax-loading.check').show();
		jQuery('#lingulab-resultdiv').hide();
		
		jQuery.ajax({
			url:  ajaxurl + '?action=checkContent',
			type: 'POST',
			data: data,
			success: function (response)
			{
				jQuery('#lingulab .ajax-loading.check').hide();
				jQuery('#lingulab-check').removeClass('button-primary-disabled');
				if ( '' != response )
				{
					// set and show response					
					jQuery('#lingulab .done').html(response);
					jQuery('#lingulab .loading_icon').hide();
					jQuery('#lingulab-resultdiv').show();
					jQuery('#lingulab .done').fadeIn('slow');
                    jQuery('#lingulab-getcontentdiv').fadeIn('slow');
					
				} 
				else
				{
					// wenn der Request eine Form von false zurückschickt, Fehler ausgeben.
					alert('Fehler beim Abschicken des Formulares.');
				}
			}
		});
						
		return false;
	}); // click function
    
	jQuery('#lingulab-refreshtext').click(function ()
	{
		if ( jQuery('#lingulab-refreshtext').hasClass('button-primary-disabled') )
			return false;
		
		jQuery('#lingulab-refreshtext').addClass('button-primary-disabled');
		jQuery('#lingulab .ajax-loading.refresh').show();
		
		//send request
		jQuery.ajax({
			url: ajaxurl + '?action=getContent',
			type: 'POST',
			data: '',
    		success: function (response)
    		{
				jQuery('#lingulab-refreshtext').removeClass('button-primary-disabled');
				jQuery('#lingulab .ajax-loading.refresh').hide();
			
				r = wpAjax.parseAjaxResponse(response);				
				if ( 'object' == typeof r )
				{
					var data = r.responses[0].supplemental;
					if ( '1' == data.status )
					{
						jQuery('#title').val(data.h1);
						lingulab.setEditorText(data.text);
						jQuery('#excerpt').val(data.h3);
					} else {
						alert('Fehler beim Abschicken des Formulares.');
					}										
				} else {
					alert('Internet Fehler beim Zugriff.');
				}												
        	}
        });        
        return false;
	}); // click function

}); // ready function
