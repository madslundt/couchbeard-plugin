<?php
/*
  Plugin Name: Couch Beard APIs
  Plugin URI:
  Description: Manage API keys and login for applications
  Author: Mads Lundt
  Version: 0.1
  Author URI:
*/

/*  Copyright 2013  Mads Lundt  (email : madslundt@live.dk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class CouchBeardPlugin {

    const DOMAIN = 'couchbeard';

    public static $table_name;

    // For database create.
    public static $apis = array("CouchPotato", "SickBeard", "SABnzbd");
    public static $logins = array("XBMC");

    public function __construct() {
        global $status, $page;

        $this->load_dependencies();

        add_action('admin_menu', array(&$this,'add_menu_items'));
        add_action('admin_menu', array(&$this, 'loadStyle'));

        /**
         * After the plugins have loaded initalise a single instance of couchbeard
         */
        add_action( 'plugins_loaded', array( 'couchbeard_widget', 'get_instance' ) );
    }

    public function loadStyle()
    {
        wp_register_style('admin_style', plugins_url( 'css/admin_style.css' , __FILE__ ));
        wp_enqueue_style('admin_style');
    }
    
    public function add_menu_items() {
        global $submenu;
        add_menu_page(
            'Couchbeard',
            'CouchBeard',
            'activate_plugins',
            'CouchBeard',
            array(&$this,'render_couchbeard_page')
        );
    }

    public static function getAllApps() {
        return array_merge(self::$apis, self::$logins);
    }

    public function add_couchbeard_settings($settings) {
        $new_settings = array(
            array(
                /*Sections*/
                'name'      => 'CouchBeard',
                'title'     => __('Applications',self::DOMAIN),
                'fields'    => array()
            )
        );
        return array_merge($settings,$new_settings);
    }

    public function render_couchbeard_page() {
?>
        <div class="wrap">
            <div id="icon-users" class="icon32"><br/></div>
<?php
                $this->render_list_table(new Couchbeard_List_Table());
?>
        </div>
<?php
    }

    private function render_list_table($table) {
        $table->prepare_items();   
?>
    <h2><?php $table->get_title(); ?></h2>
    <form id="applicationedit" method="POST">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php $table->views(); ?>
        <?php $table->display(); ?>
        <input type="submit" class="button-primary sub" name="submitbutton">
    </form>
<?php
        return $table;
    }


    private function load_dependencies() {
        global $wpdb;

        if (!class_exists('WP_List_Table'))
            require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

        self::$table_name = $wpdb->prefix . 'apis';
        $wpdb->api = $wpdb->prefix . 'apis';
        if ($wpdb->get_var('SHOW TABLES LIKE \'' . $wpdb->api . '\'') != $wpdb->api)
        {
            $sql = "CREATE TABLE " . $wpdb->api . "(
                  ID INT(20) UNSIGNED NOT NULL AUTO_INCREMENT ,
                  name VARCHAR(45) NOT NULL UNIQUE ,
                  api VARCHAR(100) NULL ,
                  ip VARCHAR(100) NULL ,
                  username VARCHAR(45) NULL ,
                  password VARCHAR(45) NULL ,
                  login TINYINT(1) DEFAULT 0 NOT NULL ,
                  PRIMARY KEY (ID) )
                ENGINE = InnoDB;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            add_option('api_database_version', '1.0');

	        foreach (self::$apis as $a)
	        {
	            $wpdb->insert($wpdb->api, array('name' => $a));
		    }

	        foreach (self::$logins as $l)
	        {
	           	$wpdb->insert($wpdb->api, array('name' => $l, 'login' => 1));
	        
	        }
	    }

        require_once(__DIR__ . '/couchbeard-list-table.php');

        add_action( 'wp_enqueue_scripts', function() {
            wp_register_script( 'cb_custom_scripts', plugins_url('js/custom-scripts.js', __FILE__ ), array('jquery'), '1.0', true );
    		wp_enqueue_script( 'cb_custom_scripts' );
            wp_register_style('fancyinput_style', plugins_url( 'css/fancyinput.css', __FILE__ ));
            wp_register_script( 'fancyinput_script', plugins_url('/js/fancyinput.js', __FILE__ ), array('jquery'), '1.0', true );
            wp_deregister_script('jquery');
            wp_register_script('jquery', '//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js', false, '1.11.0', false);
        });

        if (!class_exists('couchbeard')) {
            require_once(__DIR__ . '/couchbeard.php');
        }
        if (!class_exists('couchpotato')) {
            require_once(__DIR__ . '/couchpotato.php');
        }
        if (!class_exists('xbmc')) {
            require_once(__DIR__ . '/xbmc.php');
        }
        if (!class_exists('sickbeard')) {
            require_once(__DIR__ . '/sickbeard.php');
        }
        if (!class_exists('sabnzbd')) {
            require_once(__DIR__ . '/sabnzbd.php');
        }

        if (!class_exists('imdbAPI'))
            require_once(__DIR__ . '/imdbAPI.php');

        // require_once(__DIR__ . '/ajax_calls.php');
    }

} //class

