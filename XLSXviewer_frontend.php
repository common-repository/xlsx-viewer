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


    use Shuchkin\SimpleXLSX;
    require_once __DIR__.'/lib/SimpleXLSX/SimpleXLSX.php';

    /***************************************
     * Show XLSX table in post or page
    /**************************************/
    function show_table( $atts ) 
    {
        $file_path = XLSXviewer_UPLOAD_PATH . $atts['file_name'];

        global $wpdb;
        $XLSX_table_name = $wpdb->prefix . XLSXviewer_PLUGIN_NAME;

        global $wpdb;
        $XLSX_table_name = $wpdb->prefix . XLSXviewer_PLUGIN_NAME;
        $post_table_name = $wpdb->prefix . 'posts';

        $page_id = get_the_ID();

        $retrieve_data = "SELECT 
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
                post_id = $page_id
        ";

        $row = $wpdb->get_row( $retrieve_data );

        // show / hide table borders
        ( $row->border_show == 1 ) ? $border = 'border: 1px solid' : $border = 'border: none';

        // define border color if $border = 1
        ( $row->border_show == 1 && $row->border_color != '' ) ? $border_color = 'border-color: #' . $row->border_color : $border_color = 'border-color: none';

        // show / hide first row
        ( $row->first_row_show == 1 ) ? $first_row = '' : $first_row = 'display: none';

        // // define text color if $first_row is visible
        ( $row->first_row_show == 1 && $row->first_row_text_color != '' ) ? $first_row_text_color = 'color: #' . $row->first_row_text_color : $first_row_text_color = 'color: inherit';

        // // define background color if $first_row is visible
        ( $row->first_row_show == 1 && $row->first_row_background_color != '' ) ? $first_row_background_color = 'background-color: #' . $row->first_row_background_color : $first_row_background_color = 'background-color: none';

        // define whole table background color 
        $row->table_background_color =! '' ? $table_background_color = 'background-color: #' . $row->table_background_color : $table_background_color = 'background-color: none';

        ?>
            <style>
                #XLSXviewer_main_table table,
                #XLSXviewer_main_table table tr,
                #XLSXviewer_main_table table td {
                    <?php echo esc_html( $border ) ?>;
                    <?php echo esc_html( $border_color ) ?>;
                }

                #XLSXviewer_main_table table tr:first-of-type {
                    <?php echo esc_html( $first_row ) ?>;
                    <?php echo esc_html( $first_row_text_color ) ?>;
                    <?php echo esc_html( $first_row_background_color ) ?>;
                }

                #XLSXviewer_main_table table {
                    <?php echo esc_html( $table_background_color ) ?>;
                }
            </style>
        <?php

        // Load file
        if ( $xlsx = SimpleXLSX::parse( $file_path ) ) 
        {
            // Retrieve sheet number
            $num_sheets = count( $xlsx->sheetNames() );

            // Check if "sheet" is in the URL
            $sheet_index = isset( $_GET['sheet'] ) ? (int) $_GET['sheet'] : 0;

            // Check if the index requested is valid
            if ( $sheet_index < 0 || $sheet_index >= $num_sheets ) 
            {
                $sheet_index = 0;
            }

            // Retrieve active sheet name
            $sheet_name = $xlsx->sheetName( $sheet_index );

            // Retrieve all sheets name
            $all_sheet_names = $xlsx->sheetNames();

            // Print active sheet name
            // $echo .= "<div>{$sheet_name}</div>";

            // Retrieve data from active sheet
            $rows = $xlsx->rows( $sheet_index );

            $echo = '';

            // Create & render top navigation buttons

            if ( $xlsx = SimpleXLSX::parse( $file_path ) ) 
            {
                $top_nav = renderNavigationButtons( $all_sheet_names, $sheet_index, $num_sheets );
                $echo .= $top_nav;
            }

            // Print table for active sheet
            $echo .= '<div id="XLSXviewer_main_table">';
            $echo .= '<table>';

            foreach ( $rows as $row ) 
            {
                $echo .= '<tr>';

                foreach ( $row as $cell ) 
                {
                    $echo .= '<td>' . $cell . '</td>';
                }

                $echo .= '</tr>';
            }

            $echo .= '</table>';
            $echo .= '</div>';

        } else {
            // OMG! Something went wrong ...
            $echo = SimpleXLSX::parseError();
        }

        // Create & render bottom navigation buttons
        if ( $xlsx = SimpleXLSX::parse( $file_path ) ) 
        {
            $bottom_nav = renderNavigationButtons( $all_sheet_names, $sheet_index, $num_sheets );
            $echo .= $bottom_nav;
        }

        return wp_kses_post( $echo );
    }

	add_shortcode( XLSXviewer_PLUGIN_NAME,'show_table' );
    /***************************************
    /**************************************/


    /***************************************
     * Save data on saving post or page
    /**************************************/
    function save_XLSXdata( $post_id ) 
    {
        // Check it isn't an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        {
            return;
        }
    
        // Check if user has necessary permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) 
        {
            return;
        }

        // Save data in wp_XLSXviewer table
        global $wpdb;
        $XLSX_table_name = $wpdb->prefix . XLSXviewer_PLUGIN_NAME;
        $post_table_name = $wpdb->prefix . 'posts';

        $current_page = get_post( $post_id );
        $page_title = $current_page->post_title;

        $current_time = current_time( 'timestamp' );
        $formatted_time = date( 'Y-m-d H.i.s', $current_time );

        $file_name = get_shortcode_from_post( $post_id );

        $existing_record = "SELECT 
                WP_XLSX.post_id

            FROM 
                $XLSX_table_name AS WP_XLSX
                INNER JOIN wp_posts AS WP_P 
                ON WP_XLSX.post_id = WP_P.ID

            WHERE 
                post_id = $post_id AND 
                WP_P.post_parent = 0
        ";

        $row_existing_record = $wpdb->get_row( $existing_record );

        if ( !$row_existing_record )
        {
            // Insert new record
            $insert_data = array(
                'post_id' => $post_id,
                'date_saved' => $formatted_time,
                'file_name' => $file_name
            );

            $format = array(
                '%d',
                '%s',
                '%s',
                '%s'
            );

            $wpdb->insert( $XLSX_table_name, $insert_data, $format );
        }

        else if ( $row_existing_record ) 
        {
            // Update existing record
            $update_data = array(
                'post_id' => $post_id,
                'date_saved' => $formatted_time,
                'file_name' => $file_name
            );

            $where = array(
                'post_id' => $post_id
            );

            $wpdb->update( $XLSX_table_name, $update_data, $where );
        }
    }

    add_action( 'save_post', 'save_XLSXdata' );
    /***************************************
    /**************************************/


    /***************************************
     * Retrieve shortcode from table wp_posts
    /**************************************/
    function get_shortcode_from_post( $post_id ) 
    {
        $shortcode = '';
        $post_content = get_post_field( 'post_content', $post_id );
    
        if ( preg_match( '/\[XLSXviewer (.*)\]/i', $post_content, $matches ) ) 
        {
            $shortcode = $matches[1];

            $opening_double_quote = strpos( $shortcode, '"' ) + 1;
            $closing_double_quote = strpos( $shortcode, '"', $opening_double_quote) ;
            $substring = substr($shortcode, $opening_double_quote, $closing_double_quote - $opening_double_quote );
        }
    
        return $substring;
    }
    /***************************************
    /**************************************/


    /***************************************
     * Button navigation
    /**************************************/
    function renderNavigationButtons( $sheet_names, $sheetIndex, $count_sheets ) 
    {
        // Render navigation buttons if there is more than one sheet
        if( $count_sheets > 1 )
        {
            $echo = '';
            $echo .= '<div class="d-flex justify-content-center mb-3">';

            foreach ( $sheet_names as $sheetIndex => $sheetName ) 
            {
                $get_sheet = isset( $_GET['sheet'] ) ? sanitize_key( $_GET['sheet'] ) : 0;
                $class = ( $get_sheet == sanitize_key( $sheetIndex ) ) ? 'btn-dark' : 'btn-outline-secondary';
                $echo .= '<a href="?sheet=' . sanitize_key( $sheetIndex ) . '" class="m-1 btn rounded-0 ' . $class . '">' . esc_html( $sheetName ) . '</a>';
            }
    
            $echo .= '</div>';

            return $echo;
        }
    }
?>
