<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.fedegomez.es
 * @since      1.0.0
 *
 * @package    Send_To_Archive_Org
 * @subpackage Send_To_Archive_Org/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Send_To_Archive_Org
 * @subpackage Send_To_Archive_Org/admin
 * @author     Fede Gómez <hola@fedegomez.es>
 */
class Send_To_Archive_Org_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */

    const ICON_NOT_ARCHIVED = '<span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>';
    const ICON_ARCHIVED = '<span class="dashicons dashicons-yes" style="color: #46b450;"></span>';
    const ICON_WARNING = '<span class="dashicons dashicons-warning" style="color: #f7b217;"></span>';
    const ICON_INFO = '<span class="dashicons dashicons-info" style="color: #0073aa;"></span>';
    
    public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

        $current_screen = get_current_screen();
        $post_type = $current_screen->post_type;
        $selected_post_types = get_option('send_to_archive_org_options')['post_types'] ?? array();
        $show_archive_column = get_option('send_to_archive_org_options')['show_archive_column'] ?? false;

        if ( in_array($post_type, $selected_post_types) || $current_screen->id === 'settings_page_send_to_archive_org' ) {
            wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/send-to-archive-org-admin.js', array( 'jquery' ), $this->version, false );
            wp_localize_script( $this->plugin_name, 'send_to_archive_org', array(
                'success_message' => __('Request successfully submitted', 'send-to-archive-org'),
                'nonce' => wp_create_nonce('security'),
            ) );
        }
	}

    public function add_script_as_module( $tag, $handle, $src ) {
        if ( $this->plugin_name === $handle ) {
            $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
        }

        return $tag;
    }

    public function add_features_to_cpt() {
        $custom_post_types = get_post_types(array('_builtin' => false), 'names');

        foreach ($custom_post_types as $post_type) {
            add_filter("{$post_type}_row_actions", array($this, 'add_row_action'), 10, 2);
            add_filter("bulk_actions-edit-{$post_type}", array($this, 'send_to_archive_org_bulk_action'));
            add_filter("handle_bulk_actions-edit-{$post_type}", array($this, 'handle_bulk_actions'), 10, 3);

            $post_type_object = get_post_type_object($post_type);

            if ($post_type_object->capability_type != 'post' && $post_type_object->capability_type != 'page') {
                add_filter("manage_{$post_type}_posts_columns", array($this, 'add_archive_org_column'));
                add_action("manage_{$post_type}_posts_custom_column", array($this, 'add_archive_org_column_content'), 10, 2);
            }
        }
    }


    public function register_plugin_settings() {
        register_setting( 'send_to_archive_org', 'send_to_archive_org_options' );

        add_settings_section(
            'send_to_archive_org_settings_section',
            '',
            array( $this, 'settings_section_callback' ),
            'send_to_archive_org'
        );

        add_settings_field(
            'send_to_archive_org_post_types',
            __('Select post types' , 'send-to-archive-org' ),
            array($this, 'post_types_field_callback'),
            'send_to_archive_org',
            'send_to_archive_org_settings_section'
        );

        add_settings_field(
            'send_to_archive_org_show_archive_column',
            __( 'Show Archive.org info', 'send-to-archive-org' ),
            array($this, 'show_archive_column_field_callback'),
            'send_to_archive_org',
            'send_to_archive_org_settings_section'
        );

        add_settings_field(
            'report_result',
            __( 'Report the result' , 'send-to-archive-org' ),
            array($this, 'report_result_field_callback'),
            'send_to_archive_org',
            'send_to_archive_org_settings_section'
        );

        add_settings_field(
            'send_results_to',
            __( 'Send results to', 'send-to-archive-org' ),
            array($this, 'send_results_to_field_callback'),
            'send_to_archive_org',
            'send_to_archive_org_settings_section'
        );
    }

    public function settings_section_callback() {
        // empty callback
    }

    public function post_types_field_callback() {
        $options = get_option('send_to_archive_org_options');
        $post_types = $this->get_filtered_post_types();

        echo '<div style="max-width: 400px; display: grid; grid-template-columns: repeat(2, 1fr); grid-gap: 10px;">';
        foreach ($post_types as $post_type) {
            $checked = in_array($post_type->name, $options['post_types'] ?? array()) ? 'checked' : '';
            echo '<div>';
            echo '<input type="checkbox" id="post_types_' . esc_attr($post_type->name) . '" name="send_to_archive_org_options[post_types][]" value="' . esc_attr($post_type->name) . '" ' . esc_attr($checked) . '>';
            echo '<label for="post_types_' . esc_attr($post_type->name) . '">' . esc_html($post_type->labels->name) . '</label>';
            echo '</div>';
        }
        echo '</div>';
    }

    public function show_archive_column_field_callback() {
        $options = get_option('send_to_archive_org_options');
        $checked = isset($options['show_archive_column']) && $options['show_archive_column'] ? 'checked' : '';

        echo '<input type="checkbox" id="show_archive_column" name="send_to_archive_org_options[show_archive_column]" value="1" ' . esc_attr($checked) . '>';
        echo '<label for="show_archive_column">' .esc_html(__('Show column with the date of the last save in Archive.org', 'send-to-archive-org' )) .'</label>';
    }

    public function report_result_field_callback() {
        $options = get_option('send_to_archive_org_options');
        $report_result = $options['report_result'] ?? '';

        echo '<select id="report_result" name="send_to_archive_org_options[report_result]">';
        echo '<option value="yes"' . selected($report_result, 'yes', false) . '>'. esc_html(__ ('Yes', 'send-to-archive-org' )) .'</option>';
        echo '<option value="no"' . selected($report_result, 'no', false) . '>'. esc_html(__ ('No', 'send-to-archive-org' )) .'</option>';
        echo '</select>';
    }

    public function send_results_to_field_callback() {
        $options = get_option('send_to_archive_org_options');
        $send_to = $options['send_results_to'] ?? get_bloginfo('admin_email');

        echo '<input type="email" size="30" id="send_results_to" name="send_to_archive_org_options[send_results_to]" value="' . esc_attr($send_to) . '">';
    }

    public function register_options_page() {
        add_options_page(
            __( 'Send to Archive.org Settings', 'send-to-archive-org' ),
            'Send to Archive.org',
            'manage_options',
            'send_to_archive_org',
            array( $this, 'options_page_html' )
        );
    }

    public function options_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        settings_errors( 'send_to_archive_org_messages' );

        include 'partials/send-to-archive-org-admin-display.php';
    }

    public function get_filtered_post_types() {
        $args = array(
            'public' => true,
            '_builtin' => false
        );

        $post_types['post'] = get_post_type_object('post');
        $post_types['page'] = get_post_type_object('page');
        $post_types = array_merge( $post_types,  get_post_types($args, 'objects') );

        return $post_types;
    }

    public function add_row_action( $actions, $post ) {
        $selected_post_types = get_option('send_to_archive_org_options')['post_types'] ?? array();

        if (in_array($post->post_type, $selected_post_types) && $post->post_status === 'publish') {
            $actions['send_to_archive_org'] = '<a href="#" class="send_to_archive_org" data-post_title="'. $post->post_title .'" data-post_id="' . $post->ID . '">'. __('Send to Archive.org', 'send-to-archive-org' ) . '</a>';
        }

        return $actions;
    }

    public function add_archive_org_column( $columns ) {
        $selected_post_types = get_option('send_to_archive_org_options')['post_types'] ?? array();
        $show_archive_column = get_option('send_to_archive_org_options')['show_archive_column'] ?? false;
        $post_type = get_query_var('post_type');

        if ( in_array($post_type, $selected_post_types) && $show_archive_column ) {
            $columns['archive_org'] = __( 'Archive.org', 'send-to-archive-org' );
        }

        return $columns;
    }

    public function add_archive_org_column_content( $column, $post_id ) {
        if ( 'archive_org' === $column ) {
            echo esc_html(__('Checking...', 'send-to-archive-org' ));
        }
    }

    private function split_timestamp($timestamp) {
        $year = substr( $timestamp, 0, 4 );
        $month = substr( $timestamp, 4, 2 );
        $day = substr( $timestamp, 6, 2 );
        $hour = substr( $timestamp, 8, 2 );
        $minute = substr( $timestamp, 10, 2 );
        $second = substr( $timestamp, 12, 2 );
        return array(
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second,
        );
    }

    private function format_timestamp($timestamp) {
        if (substr($timestamp, 0, 1) === '_') {
            $timestamp = substr($timestamp, 1);
        }

        $split_timestamp = $this->split_timestamp($timestamp);
        $t_date = date_i18n( __( 'Y/m/d' ), strtotime( $split_timestamp['year'] . '-' . $split_timestamp['month'] . '-' . $split_timestamp['day'] ) );
        $t_time = date_i18n( __( 'g:i a' ), strtotime( $split_timestamp['hour'] . ':' . $split_timestamp['minute'] . ':' . $split_timestamp['second'] ) );

		/* translators: 1: date, 2: time */
	    return sprintf( __('%1$s at %2$s'), $t_date, $t_time );
    }

    private function build_link_to_archive_org($timestamp, $permalink) {
        $archive_url = 'https://web.archive.org/web/'. $timestamp .'/'. $permalink;
        $formatted_datetime = $this->format_timestamp($timestamp);
        return "<a target='_blank' href='$archive_url'>". $formatted_datetime ."</a>";
    }

    public function handle_post_save( $post_id, $post, $update ) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (post_password_required($post_id)) return;
        if ($post->post_status !== 'publish') return;

        $options = get_option('send_to_archive_org_options');
        $selected_post_types = $options['post_types'] ?? array();

        if (in_array($post->post_type, $selected_post_types)) {
            $permalink = get_permalink($post_id);

            if (false === $permalink || empty($permalink)) return;

            delete_transient('archive_org_timestamp_'.$post_id);
            $this->send_to_archive_org($post_id, $options, isset($_POST['_inline_edit']));
        }
    }

    public function send_to_archive_org($post_id = null, $options = null, $is_quick_edit = false) {
        if (wp_doing_ajax()) {
            if (!$is_quick_edit) {
                check_ajax_referer('security', 'nonce');
            }
            if (isset($_POST['post_id'])) {
                $post_id = $_POST['post_id'];
            } elseif (isset($_POST['post_ID'])) {
                $post_id = $_POST['post_ID'];
            }
        } elseif (isset($_REQUEST['nonce']) && !wp_verify_nonce($_REQUEST['nonce'], 'security')) {
            wp_die(esc_html(__('Security check failed', 'send-to-archive-org')));
        }

        if (post_password_required($post_id) || get_post_status($post_id) !== 'publish') return;
        $post_url = get_permalink($post_id);
        $result = $this->send_email($post_url);

        if (!$is_quick_edit && wp_doing_ajax()) {
            if (is_wp_error($result)) {
                wp_send_json_error( $result->get_error_message() );
            } else {
                $timestamp = get_transient('archive_org_timestamp_'.$post_id);
                $result = self::ICON_INFO . __('Sent to Archive.org on', 'send-to-archive-org' ) . $this->format_timestamp($timestamp);
                wp_send_json_success( $result );
            }
        }
    }

    private function send_email($permalinks) {
        $options = get_option('send_to_archive_org_options');
        $email_to = 'savepagenow@archive.org';

        $posts_urls = '';
        $count = 0;
        if (is_array($permalinks)) {
            foreach ($permalinks as $permalink) {
                $post_id = url_to_postid($permalink);
                if (post_password_required($post_id) || get_post_status($post_id) !== 'publish') continue;
                $posts_urls .= $permalink . "\n";
                $count++;
            }
            $email_subject = __('Save bulk request', 'send-to-archive-org' );
        } else {
            $posts_urls = $permalinks;
            $email_subject = 'Save request for ' . $permalinks;
            $count++;
            if (post_password_required($permalinks) || get_post_status(url_to_postid($permalinks)) !== 'publish') {
                $count = 0;
            }
        }

        if ($count === 0) return new WP_Error('empty_url', __('There are no posts to submit', 'send-to-archive-org'));

        $headers = array();
        if (isset($options['report_result']) && $options['report_result'] === 'yes') {
            $headers = array( 'From:' . $options['send_results_to'] ?? get_bloginfo('admin_email') );
        }
        $email_message = $posts_urls;
        $result = wp_mail($email_to, $email_subject, $email_message, $headers);
        $current_time = current_time('timestamp');
        $t_datetime = date_i18n(__('YmdHis'), $current_time);
        if (is_array($permalinks)) {
            foreach ($permalinks as $permalink) {
                $post_id = url_to_postid($permalink);
                set_transient('archive_org_timestamp_'.$post_id, '_'.$t_datetime, HOUR_IN_SECONDS);
            }
        } else {
            $post_id = url_to_postid($permalinks);
            set_transient('archive_org_timestamp_'.$post_id, '_'.$t_datetime, HOUR_IN_SECONDS);
        }

        return $result;
    }

    public function get_archive_org_availability() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'security')) {
            wp_die(esc_html(__('Security check failed', 'send-to-archive-org')));
        }

        $posts = $_POST['posts'] ?? array($_POST['post']);
        $posts_timestamps = array();
        $results = array();

        foreach($posts as $post) {
            $timestamp = get_transient('archive_org_timestamp_'.$post);
            $permalink = get_permalink($post);

            if ($timestamp) {
                $posts_timestamps[$post] = ['timestamp' => $timestamp, 'permalink' => $permalink];
            } else {
                $url_api = 'https://archive.org/wayback/available?url=' . $permalink;
                $response = wp_remote_get( esc_url_raw( $url_api ) );

                if ( !is_wp_error( $response ) ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ), true );
                    $timestamp = $data['archived_snapshots']['closest']['timestamp'] ?? '0';

                    set_transient('archive_org_timestamp_'.$post, $timestamp, HOUR_IN_SECONDS);
                    $posts_timestamps[$post] = ['timestamp' => $timestamp, 'permalink' => $permalink];

                } else {
                    $posts_timestamps[$post] = ['timestamp' => null, 'permalink' => $permalink];
                }
            }
        }

        foreach ($posts_timestamps as $post => $values) {
            if ( is_null($values['timestamp']) ) {
                $results[$post] = self::ICON_NOT_ARCHIVED . __('Failed to query Archive.org', 'send-to-archive-org' );
            } else {
                if ( $values['timestamp'] !== '0' ) {

                    if (substr($values['timestamp'], 0, 1) === '_') {
                        $results[$post] = self::ICON_INFO . __('Sent to Archive.org on', 'send-to-archive-org' ) . ' '. $this->format_timestamp($values['timestamp']);
                        continue;
                    }

                    $last_modified_gmt = get_post_modified_time('Y-m-d H:i:s', true, $post);
                    if ( $last_modified_gmt ) {
                        $split_timestamp = $this->split_timestamp($values['timestamp']);
                        $timestamp_datetime = $split_timestamp['year'] . '-' . $split_timestamp['month'] . '-' . $split_timestamp['day'] . ' ' . $split_timestamp['hour'] . ':' . $split_timestamp['minute'] . ':' . $split_timestamp['second'];
                        if ( strtotime( $timestamp_datetime ) > strtotime( $last_modified_gmt ) ) {
                            $icon = self::ICON_ARCHIVED;
                        } else {
                            $icon = self::ICON_WARNING;
                        }
                    } else {
                        $icon = self::ICON_ARCHIVED;
                    }
                    $results[$post] = $icon . $this->build_link_to_archive_org($values['timestamp'], $values['permalink'] );
                } else {
                    $results[$post] = self::ICON_NOT_ARCHIVED . __('Not archived yet', 'send-to-archive-org' );
                }
            }
        }

        wp_send_json_success( $results );

    }

    public function send_to_archive_org_bulk_action( $actions ) {
        $selected_post_types = get_option('send_to_archive_org_options')['post_types'] ?? array();
        $post_type = get_query_var('post_type');

        if ( in_array($post_type, $selected_post_types) ) {
            $actions['send_to_archive_org'] = __( 'Send to Archive.org', 'send-to-archive-org' );
        }

        return $actions;
    }

    public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
        if ( $action !== 'send_to_archive_org' ) {
            return $redirect_to;
        }

        $permalinks = array();
        foreach ( $post_ids as $post_id ) {
            $permalinks[] = get_permalink($post_id) . "\n";
        }

        $result = $this->send_email($permalinks);

        if (is_wp_error($result)) {
            $redirect_to_with_params = add_query_arg(array(
                'send_to_archive_org' => '1',
                'sent_result' => 0,
                'message' => $result->get_error_message()
            ), $redirect_to);
        } else {
            $redirect_to_with_params = add_query_arg(array(
                'send_to_archive_org' => '1',
                'sent_result' => 1
            ), $redirect_to);
        }

        return $redirect_to_with_params;

    }

    public function display_admin_notice() {
        if (!empty($_REQUEST['send_to_archive_org'])) {
            $result = boolval($_REQUEST['sent_result']);
            if ($result) {
                $message = __('The request has been sent to Archive.org', 'send-to-archive-org' );
                $class = 'notice-success';
            } else {
                $message = $_REQUEST['message'] ?? __('Error sending request to Archive.org', 'send-to-archive-org');
                $class = 'notice-error';
            }
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_attr($message) . '</p></div>';
        }
    }


}
