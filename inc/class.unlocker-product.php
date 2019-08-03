<?php
class Unlocker_Product{
	
	private $wpdb;

	// Constructor
    function __construct() {
        global $wpdb;        
        $this->wpdb = $wpdb;
    }

    function ulp_add_product($item){

		$user_id = get_current_user_id(); 
		$sarr = [];
		//foreach( $params as $item) {

	    $post_id = wp_insert_post( array(
	        'post_author' => $user_id,
	        'post_title' => $item['brand_name'],
	        'post_content' => $item['brand_name']. ' group includes a collection of models. You can purchase license of any of the model with this product',
	        'post_status' => 'publish',
	        'post_type' => "product",
	    ) );
	    $sarr[] = $this->clean($item['brand_name']); 
	    $sarr[] = $item['id'];
	    $sku =  implode("-", $sarr);
	    wp_set_object_terms( $post_id, 'simple', 'product_type' );

	    update_post_meta( $post_id, '_visibility', 'visible' );
	    update_post_meta( $post_id, '_stock_status', 'instock');
	    update_post_meta( $post_id, 'total_sales', '0' );
	    update_post_meta( $post_id, '_downloadable', 'no' );
	    update_post_meta( $post_id, '_virtual', 'yes' );
	    update_post_meta( $post_id, '_regular_price', $item['price'] );
	    update_post_meta( $post_id, '_sale_price', '' );
	    update_post_meta( $post_id, '_purchase_note', '' );
	    update_post_meta( $post_id, '_featured', 'no' );
	    update_post_meta( $post_id, '_weight', '' );
	    update_post_meta( $post_id, '_length', '' );
	    update_post_meta( $post_id, '_width', '' );
	    update_post_meta( $post_id, '_height', '' );
	    update_post_meta( $post_id, '_sku', $sku );
	    update_post_meta( $post_id, '_product_attributes', array() );
	    update_post_meta( $post_id, '_sale_price_dates_from', '' );
	    update_post_meta( $post_id, '_sale_price_dates_to', '' );
	    update_post_meta( $post_id, '_price', $item['price'] );
	    update_post_meta( $post_id, '_sold_individually', '' );
	    update_post_meta( $post_id, '_manage_stock', 'no' );
	    update_post_meta( $post_id, '_backorders', 'no' );
	    update_post_meta( $post_id, '_stock', '' );
		//}

	    return $post_id;
    }

    private function clean($string) {
    	$string = trim($string);
   		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

   		$res = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

   		return strtolower($res);
	}

	public function ulp_delete_product($postid){
		$force_delete = false;

	    delete_post_meta( $postid, '_visibility' );
	    delete_post_meta( $postid, '_stock_status');
	    delete_post_meta( $postid, 'total_sales');
	    delete_post_meta( $postid, '_downloadable');
	    delete_post_meta( $postid, '_virtual' );
	    delete_post_meta( $postid, '_regular_price');
	    delete_post_meta( $postid, '_sale_price');
	    delete_post_meta( $postid, '_purchase_note');
	    delete_post_meta( $postid, '_featured');
	    delete_post_meta( $postid, '_weight' );
	    delete_post_meta( $postid, '_length');
	    delete_post_meta( $postid, '_width' );
	    delete_post_meta( $postid, '_height');
	    delete_post_meta( $postid, '_sku' );
	    delete_post_meta( $postid, '_product_attributes' );
	    delete_post_meta( $postid, '_sale_price_dates_from');
	    delete_post_meta( $postid, '_sale_price_dates_to');
	    delete_post_meta( $postid, '_price');
	    delete_post_meta( $postid, '_sold_individually' );
	    delete_post_meta( $postid, '_manage_stock' );
	    delete_post_meta( $postid, '_backorders' );
	    delete_post_meta( $postid, '_stock' );

		wp_remove_object_terms( $postid, 'simple', 'product_type' );

		wp_delete_post( $postid, $force_delete);
	}
}
?>