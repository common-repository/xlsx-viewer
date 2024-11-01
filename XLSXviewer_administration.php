<?php
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
     * Define header for all pages
    /**************************************/
    function XLSXviewer_header( $title_page ) 
    {
        $echo = '
            <h2 class="XLSXviewer_mainTitle pt-4">
                <span>XLSXviewer<sub>v.' . XLSXviewer_PLUGIN_VERSION . '</sub>
                </span>
            </h2>

            <div class="container">
                <h3 class="pageTitle pt-5">
                ' . $title_page . '
                </h3>
            </div>
        ';

        echo wp_kses_post( $echo );
    }
    /***************************************
    /**************************************/


    /***************************************
    * Generate random string
    ***************************************/
    function random_string( $num )
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random_string = substr( str_shuffle( $characters ), 0, $num );
        return esc_html( $random_string );
    }
    /***************************************
    ***************************************/


    /***************************************
    * TABLE SETTING:
    * Define administration main page
    ***************************************/
    function XLSXviewer_table_setting() 
    {
        XLSXviewer_header( 'TABLE SETTING' );
        load_table_setting();
    }
    /**************************************/


    /***************************************
    * Define WP_List_Table Class TABLE SETTING
    ***************************************/
    if ( ! class_exists( 'WP_List_Table' ) )
    {
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }
    
    class XLSXviewer_Table_Setting extends WP_List_Table 
    {
        function __construct() 
        {
            parent::__construct( array(
                'singular' => 'row', // Singular name of the row
                'plural' => 'rows', // Plural name of the row
                'ajax' => true // Does this table support ajax?
            ) );
        }

        protected function column_default( $item, $column_name ) 
        {
            if( $this->file_exists( $item['file_name'] ) )
            {
                switch( $column_name ) 
                { 
                    // case 'post_id': // not shown but useful
                    //     return $item['post_id'];
    
                    case 'post_title':
                        return $item['post_title'];
    
                    case 'file_name':
                        return $this->file_exists( $item['file_name'] );
    
                    case 'shortcode':
                        return $item['shortcode'];
    
                    case 'setup':
                        return $this-> setup_table( $item['post_id'], $item['file_name'] );
    
                    default:
                        return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes :)
                }
            }
        }

        public function get_columns()
        {
            $columns = array(
                // 'post_id' => 'Page ID', // not shown but useful
                'post_title' => 'Page name',
                'file_name' => 'File name',
                'shortcode' => 'Shortcode',
                'setup' => 'Table setup'
            );

            return $columns;
        }

        public function prepare_items() 
        {
            global $wpdb;
            $XLSX_table_name = $wpdb->prefix . XLSXviewer_PLUGIN_NAME;
            $post_table_name = $wpdb->prefix . 'posts';

            $dataContent = $wpdb->get_results( "SELECT 
                    WP_XLSX.post_id,
                    WP_XLSX.file_name,
                    WP_P.post_title

                FROM 
                    $XLSX_table_name AS WP_XLSX 
                    INNER JOIN $post_table_name AS WP_P 
                    ON WP_XLSX.post_id = WP_P.ID

                WHERE 
                    WP_P.post_parent = 0 AND  
                    WP_XLSX.file_name IS NOT NULL
            " );

            $data = array();
            foreach ( $dataContent as $result ) 
            {
                $data[] = array(
                    'post_id' => $result->post_id,
                    'post_title' => $result->post_title,
                    'file_name' => $result->file_name,
                    'shortcode' => '[' . XLSXviewer_PLUGIN_NAME . ' file_name="' . $result->file_name . '"]',
                );
            }

            $this->_column_headers = array( $this->get_columns(), array(), array() );
            $this->items = $data;
        }

        public function file_exists( $file_name )
        {
            if( $file_name != NULL )
            {
                $echo = sanitize_file_name( $file_name );
            } else {
                $echo = "";
            }

            if ( ! file_exists( XLSXviewer_UPLOAD_PATH . $file_name ) ) 
            {
                $echo .= '
                    <br>
                    <span class="badge rounded-pill text-bg-danger">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                        File doesn\'t exists, please <a href="' . admin_url( "admin.php?page=file_upload" ) . '">upload</a> to make shortcode works
                    </span>
                ';
            }

            return wp_kses_post( $echo );
        }

        public function setup_table( $post_id, $file_name )
        {
            global $wpdb;
            $XLSX_table_name = $wpdb->prefix . XLSXviewer_PLUGIN_NAME;

            $setup_values = "SELECT 
                    border_show,
                    border_color,
                    first_row_show,
                    first_row_text_color,
                    first_row_background_color,
                    table_background_color

                FROM 
                    $XLSX_table_name

                WHERE 
                    post_id = $post_id
            ";

            $row = $wpdb->get_row( $setup_values );

            $border_show = $row->border_show;

            $border_color = $row->border_color;

            $first_row_show = $row->first_row_show;

            $first_row_text_color = $row->first_row_text_color;
            $first_row_background_color = $row->first_row_background_color;
            $table_background_color = $row->table_background_color;

            $border_color_placeholder = ( $border_color != '')  ? $border_color : 'Border color (hex)';
            $border_color_picker = ( $border_color != '' ) ? 'background-color:#' . $border_color . ' !important; border-color:#8c8f94 !important' : '';

            $first_row_text_color_placeholder = ( $first_row_text_color != '' ) ? $first_row_text_color : 'Text color (hex)';
            $first_row_text_color_picker = ( $first_row_text_color != '' ) ? 'background-color:#' . $first_row_text_color . ' !important; border-color:#8c8f94 !important' : '';

            $first_row_background_color_placeholder = ( $first_row_background_color != '' ) ? $first_row_background_color : 'Background color (hex)';
            $first_row_background_color_picker = ( $first_row_background_color != '' ) ? 'background-color:#' . $first_row_background_color . ' !important; border-color:#8c8f94 !important' : '';

            $table_background_color_placeholder = ( $table_background_color != '' ) ? $table_background_color : 'Table background color (hex)';
            $table_background_color_picker = ( $table_background_color != '' ) ? 'background-color:#' . $table_background_color . ' !important; border-color:#8c8f94 !important' : '';

            ?>
                <div class="badge rounded-pill text-bg-secondary btn-sm w-100 accordion" title="Open table setting">
                    <i class="fa fa-arrow-down" aria-hidden="true"></i> Open table setting
                </div>

                <div class="panel mt-3">
                    <form id="setup_table_form">

                        <!-- Table borders -->
                        <div class="row g-0 mb-2">
                            <div class="col-12">

                                <div class="row g-0">
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text" id="inputGroup-sizing-sm">Border</span>
                                        </div>
                                    </div>

                                    <div class="col-6 text-end">
                                        <select id="border_show" name="border_show">
                                            <option value="1" <?php echo absint( $border_show ) == 1 ? "selected" : "" ?>>Show</option>
                                            <option value="0" <?php echo absint( $border_show ) == 0 ? "selected" : "" ?>>Hide</option>
                                        </select>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="row g-0 mb-2">
                            <div class="col-6 mb-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" id="inputGroup-sizing-sm">Border color</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="input-group input-group-sm">
                                    <input oninput="this.value = this.value.replace(/[^0-9A-Fa-f]/g, '').replace(/(\..*)\./g, '$1');" type="text" name="border_color" id="border_color" class="form-control" placeholder="<?php echo sanitize_hex_color_no_hash( $border_color_placeholder ) ?>" maxlength="6" title="Set here the color of table border">
                                    <span onclick="openColorPicker(this, 'border_color')" class="input-group-text border_color_picker" id="inputGroup-sizing-sm" style="<?php echo sanitize_text_field( $border_color_picker ) ?>">
                                        &nbsp;&nbsp;
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- Table borders -->

                        <!-- First row -->
                        <div class="row g-0 mb-2 mt-4">
                            <div class="col-12">

                                <div class="row g-0">
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text" id="inputGroup-sizing-sm">1st row</span>
                                        </div>
                                    </div>

                                    <div class="col-6 text-end">
                                        <select id="first_row_show" name="first_row_show">
                                            <option value="1" <?php echo absint( $first_row_show == 1 ) ? "selected" : "" ?>>Show</option>
                                            <option value="0" <?php echo absint( $first_row_show == 0 ) ? "selected" : "" ?>>Hide</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-0 mb-2">

                            <div class="col-6 mb-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" id="inputGroup-sizing-sm">1st row text color</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="input-group input-group-sm">
                                    <input oninput="this.value = this.value.replace(/[^0-9A-Fa-f]/g, '').replace(/(\..*)\./g, '$1');" type="text" name="first_row_text_color" id="first_row_text_color" class="form-control" placeholder="<?php echo sanitize_hex_color_no_hash( $first_row_text_color_placeholder ) ?>" maxlength="6">
                                    <span onclick="openColorPicker(this, 'first_row_text_color')" class="input-group-text border_color_picker" id="inputGroup-sizing-sm" style="<?php echo sanitize_text_field( $first_row_text_color_picker ) ?>">
                                        &nbsp;&nbsp;
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-0 mb-2">

                            <div class="col-6 mb-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" id="inputGroup-sizing-sm">1st row background color</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="input-group input-group-sm">
                                    <input oninput="this.value = this.value.replace(/[^0-9A-Fa-f]/g, '').replace(/(\..*)\./g, '$1');" type="text" name="first_row_background_color" id="first_row_background_color" class="form-control" placeholder="<?php echo sanitize_hex_color_no_hash( $first_row_background_color_placeholder ) ?>" maxlength="6">
                                    <span onclick="openColorPicker(this, 'first_row_background_color')" class="input-group-text border_color_picker" id="inputGroup-sizing-sm" style="<?php echo sanitize_text_field( $first_row_background_color_picker ) ?>">
                                        &nbsp;&nbsp;
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- First row -->

                        <!-- Table background color -->
                        <div class="row g-0 mb-2 mt-4">
                            <div class="col-11 mb-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" id="inputGroup-sizing-sm">Table background color</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="input-group input-group-sm">
                                    <input oninput="this.value = this.value.replace(/[^0-9A-Fa-f]/g, '').replace(/(\..*)\./g, '$1');" type="text" name="table_background_color" id="table_background_color" class="form-control" placeholder="<?php echo sanitize_hex_color_no_hash( $table_background_color_placeholder ) ?>" maxlength="6">
                                    <span onclick="openColorPicker(this, 'table_background_color')" class="input-group-text border_color_picker" id="inputGroup-sizing-sm" style="<?php echo sanitize_text_field( $table_background_color_picker ) ?>">
                                        &nbsp;&nbsp;
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- Table background color -->

                        <div class="row g-0 mt-3">
                            <div class="col-12 text-end">
                                <input type="hidden" name="post_id" id="post_id" value="<?php echo absint( $post_id ) ?>">
                                <a href="javascript:save_table_setup_settings();" id="setup-save" class="btn btn-success setup-save" title="Save table setting">
                                    <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>            
            <?php
        }

        public function display_content() 
        {
            $this->display();
        }
    }
    /**************************************/


    /***************************************
    * Save data on setting update
    ***************************************/
    function save_table_settings_callback() 
    {
        global $wpdb;
        $XLSX_table_name = $wpdb->prefix . XLSXviewer_PLUGIN_NAME;
        $post_table_name = $wpdb->prefix . 'posts';

        $post_id = absint( $_POST['post_id'] );

        $setup_values = "SELECT 
                WP_XLSX.border_show,
                WP_XLSX.border_color,
                WP_XLSX.first_row_show,
                WP_XLSX.first_row_text_color,
                WP_XLSX.first_row_background_color,
                WP_XLSX.table_background_color

            FROM 
                $XLSX_table_name AS WP_XLSX
                INNER JOIN $post_table_name AS WP_P 
                ON WP_XLSX.post_id = WP_P.ID

            WHERE 
                post_id = $post_id
        ";

        $row = $wpdb->get_row( $setup_values );

        // sanitize variables for $_POST data
        $sanitized_border_show = absint( $_POST['border_show'] );
        $sanitized_first_row_show = absint( $_POST['first_row_show'] );
        $sanitized_border_color = sanitize_hex_color_no_hash( $_POST['border_color'] );
        $sanitized_first_row_text_color = sanitize_hex_color_no_hash( $_POST['first_row_text_color'] );
        $sanitized_first_row_background_color = sanitize_hex_color_no_hash( $_POST['first_row_background_color'] );
        $sanitized_table_background_color = sanitize_hex_color_no_hash( $_POST['table_background_color'] );

        // Retrieve sent data or use stored values
        $border_show = isset( $sanitized_border_show ) ? $sanitized_border_show : $row->border_show;
        $border_color = ( ! empty( $sanitized_border_color ) ) ? $sanitized_border_color : $row->border_color;
        $first_row_show = isset( $sanitized_first_row_show ) ? $sanitized_first_row_show : $row->first_row_show;
        $first_row_text_color = ( ! empty( $sanitized_first_row_text_color ) ) ? $sanitized_first_row_text_color : $row->first_row_text_color;
        $first_row_background_color = ( ! empty($sanitized_first_row_background_color ) ) ? $sanitized_first_row_background_color : $row->first_row_background_color;
        $table_background_color = ( ! empty( $sanitized_table_background_color ) ) ? $sanitized_table_background_color : $row->table_background_color;

        // Table update
        $update_data = array(
            'border_show' => $border_show,
            'border_color' => $border_color,
            'first_row_show' => $first_row_show,
            'first_row_text_color' => $first_row_text_color,
            'first_row_background_color' => $first_row_background_color,
            'table_background_color' => $table_background_color
        );

        $where = array(
            'post_id' => $post_id
        );

        $wpdb->update( $XLSX_table_name, $update_data, $where );

        $echo = "Successfully updated";
        echo wp_kses_post( $echo );
        wp_die();
    }

    add_action( 'wp_ajax_save_settings', 'save_table_settings_callback' );
    /**************************************/


    /***************************************
    * Call Class for TABLE SETTING
    ***************************************/
    function load_table_setting() 
    {
        if ( current_user_can( 'administrator' ) ) 
        {
            echo '
                <div class="container">
            ';

                    $list_table = new XLSXviewer_Table_Setting();
                    $list_table->prepare_items();
                    $list_table->display_content();
            
            echo '
                </div>
            ';
        } else {
            return 'Not enough rights to continue here ...';
        }
    }
    /***************************************
    ***************************************/


    /***************************************
    * FILE MANAGEMENT:
    * Define administration file management
    ***************************************/
    function XLSXviewer_file_management() 
    {
        XLSXviewer_header( 'FILE MANAGEMENT' );
        load_file_management();
    }
    /**************************************/


    /***************************************
    * Define WP_List_Table Class for administration file management
    ***************************************/
    class XLSXviewer_File_Management extends WP_List_Table 
    {
        function __construct() 
        {
            parent::__construct( array(
                'singular' => 'row', // Singular name of the row
                'plural' => 'rows', // Plural name of the row
                'ajax' => true // Does this table support ajax?
            ) );
        }

        protected function column_default( $item, $column_name ) 
        {
            switch( $column_name ) 
            { 
                case 'file_name':
                    return $item['file_name'];

                case 'shortcode':
                    return $item['shortcode'];

                case 'actions':
                    return $this-> delete_file( $item['file_name'] );

                default:
                    // return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes :)
            }
        }

        public function get_columns()
        {
            $columns = array(
                'file_name' => 'File name',
                'shortcode' => 'Shortcode',
                'actions' => 'Actions'
            );

            return $columns;
        }

        public function prepare_items() 
        {
            $dir_path = XLSXviewer_UPLOAD_PATH;
            $files = scandir( $dir_path );
            $data = array();
        
            foreach( $files as $file_name ) 
            {
                if ( pathinfo( $file_name, PATHINFO_EXTENSION ) == "xlsx" ) 
                {
                    $data[] = array(
                        'file_name' => $file_name,
                        'shortcode' => '[' . XLSXviewer_PLUGIN_NAME . ' file_name="' . $file_name . '"]',
                        'actions' => ''
                    );
                }
            }

            $this->_column_headers = array( $this->get_columns(), array(), array() );
            $this->items = $data;
        }

        public function delete_file( $file_to_delete )
        {
            $rnd = random_string( 5 );

            ?>
                <div class="row g-0 my-2">
                    <div class="col-12">
                        <form id="delete_file_form">
                            <input type="hidden" name="delete_file_<?php echo esc_html( $rnd ) ?>" id="delete_file_<?php echo esc_html( $rnd ) ?>" value="<?php echo esc_html( $file_to_delete ) ?>">

                            <a href="<?php echo XLSXviewer_UPLOAD_URL . esc_html( $file_to_delete ) ?>" id="save_file" class="btn btn-outline-success btn-sm" download title="Download <?php echo esc_html( $file_to_delete ) ?>">
                                <i class="fa fa-download" aria-hidden="true"></i>
                            </a> 

                            <a href="javascript:delete_file('<?php echo esc_html( $rnd ) ?>');" id="delete_file" class="btn btn-outline-danger btn-sm w-auto" title="Download <?php echo esc_html( $file_to_delete ) ?>">
                                <i class="fa fa-trash" aria-hidden="true"></i>
                            </a>
                        </form>
                    </div>
                </div>
            <?php
        }

        public function display_content() 
        {
            $this->display();
        }
    }
    /**************************************/


    /***************************************
    * Delete file
    ***************************************/
    function delete_file_callback() 
    {
        $filename = $_POST['filename'];

        $directory = XLSXviewer_UPLOAD_PATH;
        $files = scandir( $directory );

        foreach( $files as $file ) 
        {
            if( $file == $filename ) 
            {
                $file_path = $directory . $file;
                unlink( $file_path );
                break;
            }
        }
    }

    add_action( 'wp_ajax_delete_file', 'delete_file_callback' );
    /**************************************/


    /***************************************
    * Call Class for FILE MANAGEMENT
    ***************************************/
    function load_file_management() 
    {
        if ( current_user_can( 'administrator' ) ) 
        {
            echo '
                <div class="container">
            ';

                    $manage_table = new XLSXviewer_File_Management();
                    $manage_table->prepare_items();
                    $manage_table->display_content();

            echo '
                </div>
            ';
        } else {
            return 'Not enough rights to continue here ...';
        }
    }
    /***************************************
    ***************************************/


    /***************************************
    * FILE UPLOAD 
    * Define administration upload page
    ***************************************/
    function XLSXviewer_file_upload() 
    {
        XLSXviewer_header( 'FILE UPLOAD' );
        load_file_upload();
    }
    /***************************************
    ***************************************/


    /***************************************
    * File upload 
    ***************************************/
    function XLSXviewer_upload_dir( $dirs ) 
    {
        $dirs['subdir'] = '/' . XLSXviewer_PLUGIN_NAME;
        $dirs['path'] = $dirs['basedir'] . '/' . XLSXviewer_PLUGIN_NAME;
        $dirs['url'] = $dirs['baseurl'] . '/' . XLSXviewer_PLUGIN_NAME;
    
        return $dirs;
    }

    function load_file_upload() 
    {
        ?>
            <div class="container alert alert-secondary mt-5" role="alert">
                <div class="container mt-2 mb-3">
                    <span class="badge text-bg-warning float-end rounded-1">Only .xlsx file are accepted</span>
                </div>

                <form method="post" enctype="multipart/form-data">
                    <div class="btn btn-dark mb-0 rounded-1" id="uploadTrigger">Browse &hellip;</div>
                    <div class="pt-2" id="fileNames"></div>
                    <input type="file" class="hidden" id="uploaded_files" name="uploaded_files[]" multiple="true">

                    <hr>

                    <div class="text-end">
                        <input type="submit" value="Upload Files" name="uploadFile" id="uploadFile" class="btn btn-dark mb-0 rounded-1">
                    </div>
                </form>
            </div>
        <?php

        if ($_SERVER["REQUEST_METHOD"] == "POST") 
        {
            $custom_upload_path = XLSXviewer_UPLOAD_PATH;

            if (!is_dir($custom_upload_path)) 
            {
                wp_mkdir_p($custom_upload_path);
            }
    
            if (!is_writable($custom_upload_path)) 
            {
                wp_die(esc_html('Folder doesn\'t exist or you don\'t have permission to write in it'));
            }
    
            if (!isset($_FILES['uploaded_files'])) 
            {
                echo 'No file uploaded';
            } else {
                $uploaded_files = $_FILES['uploaded_files'];

                foreach ($uploaded_files['name'] as $key => $filename) 
                {
                    $file_type = sanitize_text_field( $_FILES['uploaded_files']['type'][$key] );
                    $file_name = sanitize_text_field( $uploaded_files['name'][$key] );

                    if ( $file_type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ) 
                    {
                        add_filter( 'upload_dir', 'XLSXviewer_upload_dir' );

                        $file_data = array(
                            'name' => $uploaded_files['name'][$key],
                            'type' => $uploaded_files['type'][$key],
                            'tmp_name' => $uploaded_files['tmp_name'][$key],
                            'error' => $uploaded_files['error'][$key],
                            'size' => $uploaded_files['size'][$key]
                        );
        
                        $upload_overrides = array( 'test_form' => false );
                        $move_result = wp_handle_upload($file_data, $upload_overrides);
        
                        if ($move_result && !isset($move_result['error'])) 
                        {
                            $message = 'Upload complete for the following file: ';
                            $showFileName = esc_html( $file_name );
                            $alertType = 'success';
                        } else {
                            $message = 'Upload failed for the following file: ';
                            $showFileName = esc_html( $file_name );
                            $alertType = 'danger';
                        }
    
                        remove_filter( 'upload_dir', 'XLSXviewer_upload_dir' );
                    } else {
                        $message = 'Upload rejected for the following file: ';
                        $showFileName = esc_html( $file_name ) . ' is not a valid .xlsx file';
                        $alertType = 'danger';
                    }

                    if ( $file_type === '' ) 
                    {
                        $message = 'No file uploaded';
                        $showFileName = 'Please select .xlsx file(s) before uploading';
                        $alertType = 'warning';
                    }
                    ?>
                        <div class="container alert alert-<?php echo esc_attr( $alertType ); ?>" role="alert">
                            <h6 class="alert-heading fw-bold"><?php echo esc_html( $message ); ?></h6>
                            <hr>
                            <p class="mb-0"><?php echo esc_html( $showFileName ); ?></p>
                        </div>
                    <?php
                }
            }
        } else {
            return '
                <div class="container alert alert-<?php echo esc_attr( $alertType ) ?>" role="alert">
                    <h6 class="alert-heading fw-bold">Not enough rights to continue here ...</h6>
                </div>
            ';
        }
    }
    /***************************************
    ***************************************/


    /***************************************
    * Add config_menu on sidebar
    ***************************************/
    function XLSXviewer_config_menu() 
    {
        add_menu_page(
            XLSXviewer_PLUGIN_NAME . " - Table Setting", // menu title
            XLSXviewer_PLUGIN_NAME, // menu text
            'manage_options',  // access page policy
            XLSXviewer_PLUGIN_NAME, // page slug
            'XLSXviewer_table_setting', // callback that calls the page
            XLSXviewer_ROOT_URL . '/img/XLSXviewer_icon.png' // icon on sidebar
        );

        add_submenu_page(
            XLSXviewer_PLUGIN_NAME, // main menu slug
            XLSXviewer_PLUGIN_NAME . ' - Table Setting', // submenu title
            'Table Setting', // submenu text
            'manage_options',  // access page policy
            XLSXviewer_PLUGIN_NAME, // submenu page slug
            'XLSXviewer_table_setting' // callback that calls the page
        );

        add_submenu_page(
            XLSXviewer_PLUGIN_NAME, // main menu slug
            XLSXviewer_PLUGIN_NAME . ' - File Management', // submenu title
            'File Management', // submenu text
            'manage_options',  // access page policy
            'file_management', // submenu page slug
            'XLSXviewer_file_management' // callback that calls the page
        );

        add_submenu_page(
            XLSXviewer_PLUGIN_NAME, // main menu slug
            XLSXviewer_PLUGIN_NAME . ' - File Upload', // submenu title
            'File Upload', // submenu text
            'manage_options',  // access page policy
            'file_upload', // submenu page slug
            'XLSXviewer_file_upload' // callback that calls the page
        );
    }

    add_action( 'admin_menu', 'XLSXviewer_config_menu' );
    add_action( 'wp_ajax_aggiungi_dati', 'save_setup' );
    /***************************************
    ***************************************/
?>