new CouchBeardPlugin();

if (isset($_POST['submitbutton'])) {

    $apps = $wpdb->get_results(
        "
        SELECT *
        FROM " . CouchBeardPlugin::$table_name
    );

    foreach ($apps as $app) {
        if (empty($app->login)) {
            if (strlen($_POST['api' . $app->ID]) > 2 && strlen($_POST['ip' . $app->ID]) > 2) {
                $wpdb->query($wpdb->prepare(
                    "
                    UPDATE " . CouchBeardPlugin::$table_name . "
                    SET api = %s, ip = %s
                    WHERE ID = %s
                    ", 
                    array(
                        $_POST['api' . $app->ID],
                        $_POST['ip' . $app->ID],
                        $app->ID
                    )
                ));
            }
        } else {
            if (strlen($_POST['user' . $app->ID]) > 2 && strlen($_POST['pass' . $app->ID]) > 2 && strlen($_POST['ip' . $app->ID]) > 2) {
                $wpdb->query($wpdb->prepare(
                    "
                    UPDATE " . CouchBeardPlugin::$table_name . "
                    SET ip = %s, username = %s, password = %s
                    WHERE ID = %s
                    ", 
                    array(
                        $_POST['ip' . $app->ID],
                        $_POST['user' . $app->ID],
                        $_POST['pass' . $app->ID],
                        $app->ID
                    )
                ));
            }
        }
    }
}


/**
 * couchbeard Class
 */
class couchbeard_widget extends WP_Widget {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @var     string
     */
    const VERSION = '0.1';

    /**
     * Instance of this class.
     *
     * @var      object
     */
    protected static $instance = null;
    
    protected static $apps = array();
    protected static $styles = array('default', 'dark', 'custom');

    const APP_DOMAIN = 'app_';

    /**
     * Initialize the plugin by registering widget and loading public scripts
     *
     */ 
    public function __construct() {
        // Register Widget On Widgets Init
        add_action( 'widgets_init', array( $this, 'register_widget' ) );
        
        self::$apps = CouchBeardPlugin::getAllApps();

        if (is_admin()) {
            add_action( 'wp_admin_enqueue_scripts', function() {
                wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-ui-draggable');
            } );
        }

        $widget_options = array(
            'classname'   => 'couchbeard',
            'description' => __( 'A widget that displays couchbeard functionality', 'couchbeard' )
        );      
        
        parent::__construct( 'couchbeard', __('Couchbeard', 'couchbeard'), $widget_options );
    }

    /**
     * Return an instance of this class.
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register widget on windgets init
     *
     * @return void
     */
    public function register_widget() {
        register_widget( __CLASS__ );
    }

    /*public function private_enqueue() {
        wp_localize_script( 'admin_functions', 'admin', array('imgpath' => plugins_url( '/img', __FILE__ )) );
        wp_enqueue_script('admin_functions', plugins_url( 'js/admin.js', __FILE__ ), array('jquery', 'jquery-ui-draggable'), '1.0', true);
    }*/
    
