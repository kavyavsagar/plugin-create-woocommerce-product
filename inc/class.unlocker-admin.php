<?php
require_once( UNL__PLUGIN_INC . 'class.unlocker-model.php' );
require_once( UNL__PLUGIN_INC . 'class.unlocker-group.php' );

class Unlocker_Admin{
    
    private $wpdb;
    private $api;
    private $md;
    private $grp;

    // Constructor
    function __construct() {
        global $wpdb;        
        $this->wpdb = $wpdb;

        $this->api = new Unlocker_Rest_Api();
        $this->md = new Unlocker_Model();
        $this->grp = new Unlocker_Group();

        add_action( 'admin_menu', array( $this, 'ula_plugin_setup_menu' ));

        add_action( 'admin_enqueue_scripts', array($this, 'load_custom_ula_admin_style') );

        add_action( 'admin_enqueue_scripts', array($this, 'load_custom_ula_admin_script')  );

        add_action( 'wp_ajax_api_request',  array($this, 'ula_ajax_process_request') );  

        //call register settings function
        add_action( 'admin_init', array($this, 'register_ula_plugin_settings') );

    }
    /* setup admin menu and pages */
    public function ula_plugin_setup_menu(){
        // hook
        add_menu_page( 'Unlocker Admin Settings', 'Unlock Tools', 'manage_options', 'unlocker-admin-plugin', array($this, 'ula_admin_tools_init') );

        add_submenu_page( 'unlocker-admin-plugin', 'Unlocker Admin Settings', 'Settings', 'manage_options', 'unlocker-admin-plugin', array($this,'ula_admin_tools_init'  ));

        add_submenu_page( 'unlocker-admin-plugin', 'Add New Model' , 'Add Models', 'manage_options', 'unlocker-admin-new-model', array($this->md, 'ula_model_form' ));

        add_submenu_page( 'unlocker-admin-plugin', 'Manage Models', 'Models', 'manage_options', 'unlocker-admin-models', array($this->md, 'ula_model_page' ));

        add_submenu_page( 'unlocker-admin-plugin', 'Manage Groups', 'Groups', 'manage_options', 'unlocker-admin-groups', array( $this->grp, 'ula_group_page' ));

        add_submenu_page( 'unlocker-admin-plugin', 'Add/Edit Groups ' , 'Add Group', 'manage_options', 'unlocker-admin-edit-brand', array($this->grp, 'ula_group_form' ));
    }

    
    function register_ula_plugin_settings() {
        //register our settings
         $options = array(
            'ula_api_root_url' ,
            'ula_api_user_name',
            'ula_api_password');

        foreach ($options as $opt) {
            register_setting( 'ula-plugin-settings-group', $opt );
        }
        
    }

    public function ula_admin_tools_init(){
    ?>
        <h1>General Settings</h1>

<!--        <div class="ula-info">
            <h4>Update Models</h4>
            <p>You can fetch the list of models & brands from API</p>
            <p id="alert-msg"></p>
            <button class="ula-req-btn">Get Models</button>
        </div>
        <hr/>-->
        <div class="wrap">
           <!-- <h1>Frontend Settings</h1> -->
            <form method="post" action="options.php"> 
                <?php settings_fields( 'ula-plugin-settings-group' ); ?>
                <?php do_settings_sections( 'ula-plugin-settings-group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                    <th scope="row">API Url</th>
                    <td><input type="text" name="ula_api_root_url" value="<?php echo esc_attr( get_option('ula_api_root_url') ); ?>" style="width: 50%;" /><br><small>Enter root path of licence api (eg: http://api.server.com:8080/)</small></td>
                    </tr>
                    
                    <tr valign="top">
                    <th scope="row">API Username</th>
                    <td><input type="text" name="ula_api_user_name" value="<?php echo esc_attr( get_option('ula_api_user_name') ); ?>" style="width: 50%;" /></td>
                    </tr>

                    <tr valign="top">
                    <th scope="row">API Password</th>
                    <td><input type="text" name="ula_api_password" value="<?php echo esc_attr( get_option('ula_api_password') ); ?>" style="width: 50%;" /></td>
                    </tr>

                </table>
                <?php submit_button(); ?>
            </form>
            </div>
        </div>

    <?php
    }
    
    function load_custom_ula_admin_script($hook) {

        if($hook != 'toplevel_page_unlocker-admin-plugin') {
            return;
        }

        wp_enqueue_script( 'custom_ula_admin_script',  UNL__PLUGIN_ASSET.'admin-script.js'  );

        // make the ajaxurl var available to the above script
        wp_localize_script( 'custom_ula_admin_script', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }

    function load_custom_ula_admin_style($hook) {
        // Load only on ?page=mypluginname
        if($hook != 'toplevel_page_unlocker-admin-plugin') {
                return;
        }  
        wp_enqueue_style( 'custom_ula_admin_css', UNL__PLUGIN_ASSET.'admin-style.css' );
    }
    /*
        Ajax Request
    */
    public function ula_ajax_process_request() {
        
        // first check if data is being sent and that it is the data we want
        if ( isset( $_POST["post_var"] ) && $_POST["post_var"]) {
            // now set our response var equal to that of the POST var (this will need to be sanitized based on what you're doing with with it)

            $response = $this->api->ulr_api_model_names();
            // send the response back to the front end
            echo $response;
            wp_die();
        }
    }

    /*
     * Actions perform on activation of plugin
     */
    public function ula_plugin_activation() {

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        $brand = $this->wpdb->prefix . 'mob_brand';
        $sql1 = "CREATE TABLE ".$brand." (
            id mediumint(9) NOT NULL AUTO_INCREMENT,            
            brand_name varchar(100) NOT NULL,
            api_name varchar(100) NOT NULL,
            price DECIMAL(15, 2) NULL,
            product_id mediumint(25)  NULL,
            status tinyint(1) NOT NULL,
            PRIMARY KEY  (id)
        );";        
        dbDelta( $sql1 );

        $model = $this->wpdb->prefix . 'mob_model';
        $sql2 = "CREATE TABLE ".$model." (
            id mediumint(9) NOT NULL AUTO_INCREMENT,            
            model_name varchar(100) NOT NULL,
            brand_id mediumint(9) NOT NULL,          
            status tinyint(1) NOT NULL,
            PRIMARY KEY  (id)
        );";        
        dbDelta( $sql2 );

        $payment = $this->wpdb->prefix . 'mob_payment';
        $sql3 = "CREATE TABLE ".$payment." (
            id mediumint(9) NOT NULL AUTO_INCREMENT,            
            item_number mediumint(9) NOT NULL,            
            user_email varchar(50) NOT NULL,
            txn_id varchar(100) NULL,
            amount DECIMAL(15, 2) NOT NULL,            
            status varchar(50) NOT NULL,     
            date_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP     
            PRIMARY KEY  (id)
        );";        
        dbDelta( $sql3 );
    }

    /*
     * Actions perform on de-activation of plugin
     */
    public function ula_plugin_deactivation() {
        
        // delete all products existed
        $this->api->ulr_delete_all_group();  
        
        // Remove tables
        $tableArray = array( $this->wpdb->prefix . 'mob_brand',
                             $this->wpdb->prefix . 'mob_model',
                             $this->wpdb->prefix . 'mob_payment'
                            );

        foreach ($tableArray as $tablename) {
            $this->wpdb->query("DROP TABLE IF EXISTS $tablename");
        }
        
        // delete plugin settings
        $options = array(
            'ula_api_root_url',
            'ula_api_user_name',
            'ula_api_password');

        foreach ($options as $opt) {
            delete_option( $opt );
        }
        
    }
}
?>