<?php
/**
Plugin Name: Aspexi Sweet Popups
Plugin URI:  http://aspexi.com/downloads/aspexi-sweet-popups/?src=free_plugin
Description: Simple popups plugin based on Sweet Alert that automatically centers itself on the page and looks great no matter if you're using a desktop computer, mobile or tablet.
Author: Aspexi
Author URI: http://aspexi.com/
Version: 1.1.3

License: GPLv2 or later

    © Copyright 2019 Aspexi
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

defined('ABSPATH') or exit();

if ( ! class_exists( 'AspexiSweetPopups' ) ) {

    define('ASPEXISWEETPOPUPS_VERSION', '1.1.3');
    define('ASPEXISWEETPOPUPS_URL', plugin_dir_url( __FILE__ ) );
    define('ASPEXISWEETPOPUPS_ADMIN_URL', 'themes.php?page=' . basename( __FILE__ ) );

    class AspexiSweetPopups
    {
        protected $config   = array();
        protected $messages = array();
        protected $errors   = array();

        private $cookie_name = 'showasp';

        public function __construct() {

            $this->settings();

            add_action( 'admin_menu',           array( &$this, 'admin_menu' ) );
            add_action( 'init',                 array( &$this, 'init' ), 10 );
            add_action( 'wp_enqueue_scripts',   array( &$this, 'init_scripts' ) );
            add_action( 'admin_enqueue_scripts',array( &$this, 'admin_scripts' ) );

            add_filter( 'plugin_action_links',  array( &$this, 'settings_link' ), 10, 2);

            register_uninstall_hook( __FILE__, array( 'AspexiSweetPopups', 'uninstall' ) );
        }

        /* WP init action */
        public function init() {

            /* Internationalization */
            load_plugin_textdomain( 'aspexisweetpopups', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }

        public function admin_menu() {

            add_submenu_page( 'themes.php', __( 'Aspexi Sweet Popups', 'aspexisweetpopups' ), __( 'Aspexi Sweet Popups', 'aspexisweetpopups' ), 'manage_options', basename(__FILE__), array( &$this, 'admin_page' ) );
        }

        public function settings() {

            $config_default = array(
                'aspexisweetpopups_version' => ASPEXISWEETPOPUPS_VERSION,
                'status' => 'disabled',
                'only_frontpage' => 'off',
                'title' => 'Your title',
                'content' => 'Your content',
                'only_once' => 'off',
                'only_once_days' => 30,
                'icon_type' => 'empty',
            );

            if ( ! get_option( 'aspexisweetpopups_options' ) )
                add_option( 'aspexisweetpopups_options', $config_default, '', 'yes' );

            $this->config = get_option( 'aspexisweetpopups_options' );
        }

        public function get_content_with_shortcodes() {

            $content = nl2br( $this->config['content'] );
            $matches = array();
            preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches );

            if ( ! empty( $matches[2] ) )

                foreach ( $matches[2] as $key => $shortcode )

                    $content = str_replace( $matches[0][$key], do_shortcode( $matches[0][$key] ), $content );

            return $content;
        }

        public function init_scripts() {

            $disable_maybe = apply_filters( 'aspexisweetpopups_disablemaybe', false );

            if( $this->config['status'] != 'enabled' || ( $this->config['only_frontpage'] == 'on' && ! is_front_page() ) || $disable_maybe )

                return;

            wp_enqueue_style( 'sweet-alert', ASPEXISWEETPOPUPS_URL . 'css/sweetalert.css' );

            wp_enqueue_script( 'sweet-alert', ASPEXISWEETPOPUPS_URL . 'js/sweetalert.min.js' );
            wp_enqueue_script( 'aspexi-sweet-popups', ASPEXISWEETPOPUPS_URL . 'js/asp.js', array( 'sweet-alert', 'jquery') );
            wp_localize_script( 'aspexi-sweet-popups', 'asp', array(
                'show'      => ( isset( $_COOKIE[$this->cookie_name] ) && 'on' == $this->config['only_once'] ) ? false : true,
                'title'     => $this->config['title'],
                'content'   => $this->get_content_with_shortcodes(),
                'icon_type' => $this->config['icon_type'],
                'html'      => 'true'
            ) );

            $_expire = 0;

            if ( $this->config['only_once_days'] != '' )

                $_expire = absint( (int)$this->config['only_once_days'] );

            if( ! isset( $_COOKIE[$this->cookie_name] ) && 'on' == $this->config['only_once'] ) {

                if( 0 == $_expire )

                    $expire = 0;

                else
                    
                    $expire = time() + ( 60 * 60 * 24 * $_expire );

                setcookie( $this->cookie_name, 'false', $expire );

            }

            elseif( isset( $_COOKIE[$this->cookie_name] ) && 'on' != $this->config['only_once'] )
                // remove cookie
                setcookie( $this->cookie_name, '', time() - 360 );
        }

        public function admin_scripts( $hook_suffix ) {

            if( 'appearance_page_'.basename(__FILE__, '.php') != $hook_suffix )
                return;

            wp_enqueue_style( 'sweet-alert', ASPEXISWEETPOPUPS_URL . 'css/sweetalert.css' );
            wp_enqueue_style( 'sweet-popups', ASPEXISWEETPOPUPS_URL . 'css/sweet-popups.css' );

            wp_enqueue_script( 'sweet-alert', ASPEXISWEETPOPUPS_URL . 'js/sweetalert.min.js' );
            wp_enqueue_script( 'aspexi-sweet-popups', ASPEXISWEETPOPUPS_URL . 'js/asp-admin.js', array( 'sweet-alert', 'jquery') );
            wp_localize_script( 'aspexi-sweet-popups', 'asp', array(
                'show'      => ( isset( $_COOKIE[$this->cookie_name] ) && 'on' == $this->config['only_once'] ) ? false : true,
                'title'     => $this->config['title'],
                'content'   => nl2br( $this->config['content'] ),
                'icon_type' => $this->config['icon_type'],
                'html'      => 'true',
                'aspexisweetpopups_url' => ASPEXISWEETPOPUPS_URL,
                'nav_tab_changed_title' => __( 'Unsaved changes!', 'aspexisweetpopups' ),
                'nav_tab_changed_text' => __( 'If you leave this page now the changes you have made may be lost.', 'aspexisweetpopups' ),
                'nav_tab_changed_yes' => __( 'Stay and continue', 'aspexisweetpopups' ),
                'nav_tab_changed_no' => __( 'Drop changes and leave', 'aspexisweetpopups' ),
            ) );
        }

        public function get_pro_url() {

            return 'http://aspexi.com/downloads/aspexi-sweet-popups/?src=free_plugin';
        }

        public function get_pro_link() {

            return '<a href="'.$this->get_pro_url().'" target="_blank">'.__( 'Get PRO version', 'aspexisweetpopups' ).'</a>';
        }

        public function settings_link( $action_links, $plugin_file ) {

            if( $plugin_file == plugin_basename(__FILE__) ) {
                $pro_link = $this->get_pro_link();
                array_unshift( $action_links, $pro_link );

                $settings_link = '<a href="themes.php?page=' . basename( __FILE__ )  .  '">' . __("Settings") . '</a>';
                array_unshift( $action_links, $settings_link );
            }

            return $action_links;
        }

        public static function uninstall() {

            delete_option( 'aspexisweetpopups_options' );
        }

        public function display_admin_notices( $echo = false ) {

            $ret = '';

            foreach( (array)$this->errors as $error )
                $ret .= '<div class="error fade"><p><strong>'.$error.'</strong></p></div>';

            foreach( (array)$this->messages as $message )
                $ret .= '<div class="updated fade"><p><strong>'.$message.'</strong></p></div>';

            if( $echo )
                echo $ret;
            else
                return $ret;
        }

        public function admin_page() {

            if ( ! current_user_can('manage_options') )

                wp_die( __('You do not have sufficient permissions to access this page.') );

            if ( isset( $_REQUEST['asp_form_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'asp_nonce_name' ) ) {

                if ( ! in_array( $_REQUEST['asp_status'], array( 'enabled', 'disabled' ) ) )

                    $this->add_error( __( 'Wrong or missing status. Available statuses: enabled and disabled. Settings not saved.' ) );

                if ( ! in_array( $_REQUEST['asp_icon_type'], array( 'empty', 'warning', 'error', 'success', 'info' ) ) )

                    $this->add_error( __( 'Wrong icon type. Available types: empty, warning, error, success, info. Settings not saved.' ) );

                if( isset( $_REQUEST['asp_content'] ) ) {

                    $_asp_content = stripslashes( wp_kses_post( balanceTags( $_REQUEST['asp_content'], true ) ) );

                    if( ! strlen( $_asp_content ) )

                        $this->add_error( __( 'Missing Popup content. Settings not saved.') );

                } else 

                    $this->add_error( __( 'Missing Popup content. Settings not saved.') );

                if ( ! $this->has_errors() ) {

                    $aspexisweetpopups_request_options = array();
                    $aspexisweetpopups_request_options['status'] = isset( $_REQUEST['asp_status'] ) ? sanitize_text_field( $_REQUEST['asp_status'] ) : '';
                    $aspexisweetpopups_request_options['only_frontpage'] = isset( $_REQUEST['asp_only_frontpage'] ) ? sanitize_text_field( $_REQUEST['asp_only_frontpage'] ) : '';
                    $aspexisweetpopups_request_options['title'] = isset( $_REQUEST['asp_title'] ) ? sanitize_text_field( $_REQUEST['asp_title'] ) : '';
                    $aspexisweetpopups_request_options['content'] = $_asp_content;
                    $aspexisweetpopups_request_options['only_once'] = isset( $_REQUEST['asp_only_once'] ) ? sanitize_text_field( $_REQUEST['asp_only_once'] ) : '';
		            if( 'on' == $aspexisweetpopups_request_options['only_once'] )
                        $aspexisweetpopups_request_options['only_once_days'] = isset( $_REQUEST['asp_only_once_days'] ) ? absint( (int) $_REQUEST['asp_only_once_days'] ) : '';
                    $aspexisweetpopups_request_options['icon_type'] = isset( $_REQUEST['asp_icon_type'] ) ? sanitize_text_field( $_REQUEST['asp_icon_type'] ) : '';

                    $this->config = array_merge( $this->config, $aspexisweetpopups_request_options );

                    update_option( 'aspexisweetpopups_options',  $this->config, 'yes' );

                    $this->add_message( __( 'Settings saved.', 'aspexisweetpopups' ) );
                }
            }

            ?>
            <div class="wrap">
            <?php $this->display_admin_notices( true ); ?>
            <h1><?php _e( 'Aspexi Sweet Popups Settings', 'aspexisweetpopups' ); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a class="nav-tab <?php echo ( ! isset( $_GET['newsletter'] ) && ! isset( $_GET['shortcode'] ) ) ? 'nav-tab-active' : '' ; ?>" href="<?php echo ASPEXISWEETPOPUPS_ADMIN_URL; ?>"><?php echo __( 'Settings', 'aspexisweetpopups' ); ?></a>
                <a class="nav-tab <?php echo ( isset( $_GET['shortcode'] ) ) ? 'nav-tab-active' : '' ; ?>" href="<?php echo ASPEXISWEETPOPUPS_ADMIN_URL; ?>&shortcode=true"><?php echo __( 'Shortcode', 'aspexisweetpopups' ); ?></a>
                <a class="nav-tab <?php echo ( isset( $_GET['newsletter'] ) && strlen( $_GET['newsletter'] ) ) ? 'nav-tab-active' : '' ; ?>" href="<?php echo ASPEXISWEETPOPUPS_ADMIN_URL; ?>&newsletter=mailchimp"><?php echo __( 'Newsletter', 'aspexisweetpopups' ); ?></a>
            </h2>
            <br>
            <?php if ( isset( $_GET['shortcode'] ) && strlen( $_GET['shortcode' ] ) ) : ?>
                <div class="postbox">
                    <div class="inside">
                        <h3>
                            <span>Shortcode</span>
                        </h3>
                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
                                <td>
                                    <?php echo $this->get_pro_link(); ?><br>
                                    <p style="font-family: monospace;">[aspexi_sweet_popups theme="facebook" close_button_button="on" close_button_icon="on" close_button_color="#8cd4f5" like_box="on" like_box_protocol="http" like_box_option_url="any" like_box_url="www.facebook.com/facebook" like_box_layout="box_count" only_home="on" only_bottom="on" auto_open="on" auto_open_time="3000" auto_open_on_element="off" auto_open_on_element_name="#element_name" title="Your Title" only_once="on" only_once_days="30" icon_type="success"]Your Content[/aspexi_sweet_popups]</p>
                                    <br><br>
                                    <span><?php echo __( 'Avaliable options (accepted values separated by "|")', 'aspexisweetpopups' ); ?>:</span>
                                    <ul>
                                        <li><b>theme</b>: none|facebook|google|twitter</li>
                                        <li><b>close_button_button</b>: on|off (Default: on)</li>
                                        <li><b>close_button_icon</b>: on|off (Default: off)</li>
                                        <li><b>close_button_color</b>: (css color) (Default: #8cd4f58cd4f5)</li>
                                        <li><b>like_box</b>: on|off (Default: off)</li>
                                        <li><b>like_box_protocol</b>: http|https (Default: http)</li>
                                        <li><b>like_box_option_url</b>: any|site|page (Default: any)</li>
                                        <li><b>like_box_url</b>: (url, for example: www.facebook.com/facebook) (Default: empty)</li>
                                        <li><b>like_box_layout</b>: standard|box_count|button_count|button (Default: standard)</li>
                                        <li><b>only_home</b>: on|off (Default: off)</li>
                                        <li><b>only_bottom</b>: on|off (Default: off)</li>
                                        <li><b>auto_open</b>: on|off (Default: off)</li>
                                        <li><b>auto_open_time</b>: (time in milliseconds) (Default: 3000)</li>
                                        <li><b>auto_open_on_element</b>: on|off (Default: off)</li>
                                        <li><b>auto_open_on_element_name</b>: (jQuery element, for example #element_name) (Default: empty)</li>
                                        <li><b>title</b>: (text) (Default empty)</li>
                                        <li><b>only_once</b>: on|off (Default: off)</li>
                                        <li><b>only_once_days</b>: (Show only once per user (based on cookies)) (Default: 1)</li>
                                        <li><b>icon_type</b>: empty|info|warning|success|error (Default: empty)</li>
                                    </ul>
                                    <br>
                                    <span><?php echo __( 'Example 1', 'aspexisweetpopups' ); ?>:</span><br>
                                    <p style="font-family: monospace;">[aspexi_sweet_popups]Your Content[/aspexi_sweet_popups]</p>
                                    <br><br>
                                    <span><?php echo __( 'Example 2', 'aspexisweetpopups' ); ?>:</span><br>
                                    <p style="font-family: monospace;">[aspexi_sweet_popups close_button_button="off" close_button_icon="on" title="custom title"]Your Content[/aspexi_sweet_popups]</p>
                                    <br><br>
                                    <span><?php echo __( 'Example 3', 'aspexisweetpopups' ); ?>:</span><br>
                                    <p style="font-family: monospace;">[aspexi_sweet_popups theme="facebook" close_button_button="on" close_button_icon="on" like_box="on" like_box_protocol="http" like_box_url="www.facebook.com/facebook" like_box_layout="standard" auto_open="on" auto_open_time="3000" title="Your Title" icon_type="info" ]Your Content[/aspexi_sweet_popups]</p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( isset( $_GET['newsletter'] ) && strlen( $_GET['newsletter'] ) ) : ?>
                <h2 class="nav-tab-wrapper">
                    <a class="nav-tab <?php echo ( isset( $_GET['newsletter'] ) && $_GET['newsletter'] == 'mailchimp' ) ? 'nav-tab-active' : '' ; ?>" href="<?php echo ASPEXISWEETPOPUPS_ADMIN_URL; ?>&newsletter=mailchimp"><?php echo __( 'MailChimp', 'aspexisweetpopups' ); ?></a>
                </h2>
                <br>
                <div class="postbox">
                    <div class="inside">
                        <h3><?php echo __( 'MailChimp Integration', 'aspexisweetpopups'); ?></h3>
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th><?php echo __( 'MailChimp API Key', 'aspexisweetpopups' ); ?></th>
                                <td>
                                    <input type="text" name="asp_mailchimp_key" disabled readonly><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo __( 'MailChimp Lists', 'aspexisweetpopups' ); ?></th>
                                <td>
                                    <select name="asp_mailchimp_list_id" disabled readonly>
                                    </select><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo __( 'Enable Double Opt-In', 'aspexisweetpopups' ); ?></th>
                                <td>
                                    <input type="checkbox" name="asp_mailchimp_optin" disabled readonly><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo __( 'To display MailChimp write shortocde in popup content', 'aspexisweetpopups' ); ?></th>
                                <td>
                                    <small><pre>[aspexi_newsletter]</pre></small><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p><input class="button-primary" type="submit" name="send" value="<?php _e('Save settings', 'aspexisweetpopups'); ?>" id="submitbutton" /></p>
                <div class="postbox">
                    <div class="inside">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th><?php echo __( 'Write your button text', 'aspexisweetpopups' ); ?></th>
                                <td>
                                    <input type="text" name="asp_newsletter_button_text" disabled readonly><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo __( 'Success message', 'aspexisweetpopups' ); ?></th>
                                <td>
                                    <input type="text" name="asp_newsletter_success_message" disabled readonly><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo __( 'Error message', 'aspexisweetpopups' ); ?></th>
                                <td>
                                    <input type="text" name="asp_newsletter_error_message" disabled readonly><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p><input class="button-primary" type="submit" name="send" value="<?php _e('Save settings', 'aspexisweetpopups'); ?>" id="submitbutton" /></p>
            <?php endif; ?>
            <?php if ( ! isset( $_GET['shortcode'] ) && ! isset( $_GET['newsletter' ] ) ) : ?>
            <form method="post" action="<?php echo ASPEXISWEETPOPUPS_ADMIN_URL; ?>">
                <input type="hidden" name="asp_form_submit" value="submit" />
                <?php wp_nonce_field( plugin_basename( __FILE__ ), 'asp_nonce_name' ); ?>
                <div class="postbox">
                    <div class="inside">
                        <h3>
                            <span><?php echo __( 'Basic', 'aspexisweetpopups' ); ?></span>
                        </h3>
                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Sweet Popups', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <select name="asp_status">
                                        <option value="enabled" <?php echo ($this->config['status'] == 'enabled') ? 'selected' : ''; ?>><?php _e('Enabled', 'aspexisweetpopups'); ?></option>
                                        <option value="disabled" <?php echo ($this->config['status'] == 'disabled') ? 'selected' : ''; ?>><?php _e('Disabled', 'aspexisweetpopups'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Show only on the home page', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <input type="checkbox" name="asp_only_frontpage" value="on" <?php echo ($this->config['only_frontpage'] == 'on') ? 'checked' : ''; ?>><br>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Show when user reaches bottom of the page', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <input type="checkbox" name="asp_only_bottom" value="on" disabled readonly><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Popup title', 'aspexisweetpopups' ); ?>
                                </th>
                                <td>
                                    <input type="text" name="asp_title" value="<?php echo esc_html( $this->config['title'] ); ?>" style="width: 320px;">
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Popup content', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <textarea name="asp_content"cols="30" rows="10" style="width: 320px;"><?php echo esc_textarea( $this->config['content'] ); ?></textarea><br>
                                    <p><?php _e( 'You can use simple HTML and shortcodes.', 'aspexisweetpopups' ); ?></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Show only once per user (based on cookies)', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <input type="checkbox" name="asp_only_once" <?php echo ( 'on' == $this->config['only_once'] ) ? 'checked' : ''; ?> value="on"><br>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Cookie lifetime (in days)', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <input type="text" name="asp_only_once_days" size="3" value="<?php echo absint( (int)$this->config['only_once_days'] ); ?>" <?php echo ( 'on' != $this->config['only_once'] ) ? 'disabled' : ''; ?>>&nbsp;&nbsp;<?php echo __( 'If omitted or zero, the cookie will expire at the end of the session.', 'aspexisweetpopups' ); ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p>
                    <input class="button-primary" type="submit" name="send" value="<?php _e('Save settings', 'aspexisweetpopups'); ?>" />
                    <input class="button-secondary previewbutton" type="submit" value="<?php _e('Preview', 'aspexisweetpopups'); ?>" />
                </p>
                <div class="postbox">
                    <div class="inside">
                        <h3>
                            <span><?php echo __( 'Look & Feel', 'aspexisweetpopups' ); ?></span>
                        </h3>
                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Theme', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <div id="theme-box">
                                        <div>
                                            <label for="theme_none"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/theme_none.png' ?>" alt=""></label><br>
                                            <input id="theme_none" type="radio" name="asp_theme" value="none" checked readonly disabled>
                                            <p><?php echo __( 'Default', 'aspexisweetpopups' ); ?></p>
                                        </div>
                                        <div>
                                            <label for="theme_facebook"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/theme_facebook.png' ?>" alt=""></label><br>
                                            <input id="theme_facebook" type="radio" name="asp_theme" value="facebook" readonly disabled>
                                            <p>Facebook</p>
                                        </div>
                                        <div>
                                            <label for="theme_google"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/theme_google.png' ?>" alt=""></label><br>
                                            <input id="theme_google" type="radio" name="asp_theme" value="google" readonly disabled>
                                            <p>Google</p>
                                        </div>
                                        <div>
                                            <label for="theme_twitter"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/theme_twitter.png' ?>" alt=""></label><br>
                                            <input id="theme_twitter" type="radio" name="asp_theme" value="twitter" readonly disabled>
                                            <p>Twitter</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Icon type', 'aspexisweetpopups'); ?>
                                </th>
                                <td style="text-align: center;">
                                    <div id="icon-type-box">
                                        <div>
                                            <label for="aps_icon_type_empty">
                                                <img src="<?php echo ASPEXISWEETPOPUPS_URL ?>images/empty.png" alt=""><br><br>
                                                <input id="aps_icon_type_empty" type="radio" name="asp_icon_type" <?php echo ( 'empty' == $this->config['icon_type'] ) ? 'checked' : ''; ?> value="empty"><br><?php _e('Empty', 'aspexisweetpopups'); ?>
                                            </label>
                                        </div>
                                        <div>
                                            <label for="aps_icon_type_info">
                                                <img src="<?php echo ASPEXISWEETPOPUPS_URL ?>images/info.png" alt=""><br><br>
                                                <input id="aps_icon_type_info" type="radio" name="asp_icon_type" <?php echo ( 'info' == $this->config['icon_type'] ) ? 'checked' : ''; ?> value="info"><br><?php _e('Info', 'aspexisweetpopups'); ?>
                                            </label>
                                        </div>
                                        <div>
                                            <label for="aps_icon_type_warning">
                                                <img src="<?php echo ASPEXISWEETPOPUPS_URL ?>images/warning.png" alt=""><br><br>
                                                <input id="aps_icon_type_warning" type="radio" name="asp_icon_type" <?php echo ( 'warning' == $this->config['icon_type'] ) ? 'checked' : ''; ?> value="warning" style="vertical-align: bottom;"><br><?php _e('Warning', 'aspexisweetpopups'); ?>
                                            </label>
                                        </div>
                                        <div>
                                            <label for="aps_icon_type_success">
                                                <img src="<?php echo ASPEXISWEETPOPUPS_URL ?>images/success.png" alt=""><br><br>
                                                <input id="aps_icon_type_success" type="radio" name="asp_icon_type" <?php echo ( 'success' == $this->config['icon_type'] ) ? 'checked' : ''; ?> value="success"><Br><?php _e('Success', 'aspexisweetpopups'); ?>
                                            </label>
                                        </div>
                                        <div>
                                            <label for="aps_icon_type_error">
                                                <img src="<?php echo ASPEXISWEETPOPUPS_URL ?>images/error.png" alt=""><br><br>
                                                <input id="aps_icon_type_error" type="radio" name="asp_icon_type" <?php echo ( 'error' == $this->config['icon_type'] ) ? 'checked' : ''; ?> value="error"><br><?php _e('Error', 'aspexisweetpopups'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Select close button', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <?php echo $this->get_pro_link(); ?><br><br>
                                    <div style="float: left; text-align: center; padding: 10px;">
                                        <label for="close_button_button"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/close_button_button.png'; ?>" alt=""></label><br>
                                        <input id="close_button_button" type="checkbox" name="asp_close_button_button" value="on" disabled readonly><br>
                                        <p><?php echo __( 'Button', 'aspexisweetpopups' ); ?></p>
                                    </div>
                                    <div style="float: left; margin-left: 30px; text-align: center; padding: 10px;">
                                        <label for="close_button_icon"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/close_button_icon.png'; ?>" alt=""></label><br>
                                        <input id="close_button_icon" type="checkbox" name="asp_close_button_icon" value="on" disabled readonly><br>
                                        <p><?php echo __( 'Icon', 'aspexisweetpopups' ); ?></p>
                                    </div>
                                    <br><br><br><br><br><br><br><br>
                                    <?php echo __( 'You can add own element with class "close-sweet-popup" which close popup', 'aspexisweetpopups' ); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Close button color', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <input type="text" name="asp_close_button_color" value="#8cd4f5" disabled readonly size="6" /><br>
                                    <p><?php echo __( 'This will apply to the button color as well as long as the default theme is used.', 'sweetpopups' ); ?></p>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p>
                    <input class="button-primary" type="submit" name="send" value="<?php _e('Save settings', 'aspexisweetpopups'); ?>" />
                    <input class="button-secondary previewbutton" type="submit" value="<?php _e('Preview', 'aspexisweetpopups'); ?>" />
                </p>
                <div class="postbox">
                    <div class="inside">
                        <h3>
                            <span>Facebook</span>
                        </h3>
                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Show Facebook Like Button', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <input type="checkbox" name="asp_like_box" value="on" disabled readonly><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Like Button URL', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <select name="asp_like_box_protocol">
                                        <option value="http" disabled readonly>http://</option>
                                        <option value="https" disabled readonly>https://</option>
                                    </select>
                                    <select name="asp_like_box_option_url">
                                        <option value="any" disabled readonly><?php echo __( 'Any', 'aspexisweetpopups' ); ?></option>
                                        <option value="site" disabled readonly><?php echo __( 'Site url', 'aspexisweetpopups' ); ?></option>
                                        <option value="page" disabled readonly><?php echo __( 'Page url', 'aspexisweetpopups' ); ?></option>
                                    </select>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e('Like Button Layout', 'aspexisweetpopups'); ?>
                                </th>
                                <td>
                                    <?php echo $this->get_pro_link(); ?><br><br>
                                    <div id="like-button-layout-box">
                                        <div>
                                            <label for="like_button_layout_standard"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/like_button_layout_standard.png' ?>" alt=""></label><br>
                                            <input id="like_button_layout_standard" type="radio" name="asp_like_box_layout" value="standard" disabled readonly>
                                            <p>standard</p>
                                        </div>
                                        <div>
                                            <label for="like_button_layout_box_count"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/like_button_layout_box_count.png' ?>" alt=""></label><br>
                                            <input id="like_button_layout_box_count" type="radio" name="asp_like_box_layout" value="box_count" disabled readonly>
                                            <p>box_count</p>
                                        </div>
                                        <div>
                                            <label for="like_button_layout_button_count"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/like_button_layout_button_count.png' ?>" alt=""></label><br>
                                            <input id="like_button_layout_button_count" type="radio" name="asp_like_box_layout" value="button_count" disabled readonly>
                                            <p>button_count</p>
                                        </div>
                                        <div>
                                            <label for="like_button_layout_button"><img src="<?php echo ASPEXISWEETPOPUPS_URL . 'images/like_button_layout_button.png' ?>" alt=""></label><br>
                                            <input id="like_button_layout_button" type="radio" name="asp_like_box_layout" value="button" disabled readonly>
                                            <p>button</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p>
                    <input class="button-primary" type="submit" name="send" value="<?php _e('Save settings', 'aspexisweetpopups'); ?>" />
                    <input class="button-secondary previewbutton" type="submit" value="<?php _e('Preview', 'aspexisweetpopups'); ?>" />
                </p>
                <div class="postbox">
                    <div class="inside">
                        <h3>
                            <span><?php echo __( 'Advanced', 'aspexisweetpopups' ); ?></span>
                        </h3>
                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
                                <th scope="row"><?php _e('Auto open', 'aspexisweetpopups'); ?></th>
                                <td>
                                    <input type="checkbox" value="on" name="asp_autoopen" disabled readonly /><br>
                                    <?php _e('Auto open after', 'aspexisweetpopups'); ?>&nbsp;<input type="text" name="asp_autoopentime" value="3000" size="4" disabled readonly />&nbsp;<?php _e('milliseconds (1000 milliseconds = 1 second)', 'aspexisweetpopups'); ?><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php _e('Auto open when user reaches element', 'aspexisweetpopups'); ?></th>
                                <td>
                                    <input type="checkbox" value="on" name="asp_autoopenonelement" disabled readonly /><br>
                                    <?php echo __( 'Auto open when user reaches', 'aspexisweetpopups' ); ?>:&nbsp;<input type="text" name="asp_autoopenonelement_name" size="10" value="" disabled readonly><small>&nbsp;&nbsp;&nbsp;<?php echo __( '(jQuery selector for example #element_id, .some_class)', 'aspexisweetpopups' ); ?></small><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php _e('Auto open when user reaches position', 'aspexisweetpopups'); ?></th>
                                <td>
                                    <input type="checkbox" value="on" name="asp_autoopenonposition" disabled readonly /><br>
                                    <?php echo __( 'Auto open when user is', 'aspexisweetpopups' ); ?>:&nbsp;<input type="text" name="asp_autoopenonposition_px" <?php if ( 'on' != $this->config['autoopenonposition'] ) echo 'readonly'; ?> size="5" value="<?php echo $this->config['autoopenonposition_px']; ?>">px&nbsp;from:
                                    <select name="asp_autoopenonposition_name" disabled readonly>
                                        <option value="top" ><?php echo __( 'Top', 'aspexisweetpopups' ); ?></option>
                                        <option value="bottom"><?php echo __( 'Bottom', 'aspexisweetpopups' ); ?></option>
                                    </select><br>
                                    <?php echo $this->get_pro_link(); ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p>
                    <input class="button-primary" type="submit" name="send" value="<?php _e('Save settings', 'aspexisweetpopups'); ?>" />
                    <input class="button-secondary previewbutton" type="submit" value="<?php _e('Preview', 'aspexisweetpopups'); ?>" />
                </p>
            </form>
            <?php endif; ?>
            <br />
            <div class="postbox">
                <div class="inside">
                    <h3><span>Made by</span></h3>
                    <div class="inside">
                        <div style="width: 170px; margin: 0 auto;">
                            <a href="<?php echo $this->get_pro_url(); ?>" target="_blank"><img src="<?php echo ASPEXISWEETPOPUPS_URL.'images/aspexi300.png'; ?>" alt="" border="0" width="150" /></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        private function add_message( $message ) {

            $message = trim( $message );

            if( strlen( $message ) )
                $this->messages[] = $message;
        }

        public function has_errors() {

            return count( $this->errors );
        }

        private function add_error( $error ) {
            $error = trim( $error );

            if( strlen( $error ) )
                $this->errors[] = $error;
        }
    }

    global $aspexi_sweet_popups;
    $aspexi_sweet_popups = new AspexiSweetPopups();
}