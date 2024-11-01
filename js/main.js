function submitform()
{	    
	document.backup_form.submit();	
}
jQuery(document).ready(function(){	
	var frm = jQuery('#renaming_form');
		frm.submit(function(ev) {
			jQuery('.divMsg').show();			
			jQuery.post(jQuery(this).attr('action'), jQuery(this).serialize(), function(res){
				// Do something with the response `res`				
				jQuery('body').html(res);
				jQuery('.divMsg').hide();				
				// Don't forget to hide the loading indicator!
			});			
			return false; // prevent default action			
		});	
	jQuery("input[name='drop_previous_wordpress']").click(function(){
		if(jQuery(this).is(':checked')){
			jQuery('.message_checkbox').hide();	
		}else{
			jQuery('.message_checkbox').show();	
		}
	});
	jQuery("input[name='use_mysqldump']").click(function(){
		if(jQuery(this).is(':checked')){
			jQuery('.message_mysqldump').hide();	
		}else{
			jQuery('.message_mysqldump').show();	
		}
	});	
});