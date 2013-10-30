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
    }

    private function loadStyle()
    {
        wp_register_style('style', plugins_url( 'css/style.css' , __FILE__ ));
        wp_enqueue_style('style');
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

        if ($wpdb->get_var('SHOW TABLES LIKE ' . $this->table_name) != $this->table_name)
        {
            $sql = "CREATE TABLE " . $this->table_name . "(
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
        }

        foreach (self::$apis as $a)
        {
            $wpdb->insert($this->table_name, array('name' => $a));
        }

        foreach (self::$logins as $l)
        {
            $wpdb->insert($this->table_name, array('name' => $l, 'login' => 1));
        }

        require_once(__DIR__ . '/couchbeard-list-table.php');


        if (!class_exists('couchbeard'))
            require_once(__DIR__ . '/couchbeard.php');

        if (!class_exists('sabnzbd'))
            require_once(__DIR__ . '/sabnzbd.php');

        if (!class_exists('couchpotato'))
            require_once(__DIR__ . '/couchpotato.php');

        if (!class_exists('sickbeard'))
            require_once(__DIR__ . '/sickbeard.php');

        if (!class_exists('xbmc'))
            require_once(__DIR__ . '/xbmc.php');

        if (!class_exists('imdbAPI'))
            require_once(__DIR__ . '/imdbAPI.php');

        require_once(__DIR__ . '/ajax_calls.php');
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

function setFooter() {
?>
    %3Cdiv%20class%3D%22span1%20pull-right%22%3E%0A%20%20%20%20%20%20%20%20%3Cform%20action%3D%22https%3A%2F%2Fwww.paypal.com%2Fcgi-bin%2Fwebscr%22%20method%3D%22post%22%20target%3D%22_top%22%3E%0A%20%20%20%20%20%20%20%20%3Cinput%20type%3D%22hidden%22%20name%3D%22cmd%22%20value%3D%22_donations%22%3E%0A%20%20%20%20%20%20%20%20%3Cinput%20type%3D%22hidden%22%20name%3D%22business%22%20value%3D%22madslundt%40live.dk%22%3E%0A%20%20%20%20%20%20%20%20%3Cinput%20type%3D%22hidden%22%20name%3D%22lc%22%20value%3D%22DK%22%3E%0A%20%20%20%20%20%20%20%20%3Cinput%20type%3D%22hidden%22%20name%3D%22item_name%22%20value%3D%22CouchBeard%22%3E%0A%20%20%20%20%20%20%20%20%3Cinput%20type%3D%22hidden%22%20name%3D%22no_note%22%20value%3D%220%22%3E%0A%20%20%20%20%20%20%20%20%3Cinput%20type%3D%22hidden%22%20name%3D%22currency_code%22%20value%3D%22EUR%22%3E%0A%20%20%20%20%20%20%20%20%3Cinput%20type%3D%22hidden%22%20name%3D%22bn%22%20value%3D%22PP-DonationsBF%3Abtn_donate_LG.gif%3ANonHostedGuest%22%3E%0A%20%20%20%20%20%20%20%20%3Cinput%20type%3D%22image%22%20src%3D%22https%3A%2F%2Fwww.paypalobjects.com%2Fda_DK%2Fi%2Fbtn%2Fbtn_donate_LG.gif%22%20border%3D%220%22%20name%3D%22submit%22%20alt%3D%22PayPal%20%E2%80%93%20den%20sikre%20og%20nemme%20m%C3%A5de%20at%20betale%20p%C3%A5%20nettet.%22%3E%0A%20%20%20%20%20%20%20%20%3Cimg%20alt%3D%22%22%20border%3D%220%22%20src%3D%22https%3A%2F%2Fwww.paypalobjects.com%2Fda_DK%2Fi%2Fscr%2Fpixel.gif%22%20width%3D%221%22%20height%3D%221%22%3E%0A%20%20%20%20%20%20%20%20%3C%2Fform%3E%0A%20%20%20%20%3C%2Fdiv%3E
<?php
}
add_action('wp_footer', 'setFooter');
?>