jQuery(document).ready( function($) {
	$('.ul-model').fSelect({
	    placeholder: 'Trouvez votre modèle',
	    numDisplayed: 3,
	    overflowText: '{n} sélectionné',
	    noResultsText: 'Aucun résultat trouvé',
	    searchText: 'rechercher',
	    showSearch: true
	});

	$(".test-button").click(function(e){ 
	    e.preventDefault();  // Prevent the click from going to the link

	    var tid = $(this).attr('id'),
	        //qty = $("#quantity"+tid).val();
	        product = $("#product"+tid).val();

	    $.ajax({
	        url: wc_add_to_cart_params.ajax_url,
	        method: 'post',
	        data: { 
	            'action': 'myajax',
	            'product': product,
	        }
	    }).done( function (response) {

	          if( response.error != 'undefined' && response.error ){
	            //some kind of error processing or just redirect to link
	            // might be a good idea to link to the single product page in case JS is disabled
	            return true;
	          } else {
	            window.location.href = 'https://deblocage-telephone-mobile.com/panier/';
	            //UL_USER_AJAX.checkout_url;
	          }
	    });

  	});


});