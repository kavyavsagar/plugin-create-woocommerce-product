<?php
include_once( UNL__PLUGIN_INC .'language.php');

class Unlocker_User_Page {
	private $api;
	private $lang;

	function __construct() {	
		global $language;
		$this->api = new Unlocker_Rest_Api();	
		$this->lang = $language;

		add_action('wp_enqueue_scripts',  array( $this, 'ulu_for_setting_up_styles' ) );// css

		add_action('wp_enqueue_scripts',  array( $this, 'ulu_for_setting_up_scripts' ) ); // js

		add_action('wp_ajax_myajax', array( $this, 'ulu_myajax_callback') );
		
		add_action('wp_ajax_nopriv_myajax', array( $this, 'ulu_myajax_callback'));

		//add_action( 'woocommerce_thankyou_paypal',  array( $this, 'ulu_add_content_thankyou_paypal' ), 5 ); // top of the page

		add_action( 'woocommerce_thankyou', array( $this, 'ulu_add_content_thankyou' ), 5 );
	
	}

	function ulu_for_setting_up_styles(){
		wp_enqueue_style( 'custom_ulu_multi_select', UNL__PLUGIN_ASSET.'fSelect.css' );

		wp_enqueue_style( 'custom_ulu_user_css', UNL__PLUGIN_ASSET.'ulu-style.css' ); // custom style
	}

