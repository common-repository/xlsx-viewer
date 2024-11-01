<?php
    /*
        Plugin Name: XLSX Viewer
        Description: A WordPress plugin that allows users to upload XLSX files and display them on a website page. The plugin converts the uploaded XLSX file into an HTML table and if the file contains multiple sheets, navigation buttons are displayed to switch between sheets. Overall, this plugin provides an easy way for WordPress users to upload and display XLSX files on their website, without requiring any coding or technical knowledge.
        Version: 2.1.1
        Author: Vincenzo Tomai Pitinca
        Author URI: https://www.pitinca.it
        License: GPL2
    */

    /***************************************
     * If this file is called directly, exit
    /**************************************/
    if ( ! defined( 'ABSPATH' ) ) 
    {
        exit;
    }
    /***************************************
    /**************************************/


    /***************************************
     * Set constants
    /**************************************/
    defined( 'XLSXviewer_PLUGIN_NAME' ) or define( 'XLSXviewer_PLUGIN_NAME', 'XLSXviewer' );
    defined( 'XLSXviewer_PLUGIN_VERSION' ) or define( 'XLSXviewer_PLUGIN_VERSION', '2.1.1' );

    defined( 'XLSXviewer_BASE_NAME' ) or define( 'XLSXviewer_BASE_NAME', plugin_basename( __FILE__ ) );
    defined( 'XLSXviewer_ROOT_PATH' ) or define( 'XLSXviewer_ROOT_PATH', plugin_dir_path( __FILE__ ) );
    defined( 'XLSXviewer_ROOT_URL' ) or define( 'XLSXviewer_ROOT_URL', plugin_dir_url( __FILE__ ) );

    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['basedir'] . '/' . XLSXviewer_PLUGIN_NAME . '/';
    $upload_url = $upload_dir['baseurl'] . '/' . XLSXviewer_PLUGIN_NAME . '/';

    defined( 'XLSXviewer_UPLOAD_PATH' ) or define( 'XLSXviewer_UPLOAD_PATH', $upload_dir['basedir'] . '/' . XLSXviewer_PLUGIN_NAME . '/' );
    defined( 'XLSXviewer_UPLOAD_URL' ) or define( 'XLSXviewer_UPLOAD_URL', $upload_dir['baseurl'] . '/' . XLSXviewer_PLUGIN_NAME . '/' );

    /***************************************
     * Include component & styles (only if i'm in plugin pages)
    /**************************************/
    if (
        $pagenow === 'admin.php' && 
        isset($_GET['page']) && 
        (
            $_GET['page'] === XLSXviewer_PLUGIN_NAME || 
            $_GET['page'] === 'file_management' || 
            $_GET['page'] === 'file_upload'
        )
    ) {
        function XLSXviewer_load_resources() 
        {
            wp_enqueue_style( 'admin_style', plugins_url( '/css/XLSXviewer_admin_style.css', __FILE__ ) );
            wp_register_script('function', plugins_url('/js/XLSXviewer_function.js', __FILE__));
            wp_enqueue_script('function');

            wp_enqueue_style( 'font-awesome', plugins_url( '/lib/fontawesome/font-awesome.min.css', __FILE__ ) );

            wp_enqueue_style( 'bootstrap', plugins_url( '/lib/bootstrap/bootstrap.min.css', __FILE__ ) );
            wp_register_script('bootstrap', plugins_url('/lib/bootstrap/bootstrap.min.js', __FILE__));
            wp_enqueue_script('bootstrap');
        }

        add_action( 'admin_enqueue_scripts', 'XLSXviewer_load_resources' );
    }
    /***************************************
    /**************************************/


    /***************************************
     * Register plugin activation
    /**************************************/
    register_activation_hook( __FILE__, array( XLSXviewer_PLUGIN_NAME, 'XLSXviewer_activation' ) );
    /***************************************
    /**************************************/


    /***************************************
     * INSTALL PLUGIN
    /**************************************/
    class XLSXviewer 
    {
        function __construct() 
        {
            load_plugin_textdomain( XLSXviewer_PLUGIN_NAME, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
            add_filter( 'XLSXviewer_plugin_row_meta', array( $this, 'XLSXviewer_plugin_row_meta' ), 10, 2 );

            function XLSXviewer_settings_link( $links ) 
            {
                $XLSXviewer_settings_link = '<a href="' . admin_url( 'admin.php?page=' . XLSXviewer_PLUGIN_NAME ) . '">Settings</a>';
                array_unshift( $links, $XLSXviewer_settings_link );
                return $links;
            }

            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'XLSXviewer_settings_link' );
        }

        // Activation hook
        public static function XLSXviewer_activation() 
        {
            // PHP version compatibility check call
            if ( !XLSXviewer::XLSXviewer_php_version_check() ) 
            {
                // Deactivate the plugin
                deactivate_plugins( __FILE__ );
            } else {
                global $wpdb;

                $XLSX_table_name = $wpdb->prefix . XLSXviewer_PLUGIN_NAME;
                $charset_collate = $wpdb->get_charset_collate();

                // Verify if table already exixsts, if not create it
                if ( $wpdb->get_var("SHOW TABLES LIKE '$XLSX_table_name'" ) != $XLSX_table_name ) 
                {
                    // Create new table
                    $sql = "CREATE TABLE 
                        $XLSX_table_name (
                            post_id mediumint(9) NOT NULL AUTO_INCREMENT,
                            date_saved datetime DEFAULT current_timestamp(),
                            file_name varchar(255) DEFAULT NULL,
                            border_show int(1) DEFAULT NULL,
                            border_color varchar(6) DEFAULT NULL,
                            first_row_show int(1) DEFAULT NULL,
                            first_row_text_color varchar(6) DEFAULT NULL,
                            first_row_background_color varchar(6) DEFAULT NULL,
                            table_background_color varchar(6) DEFAULT NULL,
                            PRIMARY KEY (post_id)
                        ) $charset_collate
                    ";

                    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

                    dbDelta( $sql );
                }

                // Verify if directory already exixts, otherwise create it
                if ( ! is_dir( XLSXviewer_UPLOAD_PATH ) ) 
                {
                    wp_mkdir_p( XLSXviewer_UPLOAD_PATH );
                }
            }
        }// end method activation

        // PHP version compatibility check
        public static function XLSXviewer_php_version_check()
        {
            if ( version_compare( PHP_VERSION, '7.4.0', '<' ) ) 
            {
                return false;
            }

            return true;
            
        }// end method php_version_check

        // Plugin support and doc page url
        public function XLSXviewer_plugin_row_meta( $links, $file ) 
        {
            if ( strpos( $file, XLSXviewer_PLUGIN_NAME . '.php' ) !== false ) 
            {
                $info_links = array(
                    // 'support' => '<a href="" target="_blank">'.esc_html__('Support', 'XLSXviewer').'</a>',
                    // 'doc' => '<a href="#" target="_blank">'.esc_html__('Documentation', 'XLSXviewer').'</a>'
                );

                $links = array_merge( $links, $info_links );
            }

            return $links;
        }

    }// end Class XLSXviewer
    /***************************************
    /**************************************/


    /***************************************
     * DELETE TABLE ON PLUGIN UNINSTALL
    /**************************************/
    class XLSXviewerDeleteTable 
    {
        public static function on_XLSXviewer_uninstall() 
        {
            $self = new self();
            $self->delete_XLSXviewer_table();
        }

        public function delete_XLSXviewer_table() 
        {
            global $wpdb;
            $XLSX_table_name = $wpdb->prefix . XLSXviewer_PLUGIN_NAME;

            if ( $wpdb->get_var( "SHOW TABLES LIKE '$XLSX_table_name'" ) === $XLSX_table_name ) 
            {
                $wpdb->query( "DROP TABLE IF EXISTS $XLSX_table_name" );
            }
        }
    }
    
    // Register plugin to delete database
    register_uninstall_hook( __FILE__, array( 'XLSXviewerDeleteTable', 'on_XLSXviewer_uninstall' ) );
    /***************************************
    /**************************************/


    /***************************************
     * Load plugin
    /**************************************/
    function XLSXviewer_load_plugin() 
    {
        new XLSXviewer();
        include_once( 'XLSXviewer_frontend.php' );
        include_once( 'XLSXviewer_administration.php' );
    }

    add_action( 'plugins_loaded', 'XLSXviewer_load_plugin', 5 );
    /***************************************
    /**************************************/
?>