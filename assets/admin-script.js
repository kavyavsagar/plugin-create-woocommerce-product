jQuery(document).ready( function($) {
	$(".ula-req-btn").click( function(e) {
		
		e.preventDefault();		
		var data = {
			action: 'api_request',
            post_var: true
		};
		$('#alert-msg').text('Loading...');
		// the_ajax_script.ajaxurl is a variable that will contain the url to the ajax processing file
	 	$.post(the_ajax_script.ajaxurl, data, function(res) {
	 		
	 		var response = JSON.parse(res);
			
			if(!response.error){
				$('#alert-msg').addClass('ula-success').text(response.message);
			}else{
				$('#alert-msg').addClass('ula-error').text(response.message);
			}
	 	});
	 	return false;
	});
});