<?php 
require_once( UNL__PLUGIN_INC . 'class.unlocker-list-table.php' );

class Unlocker_Model{
    
    private $wpdb;
    private $api;

    // Constructor
    function __construct() {
        global $wpdb;        
        $this->wpdb = $wpdb;

        $this->api = new Unlocker_Rest_Api();
    }
    /**
     * PART 4. Form for adding andor editing row
     * ============================================================================
     *
     * In this part you are going to add admin page for adding andor editing items
     * You cant put all form into this function, but in this example form will
     * be placed into meta box, and if you want you can split your form into
     * as many meta boxes as you want
     *
     * http://codex.wordpress.org/Data_Validation
     * http://codex.wordpress.org/Function_Reference/selected
     */
    /**
     * Form page handler checks is there some data posted and tries to save it
     * Also it renders basic wrapper in which we are callin meta box render
     */
    public function ula_model_form()
    {
        //$table_name = $this->wpdb->prefix . 'mob_model'; // do not forget about tables prefix
        $message = '';
        $notice = '';
        // this is default $item which will be used for new records
        $default = array(
            'id' => 0,
            'model_name' => '',
            'brand_id' => '',
            //'price' => '0.00',
        );
        // here we are verifying does this request is post back and have correct nonce
        if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
            // combine our default item with request params
            $item = shortcode_atts($default, $_REQUEST);
            // validate data, and if all ok save item to database
            // if id is zero insert otherwise update
            $item_valid = $this->ula_validate_model($item);
            if ($item_valid === true) {
                if ($item['id'] == 0) { // new data
                    // brand update
                    /*$bargs = array("price" => $item["price"], "brand_id" => $item["brand_id"]);
                    $bres = $this->api->ulr_update_brand($bargs);*/
                    // model insert
                    $margs = array("brand_id" => $item["brand_id"]);
                    $mlist = explode(",", trim($item['model_name']));
                    $mids = array(); 
                    foreach ($mlist as $mkey => $mvalue) {
                        $margs['model_name'] = trim($mvalue);
                        $result = $this->api->ulr_insert_model($margs);
                        if(!$result){                            
                            break;
                        }
                        $mids[$mkey] = $result;                       
                    }

                    $item['id'] = implode(",", $mids);
                    if ($result) {
                        $message = __('Item was successfully saved', 'cltd_example');
                    } else {
                        $notice = __('There was an error while saving item', 'cltd_example');
                    }
                } else { // update

                    // brand update
                   /* $bargs = array("price" => $item["price"], "brand_id" => $item["brand_id"]);
                    $bres = $this->api->ulr_update_brand($bargs);*/

                    // model update
                    $margs = array("brand_id" => $item["brand_id"]);
                    $mlist = explode(",", trim($item['model_name']));                 
                    $mids = explode(",",  $item['id']);

                    foreach ($mlist as $mkey => $mvalue) {
                        $margs['model_name'] = trim($mvalue);

                        if(array_key_exists($mkey, $mids)){
                            $margs['model_id'] = $mids[$mkey];                            
                        }else{unset($margs['model_id']);}

                        $result = $this->api->ulr_insert_model($margs);
                        if(!$result){                            
                            break;
                        }
                        $mids[$mkey] = $result;  
                    }
                    
                    $item['id'] = implode(",", $mids);
                    //$this->wpdb->update($table_name, $item, array('id' => $item['id']));
                    if ($result) {
                        $message = __('Item was successfully updated', 'cltd_example');
                    } else {
                        $notice = __('There was an error while updating item', 'cltd_example');
                    }
                }
            } else {
                // if $item_valid not true it contains error message(s)
                $notice = $item_valid;
            }
        }
        else { 
            // if this is not post back we load item to edit or give new one to create
            $item = $default;
            if (isset($_REQUEST['id'])) {
                $item =  $this->api->ulr_brand_model_row(array("id" => $_REQUEST['id']));
                if (!$item) {
                    $item = $default;
                    $notice = __('Item not found', 'cltd_example');
                }
            }
        }
        // here we adding our custom meta box
        add_meta_box('model_form_meta_box', 'Add/Edit model', array($this, 'ula_model_form_meta_box_handler'), 'model', 'normal', 'default');
        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php _e('Model', 'cltd_example')?> 
            <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=unlocker-admin-models');?>"><?php _e('back to list', 'cltd_example')?></a>
            </h2>

            <?php if (!empty($notice)): ?>
            <div id="notice" class="error"><p><?php echo $notice ?></p></div>
            <?php endif;?>
            <?php if (!empty($message)): ?>
            <div id="message" class="updated"><p><?php echo $message ?></p></div>
            <?php endif;?>

            <form id="form" method="POST">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
                <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
                <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

                <div class="metabox-holder" id="poststuff">
                    <div id="post-body">
                        <div id="post-body-content">
                            <?php /* And here we call our custom meta box */ ?>
                            <?php do_meta_boxes('model', 'normal', $item); ?>
                            <input type="submit" value="<?php _e('Save', 'cltd_example')?>" id="submit" class="button-primary" name="submit">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    /**
     * This function renders our custom meta box
     * $item is row
     *
     * @param $item
     */
    function ula_model_form_meta_box_handler($item)
    {   
        
        $groups = $this->api->ulr_brand_list();
        ?>

        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
            <tbody>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="model name"><?php _e('Model Name', 'cltd_example')?></label>
                </th>
                <td>
                    <textarea id="model_name" name="model_name" col="10" row="6" placeholder="<?php _e('Your model names like s10,s9,note8', 'cltd_example')?>" required><?php echo esc_attr($item['model_name'])?></textarea>
                    <br/><small>You can add multiple model names with comma (eg: s10,s9,note8)</small>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="brand"><?php _e('Brand', 'cltd_example')?></label>
                </th>
                <td>
                    <select id="brand_id" name="brand_id" style="width: 95%" required>
                        <option value="">-- Brand/ Groups --</option>
                        <?php  foreach ($groups as $brand) : ?>
                        <option value="<?=$brand["id"]?>" <?php if(esc_attr($item['brand_id']) == $brand["id"]){?> selected <?php }?>><?=$brand["brand_name"]?></option>
                        <?php endforeach; ?>
                    </select>                   
                </td>
            </tr>      
            </tbody>
        </table>
        <?php
    }
    /**
     * Simple function that validates data and retrieve bool on success
     * and error message(s) on error
     *
     * @param $item
     * @return bool|string
     */
    public function ula_validate_model($item){

        $messages = array();
        if (empty($item['model_name'])) $messages[] = __('Model Name is required', 'cltd_example');
        if (empty($item['brand_id'])) $messages[] = __('Brand is required', 'cltd_example');        
        
        if (empty($messages)) return true;
        return implode('<br />', $messages);
    }

    public function ula_model_page(){

        $table = new Unlocker_List_Table();
        $table->prepare_items();

        $message = '';        
        if ('delete' === $table->current_action()) {
            $count = is_array($_REQUEST['id'])? count($_REQUEST['id']): 1;
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'cltd_example'), $count) . '</p></div>';
        }

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php _e('Manage Models', 'cltd_example')?> 
                <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=unlocker-admin-new-model');?>"><?php _e('Add new', 'cltd_example')?></a>
            </h2>
            <?php echo $message; ?>
 
            <form id="persons-table" method="GET">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>                
                <?php 
                    $table->search_box( 'search', 'search_id' );
                    $table->display();
                ?>
            </form>

        </div>
    <?php
    }   

}    
?>