    /**
     * The Public view of the Widget  
     *
     * @return mixed
     */ 
    public function widget( $args, $instance ) {
        extract( $args );

        //Our variables from the widget settings.
        $title = isset ( $instance['title'] ) ? $instance['title'] : false;
        $search = isset( $instance['search'] ) ? $instance['search'] : true;
        $apps = isset( $instance['apps'] ) ? $instance['apps'] : self::$apps;
        
        // Creating variables for all apps
        /*foreach(self::$apps as $app) {
            $app = strtolower($app);
            extract(array('app_' . $app => (isset($instance['app_' . $app]) ? $instance['app_' . $app] : -1)));
        }*/

        $row_sm = isset( $instance['row_sm'] ) ? $instance['row_sm'] : 1;
        $row_md = isset( $instance['row_md'] ) ? $instance['row_md'] : 3;
        $row_lg = isset( $instance['row_lg'] ) ? $instance['row_lg'] : 3;
        $style = isset( $instance['style'] ) ? $instance['style'] : 'default';
        $loadBS = isset( $instance['loadbs'] ) ? $instance['loadbs'] : true;

        echo $before_widget;

        if ($title) {
            echo $before_title . $title . $after_title;
        }

        //include the template based on user choice
        $this->template($search, $apps, $row_sm, $row_md, $row_lg, $style, $loadBS );
        
        echo $after_widget;
    }

    /**
     * Update the widget settings 
     *
     * @param    array    $new_instance    New instance values
     * @param    array    $old_instance    Old instance values   
     *
     * @return array
     */
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        //Strip tags from title and name to remove HTML 
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['search'] = $new_instance['search'];
        $instance['apps'] = explode(', ', $new_instance['apps']);
        
        // Creating variables for all apps
        /*foreach(self::$apps as $app) {
            $app = strtolower($app);
            extract(array(self::APP_DOMAIN . $app => $new_instance[self::APP_DOMAIN . $app]));
        }*/

        $instance['row_sm'] = $new_instance['row_sm'];
        $instance['row_md'] = $new_instance['row_md'];
        $instance['row_lg'] = $new_instance['row_lg'];
        $instance['style'] = $new_instance['style'];
        $instance['loadbs'] = $new_instance['loadbs'];

