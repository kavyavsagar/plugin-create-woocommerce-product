<?php

require_once( UNL__PLUGIN_INC . 'class.unlocker-product.php' );

class Unlocker_Rest_Api{
	
	private $wpdb;
	private $prod;

	// Constructor
    function __construct() {
        global $wpdb;        
        $this->wpdb = $wpdb;

        $this->prod = new Unlocker_Product();

    }
    // Get model name from api and save it to db
    function ulr_api_model_names(){
    	// empty datas before fetching

        $this->ulr_delete_all_group();    	
    	$modelTable = $this->wpdb->prefix . 'mob_model';    	
    	$this->wpdb->query("TRUNCATE TABLE $modelTable");
    	//

    	$url = get_option('ula_api_root_url')."models.txt";
    	$textData = file_get_contents($url);

    	$remove = "\n"; $tab = "\t"; 
    	$split = explode($remove, $textData);

		$brandList = []; $modelList = []; $responds = [];		
		$preGroups = array("Type1", "Type2", "Type3", "Type4", "Type5", "Type6", "Type7");

		foreach ($split as $string)
		{
		    $row = explode($tab, $string);
		    $model = trim($row[0]);

		    if($model != ''){
		    	// brands		    
			    $abrand = explode(" ", $model);	  
			    $brand = trim($abrand[0]);

			    if(!in_array($brand, $brandList)){
			    	$brandList[] = $brand;
			    	$bid = $this->ulr_insert_brand($brand);

			    	if(!$bid) { $responds = array("error"=> true, "message" => "Brand DB error !!"); return json_encode($responds);}

			    }else if(in_array($abrand[1], $preGroups) && !in_array($model, $brandList)){
			    	$brandList[] = $model;
			    	$bid = $this->ulr_insert_brand($model);	

			    	if(!$bid) { $responds = array("error"=> true, "message" => "Brand DB error !!"); return json_encode($responds);}
			    }

				// models
				if(!in_array($model, $modelList) && !in_array($abrand[1], $preGroups)){
					$modelList[] = $model;
					$margs = array("brand_id" => $bid, "model_name" => $model);
					$mid = $this->ulr_insert_model($margs);

					if(!$mid) { $responds = array("error"=> true, "message" => "Model DB error !!"); return json_encode($responds);}
				}
			}
		}

		$responds = array("error"=> false, "message" => "Success !! Model data get updated.");
		return json_encode($responds);
    }

    function ulr_insert_brand($insert)
    {	        

        if(!$insert['id']){    
    	
        	$b_sql = "INSERT INTO ". $this->wpdb->prefix . "mob_brand (brand_name, api_name, price, status) VALUES ('". $insert['brand_name'] ."', '". $insert['api_name'] ."', ".$insert['price'].", ".$insert['status'].")";	
        	$this->wpdb->query($b_sql);
    		
    		$brandId = $this->wpdb->insert_id;	
		}

		if($this->wpdb->last_error !== ''){
			return false;
		}

		return $brandId;
    }

    function ulr_update_brand($uparg)
    {	

    	$setQry = [];
    	if(isset($uparg['price']) && $uparg['price']){
    		$setQry[] = "price = ". $uparg['price'] ;
    	}
    	if(isset($uparg['brand_name']) && $uparg['brand_name']){
    		$setQry[] = "brand_name = '". $uparg['brand_name'] ."'" ;
    	}
        if(isset($uparg['api_name']) && $uparg['api_name']){
            $setQry[] = "api_name = '". $uparg['api_name'] ."'" ;
        }
    	if(isset($uparg['status'])){
    		$setQry[] = "status = ". $uparg['status'] ;
    	}
    	if(isset($uparg['product_id']) && $uparg['product_id']){
    		$setQry[] = "product_id = '". $uparg['product_id'] ."'" ;
    	}
 
    	$id = (isset($uparg['brand_id']))? $uparg['brand_id'] : $uparg['id'];

    	$b_sql = "UPDATE ". $this->wpdb->prefix . "mob_brand SET ". implode(", ", $setQry) ." WHERE id = ". $id;	
    	$result = $this->wpdb->query($b_sql);
		
		if( $this->wpdb->last_error !== ''){
			return false;
		}

		return $id;
    }

