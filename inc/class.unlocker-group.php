<?php
require_once( UNL__PLUGIN_INC . 'class.unlocker-group-table.php' );

class Unlocker_Group{
    
    private $wpdb;
    private $api;

    // Constructor
    function __construct() {
        global $wpdb;        
        $this->wpdb = $wpdb;

        $this->api = new Unlocker_Rest_Api();
    }

    public function ula_group_page(){

        $table = new Unlocker_Group_Table();
        $table->prepare_items();

        $message = '';        
        if ('delete' === $table->current_action()) {
            $count = is_array($_REQUEST['id'])? count($_REQUEST['id']): 1;
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'cltd_example'), $count) . '</p></div>';
        }else if ('product' === $table->current_action()) {
            $count = is_array($_REQUEST['id'])? count($_REQUEST['id']): 1;
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items add as products: %d', 'cltd_example'), $count) . '</p></div>';
        }

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php _e('Manage Groups', 'cltd_example')?> 
            <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=unlocker-admin-edit-brand');?>"><?php _e('Add new', 'cltd_example')?></a>
            </h2>
            <?php echo $message; ?>
 
            <form id="persons-table" method="GET">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>                
                <?php                    
                    $table->display();
                ?>
            </form>

        </div>
    <?php
    }   
    public function ula_group_form()
    {        
        //$table_name = $this->wpdb->prefix . 'mob_model'; // do not forget about tables prefix
        $message = '';
        $notice = '';
        // this is default $item which will be used for new records
        $default = array(
            'id' => 0,
            'brand_name' => '',            
            'price' => '0.00',
            'api_name'=> '',
            'status' => 1
        );
        // here we are verifying does this request is post back and have correct nonce
        if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
            // combine our default item with request params
            $item = shortcode_atts($default, $_REQUEST);
            // validate data, and if all ok save item to database
            // if id is zero insert otherwise update
            $item_valid = $this->ula_validate_group($item);
            if ($item_valid === true) {
                if ($item['id'] == 0) { // new data

                    $result = $this->api->ulr_insert_brand($item);
                    $item['id'] = $result;
                    
                    if ($result) {
                        $message = __('Item was successfully saved', 'cltd_example');
                    } else {
                        $notice = __('There was an error while saving item', 'cltd_example');
                    }
        
                } else { // update

                    // brand update
                    $result = $this->api->ulr_update_brand($item);
                    $item['id'] = $result;
                  
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
                $item =  $this->api->ulr_brand_row(array("id" => $_REQUEST['id']));
                if (!$item) {
                    $item = $default;
                    $notice = __('Item not found', 'cltd_example');
                }
            }
        }
        // here we adding our custom meta box
        add_meta_box('group_form_meta_box', 'Add/Edit group', array($this, 'ula_group_form_meta_box_handler'), 'group', 'normal', 'default');
        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php _e('Group', 'cltd_example')?> 
            <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=unlocker-admin-groups');?>"><?php _e('back to list', 'cltd_example')?></a>
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
                            <?php do_meta_boxes('group', 'normal', $item); ?>
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
    function ula_group_form_meta_box_handler($item)
    {   
        
        ?>

        <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
            <tbody>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="brand name"><?php _e('Brand Name', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="brand_name" name="brand_name" type="text" style="width: 95%" value="<?php echo esc_attr($item['brand_name'])?>" size="50" class="code" placeholder="<?php _e('Your brand name', 'cltd_example')?>" required>
                </td>
            </tr>
             <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="api call name"><?php _e('API Call Name', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="api_name" name="api_name" type="text" style="width: 95%" value="<?php echo esc_attr($item['api_name'])?>" size="50" class="code" placeholder="<?php _e('Your api call name', 'cltd_example')?>" required>
                </td>
            </tr>             
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="price"><?php _e('Price', 'cltd_example')?></label>
                </th>
                <td>
                    <input id="price" name="price" type="text" style="width: 95%" value="<?php echo esc_attr($item['price'])?>"
                           size="50" class="code" placeholder="<?php _e('Your price', 'cltd_example')?>" required>
                </td>
            </tr>
            <tr class="form-field">
                <th valign="top" scope="row">
                    <label for="is active"><?php _e('Is Active ?', 'cltd_example')?></label>
                </th>
                <td>
                    <input type="radio" name="status" value="<?php echo (esc_attr($item['status']) == 1)? 1: 1; ?>" <?php echo (esc_attr($item['status']) == 1)? 'checked': ''; ?>> Yes &nbsp;&nbsp;
                    <input type="radio" name="status" value="<?php echo (esc_attr($item['status']) == 0)? 0: 0;?>" <?php echo (esc_attr($item['status']) == 0)? 'checked': ''; ?>> No
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
    public function ula_validate_group($item){

        $messages = array();
        if (empty($item['brand_name'])) $messages[] = __('Model Name is required', 'cltd_example');        
        if (empty($item['price'])) $messages[] = __('Price is required', 'cltd_example');
        if (empty($item['api_name'])) $messages[] = __('Api Call Name is required', 'cltd_example');
        
        if (empty($messages)) return true;
        return implode('<br />', $messages);
    }
}
?>