        return $instance;
    }

    /**
     * Widget Settings Form
     *
     * @return mixed
     */ 
    public function form( $instance ) {
        //wp_localize_script( 'couchbeard_admin_functions', 'admin', array('imgpath' => plugins_url( '/img', __FILE__ ), 'apps_id' => $this->get_field_id('cb_apps')) );
        //wp_enqueue_script('couchbeard_admin_functions', plugins_url( 'js/admin.js', __FILE__ ), array('jquery', 'jquery-ui-draggable'), '1.0');
        
        //Set up some default widget settings.
        $defaults = array(
            'title'     => 'CouchBeard',
            'search'    => true,
            'apps'      => self::$apps,
            'row_sm'    => 3,
            'row_md'    => 3,
            'row_lg'    => 3,    
            'style'     => self::$styles,
            'loadbs'    => true
        );        /*foreach (self::$apps as $app) {
            $app = strtolower($app);
            $defaults[self::APP_DOMAIN . $app] = -1;
        }*/
        $instance = wp_parse_args( (array) $instance, $defaults );
        //$instance['apps'] = isset($instance['apps']) ? explode(', ', $instance['apps']) : array();
        $rows = count(self::$apps);
    ?>

        <style type="text/css">
            .couchbeard-widget .box {
                padding: 10px;
                border-radius: 3px;
                width: auto;
                background-color: #fff;
                display: inline-flex;
                margin: 0 5px;
                -moz-box-shadow: inset 0 0 5px #aaa;
                -webkit-box-shadow: inset 0 0 5px#aaa;
                box-shadow: inset 0 0 5px #aaa;
            }
            .couchbeard-widget .box:first-child {
                margin-left: 0;
            }
            .couchbeard-widget .box:last-child {
                margin-right: 0;
            }
            .couchbeard-widget .box img {
                width: 60px;
                height: 34px;
            }
            .couchbeard-widget .snaptarget {
                min-height: 40px;
                margin-top: 5px;
            }
            .couchbeard-widget select {
                width: 100%;
            }
            .couchbeard-widget ul li {
                display: inline-block;
            }
            .couchbeard-widget .app_check li {
                width: 32%;
            }
            .couchbeard-widget .none {
                display: none;
            }
        </style>
        <div class="couchbeard-widget" id="<?php echo $this->get_field_id('cb'); ?>">
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title', 'couchbeard'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
                <hr>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('search'); ?>"><?php _e('Search', 'couchbeard'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'search' ); ?>" name="<?php echo $this->get_field_name( 'search' ); ?>" type="checkbox" value="1" <?php checked( true, $instance['search']); ?> />
                <hr>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('apps'); ?>"><?php _e('Apps', 'couchbeard'); ?></label>
                <ul class="app_check">
                        <?php foreach (self::$apps as $app): ?>
                            <li><input data-app="<?php echo $app; ?>" value="<?php echo $app; ?>" type="checkbox"<?php echo (isset($instance['apps']) && in_array($app, $instance['apps']) ? ' checked' : ''); ?> /><?php echo $app; ?></li>
                        <?php endforeach; ?>
                </ul>
                <ul class="snaptarget">
                    <?php if (isset($instance['apps'])): ?>
                        <?php foreach ($instance['apps'] as $app):  ?>
                            <?php if (!empty($app)): ?>
                                <li data-app="<?php echo $app; ?>" title="<?php echo $app; ?>" class="draggable box <?php echo strtolower($app); ?>">
                                    <img src="<?php echo plugins_url( '/img/' . $app . '-min.png', __FILE__ ); ?>" alt="<?php echo $app; ?>" />
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <input type="hidden" value="<?php echo !empty($instance['apps']) ? implode(', ', $instance['apps']) : ''; ?>" name="<?php echo $this->get_field_name( 'apps' ); ?>" id="<?php echo $this->get_field_id( 'apps' ); ?>" />
                </ul>
                <hr>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('row'); ?>"><?php _e('Apps per row', 'couchbeard'); ?></label>
                <div>
                    <ul>
                        <li><?php _e('Small screens', 'couchbeard'); ?>
                        <select name="<?php echo $this->get_field_name( 'row_sm' ); ?>" id="<?php echo $this->get_field_id( 'row_sm' ); ?>">
                            <?php for ($i = 1; $i <= $rows; $i++): ?>
                                <option value="<?php echo $i; ?>"<?php echo ($i == $instance['row_sm'] ? ' selected="selected"' : ''); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        </li>
                        <li><?php _e('Medium screens', 'couchbeard'); ?>        
                        <select name="<?php echo $this->get_field_name( 'row_md' ); ?>" id="<?php echo $this->get_field_id( 'row_md' ); ?>">
                            <?php for ($i = 1; $i <= $rows; $i++): ?>
                                <option value="<?php echo $i; ?>"<?php echo ($i == $instance['row_md'] ? ' selected="selected"' : ''); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select> 
                        </li>
                        <li><?php _e('Large screens', 'couchbeard'); ?>                   
                        <select name="<?php echo $this->get_field_name( 'row_lg' ); ?>" id="<?php echo $this->get_field_id( 'row_lg' ); ?>">
                            <?php for ($i = 1; $i <= $rows; $i++): ?>
                                <option value="<?php echo $i; ?>"<?php echo ($i == $instance['row_lg'] ? ' selected="selected"' : ''); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        </li>
                    </ul>
                </div>
                <hr>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Design', 'couchbeard'); ?></label>
                <select name="<?php echo $this->get_field_name( 'style' ); ?>" id="<?php echo $this->get_field_id( 'style' ); ?>">
                    <?php foreach(self::$styles as $s): ?>
                        <option value="<?php echo $s; ?>"<?php echo ($s == $instance['style'] ? ' selected="selected"' : ''); ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
                <hr>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('loadbs'); ?>"><?php _e('Load Bootstrap', 'couchbeard'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'loadbs' ); ?>" name="<?php echo $this->get_field_name( 'loadbs' ); ?>" type="checkbox" value="1" <?php checked( true, $instance['loadbs']); ?> />
                <hr>
            </p>
            
            <p class="pressthis"><a target="_blank" title="Donate, It Feels Great" href="#"><span>Donate, It Feels Great!</span></a></p>    
            <script>
                $ = jQuery;
                var imgpath = "<?php echo plugins_url( '/img', __FILE__ ); ?>";
                var order = [<?php echo '"' . implode('", "', $instance['apps']) . '"'; ?>];
                $(function() {
                    $('#<?php echo $this->get_field_id("cb"); ?>').children('.snaptarget').sortable({
                        cursor: "move"
                    });
                    $('#<?php echo $this->get_field_id("cb"); ?>').children('.snaptarget').disableSelection();

                    $('#<?php echo $this->get_field_id("cb"); ?>').on('change', '.app_check input[type=checkbox]', function() {
                        if ($(this).is(":checked")) {
                            if ($(this).data('app')) {
                                order.push($(this).data('app'));
                                $('#<?php echo $this->get_field_id("cb"); ?>' + ' .snaptarget').append('<li data-app="' + $(this).data('app') + '" title="' + $(this).data('app') + '" class="draggable box ' + $(this).data('app').toLowerCase() + '">' +
                                                    '<img src="' + imgpath + '/' + $(this).data('app').toLowerCase() + '-min.png" alt="' + $(this).data('app') + '" />' +
                                                '</li>');
                            }
                        } else {
                            order.splice(order.indexOf($(this).data('app')), 1);
                            $('#<?php echo $this->get_field_id("cb"); ?>' + ' .snaptarget .' + $(this).data('app').toLowerCase()).remove();
                            
                        }
                        $('#<?php echo $this->get_field_id("cb"); ?>' + ' .snaptarget input[type=hidden]').val($.unique(order).join(', '));
                    });

                    $( '#<?php echo $this->get_field_id("cb"); ?>' + ' .snaptarget' ).on( 'sortstop', function( event, ui ) {
                        order = [];
                        $( '#<?php echo $this->get_field_id("cb"); ?>' + ' .snaptarget' ).children('li.draggable').each(function() {
                            order.push($(this).data('app'));
                        });
                        $(this).children('input[type=hidden]').val($.unique(order).join(', '));
                    });
                });
            </script>
        </div>    
        <?php
    }


    /**
     * Function to display Templates for widget
     *
     * @param    string    $template    The input to be sanitised
     * @param    array     $data_arr    The input to be sanitised
     * @param    string    $link_to     The input to be sanitised        
     *
     * @include file templates
     *
     * return void
     */
    private function template($search, $apps, $row_sm, $row_md, $row_lg, $style, $loadBS ) {
        if ($style != 'custom') {
            wp_register_style('style_' . $style, plugins_url( 'css/style_' . $style . '.css' , __FILE__ ));
            wp_enqueue_style('style_' . $style);
            wp_enqueue_style('fancyinput_style');
            wp_enqueue_script( 'fancyinput_script' );
            wp_register_script( 'cb_style_js', plugins_url('js/style_js.js', __FILE__ ), array('jquery', 'fancyinput'), '1.0', true );
            wp_enqueue_script( 'cb_style_js' );
        }
        if ($loadBS) {
            $bootstrap_scripts = array(
                'transition', //modal
                'alert',
                'button',
                //'carousel',
                'collapse', //search
                'dropdown', //menu
                'modal', 
                'scrollspy',
                //'tab',
                'tooltip',
                'popover'
                //'affix'
            );
            foreach($bootstrap_scripts as $bootscript) {
                wp_register_script( $bootscript, plugins_url('js/bootstrap/'.$bootscript.'.js', __FILE__ ), array('jquery'), '3.1.1', true );
                wp_enqueue_script( $bootscript );
            }
            wp_register_style('bs_style' . $style, plugins_url( 'css/bootstrap/bootstrap.css' , __FILE__ ));
            wp_enqueue_style('bs_style' . $style);
        }
        $search_file = plugin_dir_path( __FILE__ ) . 'views/search.php';
        if( file_exists( $search_file ) ){

            include $search_file;
    
        } else {
            printf(__('Error loading %s.', 'couchbeard'), $search_file);
        }
        $apps_name = array();
        foreach ($apps as $app) {
            if (empty($app)) {
                continue;
            }
            $apps_name[] = $app;
            $app = strtolower($app);
            $app_file = plugin_dir_path( __FILE__ ) . $app . '.php';
            /*if (file_exists($app_file)) {
                //include $app_file;
            } else {
                printf(__('Error loading %s.', 'couchbeard'), $app_file);
                continue;
            }*/

            wp_register_script( $app, plugins_url('js/' . $app . '.js', __FILE__ ), array('jquery'), '1.0', true );
            $translation_array = array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'loadmore' => __('Load more...', 'couchbeard')
                //'token' => wp_create_nonce(self::TOKEN_PREFIX),
            );
            wp_localize_script( $app, 'wp_' . $app, $translation_array );
            wp_enqueue_script( $app );

        }
        $apps_file = plugin_dir_path( __FILE__ ) . 'views/apps.php';
        if( file_exists( $apps_file ) ) {

            include $apps_file;
    
        } else {
            printf(__('Error loading %s.', 'couchbeard'), $apps_file);
        }
    }

} // end of widget