    function ulr_insert_model($insert){   
    	
    	if(!isset($insert['model_id']) || !$insert['model_id']){    	
	    	$m_sql = "INSERT INTO ". $this->wpdb->prefix . "mob_model (model_name, brand_id, status) VALUES ('". $insert['model_name'] ."', " .$insert['brand_id']. ", 1)";		    
			$result = $this->wpdb->query($m_sql);

			$modelId = $this->wpdb->insert_id;	
		}else{
			$setQry = [];

			if(isset($insert['model_name']) && $insert['model_name']) $setQry[] = "model_name = '". $insert['model_name'] ."'";	
			if(isset($insert['brand_id']) && $insert['brand_id']) $setQry[] = "brand_id = ". $insert['brand_id'] ;

			$m_sql = "UPDATE ". $this->wpdb->prefix . "mob_model SET ". implode(", ", $setQry) ." WHERE id = ". $insert['model_id'];	
    		$result = $this->wpdb->query($m_sql);

    		$modelId = $insert['model_id'];
		}

		if($this->wpdb->last_error !== ''){
			return false;
		}

		return $modelId;
    }

    function ulr_brand_list(){
    	$brand_table = $this->wpdb->prefix . "mob_brand";
    	$aresult = $this->wpdb->get_results("SELECT * from $brand_table WHERE status = 1 ORDER BY brand_name ASC", ARRAY_A);

    	return $aresult;
    }
    function ulr_brand_row($arg){    	
		$cond = '';    	
        $table_name = $this->wpdb->prefix . 'mob_brand'; 
        
        if(isset($arg['id'])  && $arg['id']){
			$cond .= ' AND id = '. $arg['id'];
		}
        if(isset($arg['product'])  && $arg['product']){
            $cond .= ' AND product_id = '. $arg['product'];
        }

    	$aresult = $this->wpdb->get_row("SELECT * FROM $table_name WHERE 1 = 1 $cond ORDER BY brand_name ASC", ARRAY_A);

    	return $aresult; 
    }
	function ulr_brand_model_row($arg){    	
		$cond = '';
    	$table_name = $this->wpdb->prefix . 'mob_model'; // do not forget about tables prefix
        $join = $this->wpdb->prefix . 'mob_brand'; 
        
        if(isset($arg['id'])  && $arg['id']){
			$cond .= ' AND '.$table_name.'.id = '. $arg['id'];
		}

    	$aresult = $this->wpdb->get_row("SELECT $table_name.id, $table_name.model_name, $table_name.brand_id, $join.price, $join.brand_name FROM $table_name LEFT JOIN $join ON $table_name.brand_id = $join.id WHERE $table_name.status = 1 $cond ORDER BY model_name ASC", ARRAY_A);

    	return $aresult; 
    }

    function ulr_brand_models($attr){    	

    	$table_name = $this->wpdb->prefix . 'mob_model'; // do not forget about tables prefix
        $join = $this->wpdb->prefix . 'mob_brand';

        $cond = "";
        if(null !== $attr['brand']){
        	$br = $attr['brand'];
        	$cond .= " AND $join.brand_name LIKE '{$br}%' ";
        } 
     
    	$aresult = $this->wpdb->get_results("SELECT $table_name.id, $table_name.model_name, $table_name.brand_id, $join.price, $join.brand_name FROM $table_name LEFT JOIN $join ON $table_name.brand_id = $join.id WHERE $table_name.status = 1 $cond ORDER BY model_name ASC", ARRAY_A);

    	return $aresult; 
    }

    function ulr_price_by_groups($attr){

    	$table_name = $this->wpdb->prefix . 'mob_model'; // do not forget about tables prefix
        $join = $this->wpdb->prefix . 'mob_brand';

        $cond = ""; $aresult = [];
        if(null !== $attr['brand']){
        	$br = $attr['brand'];
        	$cond .= " AND $join.brand_name LIKE '{$br}%' ";
        } 
        if(isset($attr['models']) && null !== $attr['models']){
        	$md = $attr['models'];
        	$cond .= " AND $table_name.id IN ($md)";
        
        	$aresult = $this->wpdb->get_results("SELECT $join.id, $join.price, $join.product_id, $join.brand_name, count($table_name.id) AS mcount FROM $table_name LEFT JOIN $join ON $table_name.brand_id = $join.id WHERE $table_name.status = 1 $cond GROUP BY  $join.id", ARRAY_A);    	   

        	foreach ($aresult  as $key => $value) {
                
        		/*$mresult = $this->wpdb->get_results("SELECT id, model_name from $table_name WHERE status = 1 AND id NOT IN ($md) AND brand_id=".$value["id"]. " LIMIT 0, 4", ARRAY_A);*/

                // models selected
                $model_sel = $this->wpdb->get_results("SELECT id, model_name from $table_name WHERE status = 1 AND id IN ($md) AND brand_id=".$value["id"], ARRAY_A); 

        		$arr = [];
    		/*	foreach ($mresult as $mkey => $mvalue) {                  
    				$arr[] = $mvalue['model_name'];
    			}*/
                // selected model names
                foreach ($model_sel as $mskey => $msval) {
                    $arr[] = $msval["model_name"];
                }                

        		$aresult[$key]['model_name'] = implode(", ", $arr);
        	}
        }
    	
    	return $aresult; 
    }