	function ulu_for_setting_up_scripts() {
	    wp_enqueue_script( 'custom_ulu_multi_select', UNL__PLUGIN_ASSET.'fSelect.js' , array( 'jquery' ) ); // custom script

		wp_enqueue_script( 'custom_ulu_user_script', UNL__PLUGIN_ASSET.'ulu-script.js' , array( 'jquery' ) ); 

	    $i18n = array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'checkout_url' => get_permalink( wc_get_page_id( 'cart' ) ) );
	    wp_localize_script( 'custom_ulu_user_script', 'UL_USER_AJAX', $i18n );

	}
    /**
     * AJAX add to cart.
     */
	function ulu_myajax_callback() {        
        ob_start();

        if(isset($_POST['product']) && $_POST['product']){
       
	        $product_id        = $_POST['product'];
	        $quantity          = 1;
	        $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
	        $product_status    = get_post_status( $product_id );

	        if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity ) && 'publish' === $product_status ) {

	            do_action( 'woocommerce_ajax_added_to_cart', $product_id );

	            wc_add_to_cart_message( $product_id );

	        } else {

	            // If there was an error adding to the cart, redirect to the product page to show any errors
	            $data = array(
	                'error'       => true,
	                'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id )
	            );

	            wp_send_json( $data );

	        }
    	}

     	wp_die(); 
    }       

    function ulu_models_by_groups($arg){
    
    	$res_models = $this->api->ulr_price_by_groups($arg);

  		?>	     
			
		<table class="ul-list-table" border="0"> 			
			<tr>		  
			   <th><?=$this->lang["GROUP"]?></th>
			   <th><?=$this->lang["MODELS"]?></th>
			   <th><?=$this->lang["PRICE"]?></th>
			   <th></th>
			</tr> 	
		<?php foreach($res_models as $mk => $mv):?>	
			<input type="hidden" id="item<?=$mv["id"]?>" value="<?=$mv["model_name"]?>">
			<input type="hidden" id="amount<?=$mv["id"]?>" value="<?=$mv["id"]?>">

			<input type="hidden" id="product<?=$mv["id"]?>" value="<?=$mv["product_id"]?>">			
			<tr id="row<?=$mv["id"]?>">				
				<td><?=$mv["brand_name"]?></td>
				<td width="40%"><?=$mv["model_name"]?><?=$mv['mcount'] > 4? '...': ''?></td>
				<td><?=$mv["price"]?></td>
				<td><button type="button" name="catbtn" class="ul-btn-search test-button" id="<?=$mv["id"]?>"><?=$this->lang["ADD_CART"]?></button>
				</td>
			</tr>
		
		<?php endforeach;?>
		</table>

    <?php }

	function ulu_search_form($attr){

        $groups = $this->api->ulr_brand_models($attr);
       
	?>
		<div class="ul-container"> 
		  <form method="POST" name="ul-form-search" action="<?=$_SERVER['REQUEST_URI']?>">
			<input type="hidden" id="selModels" name="selModels" value="<?=isset($_POST['selModels'])?$_POST['selModels']: '' ?>"/>
			<select id="ul-model" class="ul-model" name="modelid[]" required  multiple="multiple">
				<option value="">-- <?=$this->lang["MODELS"]?> --</option>
				<?php foreach ($groups as $model) : ?>
	            <option value="<?=$model["id"]?>"><?=$model["model_name"]?></option>
	            <?php endforeach; ?>
			</select>
			<div class="ul-left-btn">
			<button type="submit" name="submit" class="ul-btn-search"><?=$this->lang["SEARCH"]?></button>
			</div>
			<div class="clearx"> </div>
		  </form>
		</div>
	<?php	
	}
	
    function ulu_complete_form_process($attr) {	 
 
	    $this->ulu_search_form($attr);

	    if ( isset($_POST['submit'] ) ) { 

	    	$model = $_POST['modelid'];
	    	$attr['models'] = implode(",", $model); // model ids
	    	
	    	$this->ulu_models_by_groups($attr);	    
	    }
	   // $this->ulu_order_payment();
	 
	}
 
	// The callback function that will replace [book]
	function ulu_search_models_shortcode($attr) {
	    ob_start();

	    $this->ulu_complete_form_process($attr);

	    return ob_get_clean();
	}


	function ulu_add_content_thankyou(){		

		$parts = parse_url($_SERVER['REQUEST_URI']);
		$order_id =  preg_replace("/[^0-9]/", '', $parts['path']);

		// If transaction data is available in the URL 
	  if($order_id && !empty($_GET['key']))
	  { 
	  	// Get transaction information from URL 
		$order = new WC_Order($order_id);

		$user_email = $order->get_billing_email();
		$txt = $order->get_transaction_id();
		$status = $order->get_status();
		$items = $order->get_items();		

	  	foreach ($items as $key => $item) {
		    $product_id = $item->get_product_id();
		    $amt = $item->get_total();		

		    $brand_data = $this->api->ulr_brand_row(array("product" => $product_id));
		    $api_call_name = $brand_data['api_name'];

		    $transaction = [
			    'item_number' => $brand_data["id"], //brand id
			    'payment_status' => $status,
			    'payment_amount' =>  $amt,
			    'txn_id' => $txt,
			    'user_email' =>  $user_email
			];
			
			// Check if paypal request or response
			$payment_id = $this->api->ulr_insert_payment($transaction);	   
		}
	  
		//if ($payment_id !== false) {  // Payment successfully added.	?>

		<?php // Get licence
			$licence = $this->ulu_licence_display($api_call_name);	

			if($licence){
				// Deliver licence via email
				$args =  array("user_email" => $transaction["user_email"], 
							"licence_code" => $licence,
							"model_name" => $api_call_name);
				$this->ulu_deliver_mail($args);
			}

	   // }else{ // payment failed ?> 

	        <!-- <p class="ul-error">Your Payment Failed</p> -->
	    <?php //} 
	    }
	}

	function ulu_licence_display($item){

		$licence = $this->api->ulr_api_licence_code($item);
		if($licence !== false) { 		
		?>	
			<h4><?=$this->lang["UNLOCK_TITLE"]?></h4>
		    <p><?=$this->lang["UNLOCK_TEXT"]?></p>
		    <p class="code"><b><?=$this->lang["CODE"]?> :</b> <?=$licence?></p>
	    <?php 
	    	return $licence;
		}
	}

	function ulu_deliver_mail($args){
		$headers = array();

        $to   = $args["user_email"] ;
        $subject = $this->lang["EMAIL_SUBJECT"];
        
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        // get the blog administrator's email address
        $from = get_option( 'admin_email' );
        $headers[] = "From: admin <$from>";

        $text = $args["model_name"];  

        $content = str_replace(
		    array('%text%', '%code%'),
		    array($text, $args["licence_code"]),
		    file_get_contents( UNL__PLUGIN_INC .'email-template.html')
		);

        // If email has been process for sending, display a success message
      	wp_mail( $to, $subject, $content, $headers );
    
	}
	
}	

// Register a new shortcode: [unlock_mobile_model]
$ulu = new Unlocker_User_Page();
add_shortcode( 'unlock_mobile_model', array($ulu, 'ulu_search_models_shortcode') );

?>