// SEARCH
function myprefix_autocomplete_init() {  
    // Register our jQuery UI style and our custom javascript file  
    wp_register_style('myprefix-jquery-ui','http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
    wp_register_script( 'cb_search', plugins_url('js/search.js', __FILE__ ), array('jquery', 'jquery-ui-autocomplete'), '1.0', true );
    wp_localize_script( 'cb_search', 'cb_search', array('url' => admin_url( 'admin-ajax.php' )));  
    // Function to fire whenever search form is displayed  
    wp_enqueue_script( 'cb_search' );  
    wp_enqueue_style( 'myprefix-jquery-ui' );
  
    // Functions to deal with the AJAX request - one for logged in users, the other for non-logged in users.  
    add_action( 'wp_ajax_myprefix_autocompletesearch', 'myprefix_autocomplete_suggestions' );
    add_action( 'wp_ajax_nopriv_myprefix_autocompletesearch', 'myprefix_autocomplete_suggestions' );  
}
//add_action( 'init', 'myprefix_autocomplete_init' );    

function myprefix_autocomplete_suggestions() {
    //$url = "http://www.omdbapi.com/?s=" . urlencode($_REQUEST['term']);
    //$url = "http://imdbapi.org/?q=" . $_REQUEST['term'] . "&episode=0&limit=10";
    $search = str_replace(array(" ", "(", ")"), array("_", "", ""), $_REQUEST['term']); //format search term
    $firstchar = substr($search,0,1); //get first character
    $url = "http://sg.media-imdb.com/suggests/${firstchar}/${search}.json"; //format IMDb suggest URL
    $imdb = curl_download($url);
    preg_match('/^imdb\$.*?\((.*?)\)$/ms', $imdb, $matches); //convert JSONP to JSON

    if(!$_SERVER["HTTP_X_REQUESTED_WITH"] || !$_GET['term']) {
        echo __('error', 'couchbeard') . __LINE__;
        exit();
    }

    $json = $matches[1];
    $arr = json_decode($json, true);

    $suggestions = array();  

    if(isset($arr['d'])) {
        foreach ($arr['d'] as $data) {
            if ($data['q'] == "feature" || $data['q'] == "TV series") {
                $suggestion = array();
                $img = preg_replace('/_V1_.*?.jpg/ms', "_V1._SY50.jpg", $data['i'][0]);
                $string = (strlen($data['l']) > 50) ? substr($data['l'], 0, 45).'...' : $data['l'];
                $searchpage = get_page_by_title( 'Search' );
                $suggestion['searchpageid'] = '#';
                $suggestion['imdbid'] = (string) $data['id'];
                $suggestion['label'] = $data['l'];
                $suggestion['title'] = $string;
                $suggestion['year'] = $data['y'];
                $suggestion['type'] = $data['q'];
                $suggestion['image'] = (empty($img)) ? IMAGES . '/no_cover.png' : $img;
                $suggestions[] = $suggestion;
            }
        }
    } else {
        $suggestion = array();
        $suggestion['imdbid'] = -1;
        $suggestion['title'] = __('No results', 'couchbeard');
        $suggestions[] = $suggestion;
    }

    echo $_GET["callback"] . "(" . json_encode($suggestions) . ")";
    exit();
}

/*function setFooter() {
?>
    <div class="span1 pull-right">
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_donations">
        <input type="hidden" name="business" value="madslundt@live.dk">
        <input type="hidden" name="lc" value="DK">
        <input type="hidden" name="item_name" value="CouchBeard">
        <input type="hidden" name="no_note" value="0">
        <input type="hidden" name="currency_code" value="EUR">
        <input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHostedGuest">
        <input type="image" src="https://www.paypalobjects.com/da_DK/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal – den sikre og nemme måde at betale på nettet.">
        <img alt="" border="0" src="https://www.paypalobjects.com/da_DK/i/scr/pixel.gif" width="1" height="1">
        </form>
    </div>
<?php
}
add_action('wp_footer', 'setFooter');*/
?>