    function ulr_insert_payment($args){
    	if(empty($args)){
    		return false;
    	}    

    	$m_sql = "INSERT INTO ". $this->wpdb->prefix . "mob_payment (item_number, user_email, txn_id, amount, status) VALUES (". $args["item_number"] .", '". $args["user_email"] ."', '" .$args["txn_id"]. "', ".$args["payment_amount"].", '".$args["payment_status"]."')";		    
		$result = $this->wpdb->query($m_sql);

        $payment_id = $this->wpdb->insert_id;  

		if($this->wpdb->last_error !== ''){
			return false;
		}

		return $payment_id;
    }

    function ulr_api_licence_code($model){
    	
		$ch = curl_init();

		$post_fields = array(
			'Username' => get_option('ula_api_user_name'),
			'Password' => get_option('ula_api_password'),
			'ServiceID' => 'Direct unlock',
			'ModelName' => $model
		);

		curl_setopt($ch, CURLOPT_URL, get_option('ula_api_root_url'));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
		// Receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec($ch);
        $output =  json_decode($server_output, true);
        // do anything you want with your response

		$info = curl_getinfo($ch);
		// Check the http response
		$httpCode = $info['http_code'];
		if ($httpCode != 200) {
		    //throw new Exception("PayPal responded with http code $httpCode");
		    return false;
		}
		// Check for error
		if(isset($output['ERROR']) && $output['ERROR']){
            echo $output['ERROR'];
            return false;
        }

		curl_close ($ch);

		return $output["LICENCE"];
    }

    public function ulr_create_products($ids){
    	
        $table_name = $this->wpdb->prefix . 'mob_brand';
     
    	$aresult = $this->wpdb->get_results("SELECT * FROM $table_name WHERE status = 1 AND id IN ($ids) ORDER BY brand_name ASC", ARRAY_A);

    	if(!empty($aresult)){
    		foreach( $aresult as $item) {
    			$pid = $this->prod->ulp_add_product($item); // add product

    			$params =  array("product_id" => $pid, "id" => $item["id"]);
    			$res = $this->ulr_update_brand($params);
    		}
    	}    		
    }
    public function ulr_delete_models($arg){
        $table_name = $this->wpdb->prefix . 'mob_model'; // do not forget about tables prefix
        $cond = '';
        if(isset($arg['id']) && $arg['id']){ // model ids
            $cond .= 'id IN (' .$arg['id']. ')';
        }
        if(isset($arg['brandid']) && $arg['brandid']){ // brand ids
            $cond .= 'brand_id = ' .$arg['brandid'];
        }

        $this->wpdb->query("DELETE FROM $table_name WHERE $cond");
    }
    public function ulr_delete_group_product($ids){

        $table_name = $this->wpdb->prefix . 'mob_brand';
        $aid =  explode(",", $ids);

        if(!empty($aid)){
            foreach( $aid as $id) {                
                $res = $this->ulr_brand_row(array("id" => $id));

                // delete models
                $this->ulr_delete_models(array("brandid" => $id));

                if($res['product_id']){
                    // if product exists, delete it
                    $this->prod->ulp_delete_product($res['product_id']); // delete product
                }               
            }
        }
        
        $this->wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
    }

    public function ulr_delete_all_group(){
        $table_name = $this->wpdb->prefix . 'mob_brand';
        $result = $this->ulr_brand_list();

        if(!empty($result)){
            foreach( $result as $res) {  
                if($res['product_id']){
                    // if product exists, delete it
                    $this->prod->ulp_delete_product($res['product_id']); // delete product
                } 
            }
        }

        $this->wpdb->query("TRUNCATE TABLE $table_name");
    }
}
?>